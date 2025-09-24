<?php
/**
 * User Controller
 * Handles user management operations
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/FileUpload.php';
require_once __DIR__ . '/BaseController.php';

class UserController extends BaseController {
    
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }
    
    /**
     * Get all users with pagination and filters
     */
    public function getAll($params = []) {
        return $this->index();
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $user = $this->userModel->findById($id);
            if (!$user) {
                return $this->sendError('User not found', 404);
            }
            
            return $this->sendResponse([
                'success' => true,
                'data' => $user
            ]);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch user: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user profile by ID
     */
    public function getProfileById($id) {
        try {
            $user = $this->userModel->getProfile($id);
            if (!$user) {
                return $this->sendError('User not found', 404);
            }
            
            return $this->sendResponse([
                'success' => true,
                'data' => $user
            ]);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch profile: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all users with pagination and filters
     */
    public function index() {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $users = $this->userModel->getUsers($limit, $offset, $search, $role, $status);
            $total = $this->userModel->getUsersCount($search, $role, $status);
            
            $this->sendResponse([
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch users: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single user by ID
     */
    public function show($id) {
        try {
            $user = $this->userModel->getUserById($id);
            
            if (!$user) {
                $this->sendError('User not found', 404);
                return;
            }
            
            // Remove sensitive data
            unset($user['password']);
            unset($user['remember_token']);
            
            $this->sendResponse([
                'success' => true,
                'data' => $user
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch user: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new user
     */
    public function create() {
        try {
            $data = $this->getJsonInput();
            
            // Validate required fields
            $required = ['name', 'email', 'password', 'role'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->sendError("Field '$field' is required", 400);
                    return;
                }
            }
            
            // Check if email already exists
            if ($this->userModel->getUserByEmail($data['email'])) {
                $this->sendError('Email already exists', 400);
                return;
            }
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['status'] = $data['status'] ?? 'active';
            
            $userId = $this->userModel->createUser($data);
            
            if ($userId) {
                $user = $this->userModel->getUserById($userId);
                unset($user['password']);
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => $user
                ], 201);
            } else {
                $this->sendError('Failed to create user');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to create user: ' . $e->getMessage());
        }
    }
    
    /**
     * Update user
     */
    public function update($id) {
        try {
            $data = $this->getJsonInput();
            
            $user = $this->userModel->getUserById($id);
            if (!$user) {
                $this->sendError('User not found', 404);
                return;
            }
            
            // Check if email is being changed and if it already exists
            if (!empty($data['email']) && $data['email'] !== $user['email']) {
                if ($this->userModel->getUserByEmail($data['email'])) {
                    $this->sendError('Email already exists', 400);
                    return;
                }
            }
            
            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if ($this->userModel->updateUser($id, $data)) {
                $updatedUser = $this->userModel->getUserById($id);
                unset($updatedUser['password']);
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'data' => $updatedUser
                ]);
            } else {
                $this->sendError('Failed to update user');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to update user: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete user
     */
    public function delete($id) {
        try {
            $user = $this->userModel->getUserById($id);
            if (!$user) {
                $this->sendError('User not found', 404);
                return;
            }
            
            // Prevent deleting super admin
            if ($user['role'] === 'super_admin') {
                $this->sendError('Cannot delete super admin user', 403);
                return;
            }
            
            if ($this->userModel->deleteUser($id)) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                $this->sendError('Failed to delete user');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to delete user: ' . $e->getMessage());
        }
    }
    
    /**
     * Activate user
     */
    public function activate($id) {
        try {
            if ($this->userModel->updateUser($id, ['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')])) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'User activated successfully'
                ]);
            } else {
                $this->sendError('Failed to activate user');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to activate user: ' . $e->getMessage());
        }
    }
    
    /**
     * Deactivate user
     */
    public function deactivate($id) {
        try {
            if ($this->userModel->updateUser($id, ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')])) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'User deactivated successfully'
                ]);
            } else {
                $this->sendError('Failed to deactivate user');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to deactivate user: ' . $e->getMessage());
        }
    }
    
    /**
     * Get current user profile
     */
    public function getProfile() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->sendError('User not authenticated', 401);
                return;
            }
            
            $user = $this->userModel->getUserById($userId);
            
            if (!$user) {
                $this->sendError('User not found', 404);
                return;
            }
            
            // Remove sensitive data
            unset($user['password']);
            unset($user['remember_token']);
            
            $this->sendResponse([
                'success' => true,
                'data' => $user
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch profile: ' . $e->getMessage());
        }
    }
    
    /**
     * Update current user profile
     */
    public function updateProfile() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->sendError('User not authenticated', 401);
                return;
            }
            
            $data = $this->getJsonInput();
            
            // Remove fields that shouldn't be updated via profile
            unset($data['role']);
            unset($data['status']);
            unset($data['password']);
            
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                $this->sendError('User not found', 404);
                return;
            }
            
            // Check if email is being changed and if it already exists
            if (!empty($data['email']) && $data['email'] !== $user['email']) {
                if ($this->userModel->getUserByEmail($data['email'])) {
                    $this->sendError('Email already exists', 400);
                    return;
                }
            }
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if ($this->userModel->updateUser($userId, $data)) {
                $updatedUser = $this->userModel->getUserById($userId);
                unset($updatedUser['password']);
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $updatedUser
                ]);
            } else {
                $this->sendError('Failed to update profile');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to update profile: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload user avatar
     */
    public function uploadAvatar() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->sendError('User not authenticated', 401);
                return;
            }
            
            if (!isset($_FILES['avatar'])) {
                $this->sendError('No file uploaded', 400);
                return;
            }
            
            $fileUpload = new FileUpload();
            $uploadResult = $fileUpload->uploadImage($_FILES['avatar'], 'profiles');
            
            if ($uploadResult['success']) {
                $avatarPath = $uploadResult['file_path'];
                
                if ($this->userModel->update($userId, ['avatar' => $avatarPath, 'updated_at' => date('Y-m-d H:i:s')])) {
                    $this->sendResponse([
                        'success' => true,
                        'message' => 'Avatar uploaded successfully',
                        'data' => ['avatar' => $avatarPath]
                    ]);
                } else {
                    $this->sendError('Failed to update avatar in database');
                }
            } else {
                $this->sendError($uploadResult['message']);
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to upload avatar: ' . $e->getMessage());
        }
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        return $this->jsonError($message, $code);
    }
    
    /**
     * Send success response
     */
    private function sendResponse($data, $message = 'Success', $code = 200) {
        return $this->jsonSuccess($data, $message, $code);
    }
}
?>