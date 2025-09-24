<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/Database.php';

class Auth {
    private $db;
    private $user;
    
    public function __construct() {
        $this->db = new Database();
        $this->user = new User();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        try {
            // Validate required fields
            $required = ['name', 'email', 'phone', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            // Validate phone format (Indian mobile number)
            if (!preg_match('/^[6-9]\d{9}$/', $data['phone'])) {
                throw new Exception("Invalid phone number format");
            }
            
            // Validate password strength
            if (strlen($data['password']) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            
            // Check if user already exists
            if ($this->user->getByEmail($data['email'])) {
                throw new Exception("Email already registered");
            }
            
            if ($this->user->getByPhone($data['phone'])) {
                throw new Exception("Phone number already registered");
            }
            
            // Set default role
            $data['role'] = $data['role'] ?? 'user';
            $data['status'] = 'pending'; // Requires email verification
            
            // Create user
            $userId = $this->user->create($data);
            
            if ($userId) {
                // Send verification OTP
                $this->sendVerificationOTP($data['email'], $data['phone']);
                
                return [
                    'success' => true,
                    'message' => 'Registration successful. Please verify your email/phone.',
                    'user_id' => $userId
                ];
            } else {
                throw new Exception("Failed to create user");
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Login user
     */
    public function login($identifier, $password, $rememberMe = false) {
        try {
            // Get user by email or phone
            $user = $this->user->getByEmail($identifier) ?: $this->user->getByPhone($identifier);
            
            if (!$user) {
                throw new Exception("Invalid credentials");
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                throw new Exception("Invalid credentials");
            }
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                if ($user['status'] === 'pending') {
                    throw new Exception("Please verify your account first");
                } elseif ($user['status'] === 'suspended') {
                    throw new Exception("Your account has been suspended");
                } else {
                    throw new Exception("Account not active");
                }
            }
            
            // Update last login
            $this->user->updateLastLogin($user['id']);
            
            // Create session
            $this->createSession($user);
            
            // Handle remember me
            if ($rememberMe) {
                $this->createRememberToken($user['id']);
            }
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $this->sanitizeUser($user),
                'redirect' => $this->getRedirectUrl($user['role'])
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Clear remember token if exists
        if (isset($_COOKIE['remember_token'])) {
            $this->clearRememberToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }
    
    /**
     * Send OTP for verification
     */
    public function sendOTP($identifier, $type = 'verification') {
        try {
            // Get user
            $user = $this->user->getByEmail($identifier) ?: $this->user->getByPhone($identifier);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Generate OTP
            $otp = $this->generateOTP();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Store OTP
            $this->storeOTP($user['id'], $otp, $type, $expiresAt);
            
            // Send OTP via email/SMS
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $this->sendOTPEmail($identifier, $otp, $type);
            } else {
                $this->sendOTPSMS($identifier, $otp, $type);
            }
            
            return [
                'success' => true,
                'message' => 'OTP sent successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify OTP
     */
    public function verifyOTP($identifier, $otp, $type = 'verification') {
        try {
            // Get user
            $user = $this->user->getByEmail($identifier) ?: $this->user->getByPhone($identifier);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Verify OTP
            if (!$this->validateOTP($user['id'], $otp, $type)) {
                throw new Exception("Invalid or expired OTP");
            }
            
            // Mark OTP as used
            $this->markOTPUsed($user['id'], $otp, $type);
            
            // Handle different OTP types
            switch ($type) {
                case 'verification':
                    // Activate user account
                    $this->user->update($user['id'], [
                        'status' => 'active',
                        'email_verified' => true,
                        'phone_verified' => true,
                        'verified_at' => date('Y-m-d H:i:s')
                    ]);
                    break;
                    
                case 'password_reset':
                    // Allow password reset
                    $_SESSION['password_reset_user'] = $user['id'];
                    $_SESSION['password_reset_expires'] = time() + 600; // 10 minutes
                    break;
            }
            
            return [
                'success' => true,
                'message' => 'OTP verified successfully',
                'type' => $type
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Reset password
     */
    public function resetPassword($newPassword) {
        try {
            // Check if password reset is allowed
            if (!isset($_SESSION['password_reset_user']) || 
                !isset($_SESSION['password_reset_expires']) ||
                time() > $_SESSION['password_reset_expires']) {
                throw new Exception("Password reset session expired");
            }
            
            $userId = $_SESSION['password_reset_user'];
            
            // Validate password
            if (strlen($newPassword) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            
            // Update password
            $this->user->update($userId, [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);
            
            // Clear reset session
            unset($_SESSION['password_reset_user']);
            unset($_SESSION['password_reset_expires']);
            
            return [
                'success' => true,
                'message' => 'Password reset successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Change password (for logged in users)
     */
    public function changePassword($currentPassword, $newPassword) {
        try {
            if (!$this->isLoggedIn()) {
                throw new Exception("Please login first");
            }
            
            $user = $this->getCurrentUser();
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Validate new password
            if (strlen($newPassword) < 8) {
                throw new Exception("Password must be at least 8 characters long");
            }
            
            // Update password
            $this->user->update($user['id'], [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current logged in user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->user->getById($_SESSION['user_id']);
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole($roles) {
        $user = $this->getCurrentUser();
        return $user && in_array($user['role'], $roles);
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }
    }
    
    /**
     * Require any of the specified roles
     */
    public function requireAnyRole($roles) {
        $this->requireAuth();
        
        if (!$this->hasAnyRole($roles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }
    }
    
    /**
     * Create user session
     */
    private function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    /**
     * Generate OTP
     */
    private function generateOTP($length = 6) {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
    
    /**
     * Store OTP in database
     */
    private function storeOTP($userId, $otp, $type, $expiresAt) {
        $connection = $this->db->getConnection();
        
        // Delete existing OTPs for this user and type
        $stmt = $connection->prepare("
            DELETE FROM user_otps 
            WHERE user_id = ? AND type = ?
        ");
        $stmt->execute([$userId, $type]);
        
        // Insert new OTP
        $stmt = $connection->prepare("
            INSERT INTO user_otps (user_id, otp, type, expires_at, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $otp, $type, $expiresAt]);
    }
    
    /**
     * Validate OTP
     */
    private function validateOTP($userId, $otp, $type) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT id FROM user_otps 
            WHERE user_id = ? AND otp = ? AND type = ? 
            AND expires_at > NOW() AND used = 0
        ");
        $stmt->execute([$userId, $otp, $type]);
        
        return $stmt->fetchColumn() !== false;
    }
    
    /**
     * Mark OTP as used
     */
    private function markOTPUsed($userId, $otp, $type) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            UPDATE user_otps 
            SET used = 1, used_at = NOW() 
            WHERE user_id = ? AND otp = ? AND type = ?
        ");
        $stmt->execute([$userId, $otp, $type]);
    }
    
    /**
     * Send OTP via email
     */
    private function sendOTPEmail($email, $otp, $type) {
        $subject = "BHRC - Your OTP Code";
        
        $message = "
        <html>
        <head>
            <title>BHRC - OTP Verification</title>
        </head>
        <body>
            <h2>BHRC - Bharatiya Human Rights Council</h2>
            <p>Your OTP for {$type} is: <strong>{$otp}</strong></p>
            <p>This OTP will expire in 10 minutes.</p>
            <p>If you didn't request this, please ignore this email.</p>
            <br>
            <p>Best regards,<br>BHRC Team</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@bhrcindia.in" . "\r\n";
        
        return mail($email, $subject, $message, $headers);
    }
    
    /**
     * Send OTP via SMS (placeholder - integrate with SMS service)
     */
    private function sendOTPSMS($phone, $otp, $type) {
        // Integrate with SMS service provider (e.g., Twilio, MSG91, etc.)
        // For now, just log the OTP
        error_log("SMS OTP for {$phone}: {$otp} (Type: {$type})");
        return true;
    }
    
    /**
     * Send verification OTP
     */
    private function sendVerificationOTP($email, $phone) {
        $otp = $this->generateOTP();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Get user ID
        $user = $this->user->getByEmail($email);
        if ($user) {
            $this->storeOTP($user['id'], $otp, 'verification', $expiresAt);
            $this->sendOTPEmail($email, $otp, 'verification');
            $this->sendOTPSMS($phone, $otp, 'verification');
        }
    }
    
    /**
     * Create remember token
     */
    private function createRememberToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $connection = $this->db->getConnection();
        
        // Delete existing tokens for this user
        $stmt = $connection->prepare("
            DELETE FROM user_tokens 
            WHERE user_id = ? AND type = 'remember'
        ");
        $stmt->execute([$userId]);
        
        // Insert new token
        $stmt = $connection->prepare("
            INSERT INTO user_tokens (user_id, token, type, expires_at, created_at) 
            VALUES (?, ?, 'remember', ?, NOW())
        ");
        $stmt->execute([$userId, $token, $expiresAt]);
        
        // Set cookie
        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
    }
    
    /**
     * Clear remember token
     */
    private function clearRememberToken($token) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            DELETE FROM user_tokens 
            WHERE token = ? AND type = 'remember'
        ");
        $stmt->execute([$token]);
    }
    
    /**
     * Check remember token
     */
    public function checkRememberToken() {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT ut.user_id, u.* 
            FROM user_tokens ut
            JOIN users u ON ut.user_id = u.id
            WHERE ut.token = ? AND ut.type = 'remember' 
            AND ut.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$token]);
        
        $user = $stmt->fetch();
        
        if ($user) {
            $this->createSession($user);
            return true;
        } else {
            // Clear invalid token
            setcookie('remember_token', '', time() - 3600, '/');
            return false;
        }
    }
    
    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl($role) {
        switch ($role) {
            case 'admin':
            case 'super_admin':
                return '/admin/dashboard.html';
            case 'moderator':
                return '/admin/moderate.html';
            default:
                return '/dashboard.html';
        }
    }
    
    /**
     * Sanitize user data for frontend
     */
    private function sanitizeUser($user) {
        unset($user['password']);
        unset($user['otp']);
        return $user;
    }
    
    /**
     * Get user permissions based on role
     */
    public function getUserPermissions($role = null) {
        if (!$role) {
            $user = $this->getCurrentUser();
            $role = $user ? $user['role'] : 'guest';
        }
        
        $permissions = [
            'guest' => [],
            'user' => [
                'view_public_content',
                'submit_complaint',
                'register_event',
                'donate',
                'newsletter_subscribe'
            ],
            'member' => [
                'view_public_content',
                'submit_complaint',
                'register_event',
                'donate',
                'newsletter_subscribe',
                'view_member_content',
                'participate_activities'
            ],
            'moderator' => [
                'view_public_content',
                'submit_complaint',
                'register_event',
                'donate',
                'newsletter_subscribe',
                'view_member_content',
                'participate_activities',
                'moderate_complaints',
                'manage_events',
                'manage_gallery'
            ],
            'admin' => [
                'view_public_content',
                'submit_complaint',
                'register_event',
                'donate',
                'newsletter_subscribe',
                'view_member_content',
                'participate_activities',
                'moderate_complaints',
                'manage_events',
                'manage_gallery',
                'manage_users',
                'manage_members',
                'view_analytics',
                'manage_donations'
            ],
            'super_admin' => [
                'all_permissions'
            ]
        ];
        
        return $permissions[$role] ?? [];
    }
    
    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        $permissions = $this->getUserPermissions($user['role']);
        
        return in_array('all_permissions', $permissions) || in_array($permission, $permissions);
    }
}

?>