<?php
/**
 * Base Controller
 * BHRC - Bharatiya Human Rights Council
 */

abstract class BaseController {
    protected $request;
    protected $response;
    protected $session;
    
    public function __construct() {
        $this->request = $_REQUEST;
        $this->response = [];
        $this->session = $_SESSION ?? [];
        
        // Set security headers
        $this->setSecurityHeaders();
        
        // Initialize CSRF protection
        $this->initCSRF();
    }
    
    /**
     * Set security headers
     */
    protected function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:; font-src \'self\' https:');
    }
    
    /**
     * Initialize CSRF protection
     */
    protected function initCSRF() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCSRF($token = null) {
        $token = $token ?? ($this->request['csrf_token'] ?? '');
        
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $this->jsonError('Invalid CSRF token', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get CSRF token
     */
    protected function getCSRFToken() {
        return $_SESSION['csrf_token'] ?? '';
    }
    
    /**
     * Validate request method
     */
    protected function validateMethod($allowedMethods) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, (array)$allowedMethods)) {
            $this->jsonError('Method not allowed', 405);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get request input
     */
    protected function getInput($key = null, $default = null) {
        if ($key === null) {
            return $this->request;
        }
        
        return $this->request[$key] ?? $default;
    }
    
    /**
     * Get JSON input
     */
    protected function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    /**
     * Sanitize input
     */
    protected function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate input
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $ruleArray = explode('|', $rule);
            $value = $data[$field] ?? null;
            
            foreach ($ruleArray as $singleRule) {
                $ruleParts = explode(':', $singleRule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field] = ucfirst($field) . ' is required';
                        }
                        break;
                        
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = ucfirst($field) . ' must be a valid email';
                        }
                        break;
                        
                    case 'min':
                        if (!empty($value) && strlen($value) < $ruleValue) {
                            $errors[$field] = ucfirst($field) . " must be at least {$ruleValue} characters";
                        }
                        break;
                        
                    case 'max':
                        if (!empty($value) && strlen($value) > $ruleValue) {
                            $errors[$field] = ucfirst($field) . " must not exceed {$ruleValue} characters";
                        }
                        break;
                        
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field] = ucfirst($field) . ' must be numeric';
                        }
                        break;
                        
                    case 'in':
                        $allowedValues = explode(',', $ruleValue);
                        if (!empty($value) && !in_array($value, $allowedValues)) {
                            $errors[$field] = ucfirst($field) . ' must be one of: ' . implode(', ', $allowedValues);
                        }
                        break;
                        
                    case 'date':
                        if (!empty($value) && !strtotime($value)) {
                            $errors[$field] = ucfirst($field) . ' must be a valid date';
                        }
                        break;
                        
                    case 'phone':
                        if (!empty($value) && !preg_match('/^[0-9+\-\s()]+$/', $value)) {
                            $errors[$field] = ucfirst($field) . ' must be a valid phone number';
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Check authentication
     */
    protected function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->jsonError('Authentication required', 401);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user
     */
    protected function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Get current user ID
     */
    protected function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Check user role
     */
    protected function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    /**
     * Require specific role
     */
    protected function requireRole($role) {
        if (!$this->hasRole($role)) {
            $this->jsonError('Insufficient permissions', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    protected function hasAnyRole($roles) {
        $user = $this->getCurrentUser();
        return $user && in_array($user['role'], (array)$roles);
    }
    
    /**
     * Require any of the specified roles
     */
    protected function requireAnyRole($roles) {
        if (!$this->hasAnyRole($roles)) {
            $this->jsonError('Insufficient permissions', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Handle file upload
     */
    protected function handleFileUpload($fileKey, $allowedTypes = [], $maxSize = null, $uploadPath = null) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'No file uploaded or upload error'];
        }
        
        $file = $_FILES[$fileKey];
        $maxSize = $maxSize ?? (defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : 5 * 1024 * 1024); // 5MB default
        $uploadPath = $uploadPath ?? (defined('UPLOAD_PATH') ? UPLOAD_PATH : __DIR__ . '/../../uploads');
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds maximum allowed size'];
        }
        
        // Check file type
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!empty($allowedTypes) && !in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }
        
        // Generate unique filename
        $fileName = uniqid() . '_' . time() . '.' . $fileType;
        $filePath = $uploadPath . '/' . $fileName;
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return [
                'success' => true,
                'filename' => $fileName,
                'path' => $filePath,
                'size' => $file['size'],
                'type' => $fileType
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    /**
     * Send JSON response
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send JSON success response
     */
    protected function jsonSuccess($message = 'Success', $data = [], $statusCode = 200) {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Send JSON error response
     */
    protected function jsonError($message = 'Error', $statusCode = 400, $errors = []) {
        $this->jsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Render view
     */
    protected function render($view, $data = []) {
        extract($data);
        
        $viewFile = __DIR__ . "/../views/{$view}.php";
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("View file not found: {$view}");
        }
    }
    
    /**
     * Log activity
     */
    protected function logActivity($action, $details = null) {
        $userId = $this->getCurrentUserId();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Log to database or file
        error_log("Activity Log: " . json_encode($logData));
    }
    
    /**
     * Rate limiting
     */
    protected function checkRateLimit($key, $maxAttempts = 60, $timeWindow = 3600) {
        $cacheKey = "rate_limit_{$key}";
        
        // Simple file-based rate limiting (in production, use Redis or Memcached)
        $rateLimitFile = sys_get_temp_dir() . "/{$cacheKey}";
        
        if (file_exists($rateLimitFile)) {
            $data = json_decode(file_get_contents($rateLimitFile), true);
            
            if ($data && $data['timestamp'] > (time() - $timeWindow)) {
                if ($data['attempts'] >= $maxAttempts) {
                    $this->jsonError('Rate limit exceeded', 429);
                    return false;
                }
                
                $data['attempts']++;
            } else {
                $data = ['attempts' => 1, 'timestamp' => time()];
            }
        } else {
            $data = ['attempts' => 1, 'timestamp' => time()];
        }
        
        file_put_contents($rateLimitFile, json_encode($data));
        return true;
    }
}
?>