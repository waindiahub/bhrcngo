<?php
/**
 * Activity Controller
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../utils/FileUpload.php';

class ActivityController extends BaseController {
    private $activityModel;
    private $fileUpload;
    
    public function __construct() {
        parent::__construct();
        $this->activityModel = new Activity();
        $this->fileUpload = new FileUpload();
    }
    
    /**
     * Get all activities
     */
    public function getAll($params = []) {
        try {
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $category = isset($params['category']) ? $params['category'] : null;
            $status = isset($params['status']) ? $params['status'] : null;
            
            $filters = [];
            if ($category) $filters['category'] = $category;
            if ($status) $filters['status'] = $status;
            
            $result = $this->activityModel->getAll($page, $limit, $filters);
            
            return $this->sendResponse($result);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch activities: ' . $e->getMessage());
        }
    }
    
    /**
     * Get activity by ID
     */
    public function getById($id) {
        try {
            if (!$id) {
                return $this->sendError('Activity ID is required');
            }
            
            $activity = $this->activityModel->getById($id);
            
            if (!$activity) {
                return $this->sendError('Activity not found', 404);
            }
            
            return $this->sendResponse($activity);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new activity
     */
    public function create($data) {
        try {
            // Validate required fields
            $required = ['title', 'description', 'category', 'status'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->sendError("Field '$field' is required");
                }
            }
            
            // Handle file upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->fileUpload->uploadImage($_FILES['image'], 'activities');
                if ($uploadResult['success']) {
                    $data['image'] = $uploadResult['filename'];
                } else {
                    return $this->sendError('Image upload failed: ' . $uploadResult['message']);
                }
            }
            
            $activityId = $this->activityModel->create($data);
            
            if ($activityId) {
                $activity = $this->activityModel->getById($activityId);
                return $this->sendResponse($activity, 'Activity created successfully');
            } else {
                return $this->sendError('Failed to create activity');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to create activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Update activity
     */
    public function update($id, $data) {
        try {
            if (!$id) {
                return $this->sendError('Activity ID is required');
            }
            
            // Check if activity exists
            $existingActivity = $this->activityModel->getById($id);
            if (!$existingActivity) {
                return $this->sendError('Activity not found', 404);
            }
            
            // Handle file upload if present
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->fileUpload->uploadImage($_FILES['image'], 'activities');
                if ($uploadResult['success']) {
                    $data['image'] = $uploadResult['filename'];
                    // Delete old image if exists
                    if (!empty($existingActivity['image'])) {
                        $this->fileUpload->deleteFile($existingActivity['image']);
                    }
                } else {
                    return $this->sendError('Image upload failed: ' . $uploadResult['message']);
                }
            }
            
            $success = $this->activityModel->update($id, $data);
            
            if ($success) {
                $activity = $this->activityModel->getById($id);
                return $this->sendResponse($activity, 'Activity updated successfully');
            } else {
                return $this->sendError('Failed to update activity');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to update activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete activity
     */
    public function delete($id) {
        try {
            if (!$id) {
                return $this->sendError('Activity ID is required');
            }
            
            // Check if activity exists
            $activity = $this->activityModel->getById($id);
            if (!$activity) {
                return $this->sendError('Activity not found', 404);
            }
            
            // Delete associated image if exists
            if (!empty($activity['image'])) {
                $this->fileUpload->deleteFile($activity['image']);
            }
            
            $success = $this->activityModel->delete($id);
            
            if ($success) {
                return $this->sendResponse(null, 'Activity deleted successfully');
            } else {
                return $this->sendError('Failed to delete activity');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to delete activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload activity image
     */
    public function upload() {
        try {
            if (!isset($_FILES['image'])) {
                return $this->sendError('No image file provided');
            }
            
            $uploadResult = $this->fileUpload->uploadImage($_FILES['image'], 'activities');
            
            if ($uploadResult['success']) {
                return $this->sendResponse([
                    'filename' => $uploadResult['filename'],
                    'url' => $uploadResult['url']
                ], 'Image uploaded successfully');
            } else {
                return $this->sendError('Image upload failed: ' . $uploadResult['message']);
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to upload image: ' . $e->getMessage());
        }
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats() {
        try {
            $stats = $this->activityModel->getStats();
            
            return $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch activity statistics: ' . $e->getMessage());
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