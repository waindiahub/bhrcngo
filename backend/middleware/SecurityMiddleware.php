<?php
/**
 * Security Middleware
 * BHRC - Bharatiya Human Rights Council
 */

class SecurityMiddleware {
    
    /**
     * Apply security headers
     */
    public static function applySecurityHeaders() {
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
               "img-src 'self' data: https: blob:; " .
               "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
               "connect-src 'self'; " .
               "media-src 'self'; " .
               "object-src 'none'; " .
               "base-uri 'self'; " .
               "form-action 'self'";
        
        header("Content-Security-Policy: {$csp}");
        
        // HSTS (HTTP Strict Transport Security)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Remove server information
        header_remove('X-Powered-By');
        header_remove('Server');
    }
    
    /**
     * Validate and sanitize input
     */
    public static function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
                
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
                
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'phone':
                return preg_replace('/[^0-9+\-\s()]/', '', trim($input));
                
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9]/', '', trim($input));
                
            case 'filename':
                return preg_replace('/[^a-zA-Z0-9._-]/', '', trim($input));
                
            case 'html':
                return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
            case 'sql':
                return addslashes(trim($input));
                
            default: // string
                return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRF($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $requestToken = $token ?? ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
        
        if (empty($sessionToken) || empty($requestToken)) {
            return false;
        }
        
        return hash_equals($sessionToken, $requestToken);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Rate limiting
     */
    public static function checkRateLimit($identifier, $maxAttempts = 60, $timeWindow = 3600) {
        $key = "rate_limit_" . md5($identifier);
        $file = sys_get_temp_dir() . "/{$key}";
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            
            if ($data && $data['timestamp'] > (time() - $timeWindow)) {
                if ($data['attempts'] >= $maxAttempts) {
                    http_response_code(429);
                    header('Retry-After: ' . ($timeWindow - (time() - $data['timestamp'])));
                    echo json_encode(['error' => 'Rate limit exceeded']);
                    exit;
                }
                
                $data['attempts']++;
            } else {
                $data = ['attempts' => 1, 'timestamp' => time()];
            }
        } else {
            $data = ['attempts' => 1, 'timestamp' => time()];
        }
        
        file_put_contents($file, json_encode($data));
        return true;
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = null) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded or invalid upload';
            return $errors;
        }
        
        // Check file size
        $maxSize = $maxSize ?? (defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : 5 * 1024 * 1024); // 5MB default
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size (' . self::formatBytes($maxSize) . ')';
        }
        
        // Check file type
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = mime_content_type($file['tmp_name']);
        
        if (!empty($allowedTypes)) {
            $allowedExtensions = array_map('strtolower', $allowedTypes);
            if (!in_array($fileType, $allowedExtensions)) {
                $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes);
            }
        }
        
        // Additional MIME type validation for images
        if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedMimes)) {
                $errors[] = 'Invalid image file';
            }
        }
        
        // Check for malicious files
        if (self::isMaliciousFile($file)) {
            $errors[] = 'File appears to be malicious';
        }
        
        return $errors;
    }
    
    /**
     * Check for malicious files
     */
    private static function isMaliciousFile($file) {
        // Check file content for PHP tags
        $content = file_get_contents($file['tmp_name']);
        
        // Look for PHP opening tags
        if (strpos($content, '<?php') !== false || 
            strpos($content, '<?=') !== false || 
            strpos($content, '<script') !== false) {
            return true;
        }
        
        // Check for executable file extensions
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'exe', 'bat', 'cmd', 'sh', 'js', 'jar'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($fileExtension, $dangerousExtensions)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Check against common passwords
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123', 
            'password123', 'admin', 'letmein', 'welcome', 'monkey'
        ];
        
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = 'Password is too common';
        }
        
        return $errors;
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Additional checks
        $domain = substr(strrchr($email, "@"), 1);
        
        // Check if domain exists
        if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate phone number
     */
    public static function validatePhone($phone) {
        // Remove all non-numeric characters except +
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Indian phone number validation
        if (preg_match('/^(\+91|91|0)?[6-9]\d{9}$/', $cleanPhone)) {
            return true;
        }
        
        // International format
        if (preg_match('/^\+[1-9]\d{1,14}$/', $cleanPhone)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate Aadhar number
     */
    public static function validateAadhar($aadhar) {
        // Remove spaces and hyphens
        $cleanAadhar = preg_replace('/[\s-]/', '', $aadhar);
        
        // Check if it's 12 digits
        if (!preg_match('/^\d{12}$/', $cleanAadhar)) {
            return false;
        }
        
        // Verhoeff algorithm for Aadhar validation
        return self::verhoeffCheck($cleanAadhar);
    }
    
    /**
     * Verhoeff algorithm for Aadhar validation
     */
    private static function verhoeffCheck($num) {
        $d = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0]
        ];
        
        $p = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8]
        ];
        
        $c = 0;
        $myArray = str_split(strrev($num));
        
        for ($i = 0; $i < count($myArray); $i++) {
            $c = $d[$c][$p[($i % 8)][$myArray[$i]]];
        }
        
        return $c == 0;
    }
    
    /**
     * Validate PAN number
     */
    public static function validatePAN($pan) {
        $pan = strtoupper(trim($pan));
        return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan);
    }
    
    /**
     * Validate Indian pincode
     */
    public static function validatePincode($pincode) {
        return preg_match('/^[1-9][0-9]{5}$/', $pincode);
    }
    
    /**
     * SQL Injection prevention
     */
    public static function preventSQLInjection($input) {
        // List of dangerous SQL keywords
        $sqlKeywords = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
            'EXEC', 'EXECUTE', 'UNION', 'SCRIPT', 'JAVASCRIPT', 'VBSCRIPT',
            'ONLOAD', 'ONERROR', 'ONCLICK', 'ONMOUSEOVER', 'ONFOCUS', 'ONBLUR'
        ];
        
        $input = trim($input);
        
        foreach ($sqlKeywords as $keyword) {
            if (stripos($input, $keyword) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * XSS prevention
     */
    public static function preventXSS($input) {
        // Remove dangerous HTML tags and attributes
        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/on\w+\s*=/i', '', $input);
        
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/../../logs/security.log';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Format bytes to human readable format
     */
    private static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Check if request is from allowed origin
     */
    public static function validateOrigin($allowedOrigins = []) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (empty($allowedOrigins)) {
            return true; // Allow all if no restrictions
        }
        
        return in_array($origin, $allowedOrigins);
    }
    
    /**
     * Validate session security
     */
    public static function validateSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $sessionTimeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600; // 1 hour default
            
            if (time() - $_SESSION['last_activity'] > $sessionTimeout) {
                session_destroy();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
        
        return true;
    }
}
?>