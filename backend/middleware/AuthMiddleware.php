<?php
/**
 * Authentication Middleware
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/SecurityMiddleware.php';
require_once __DIR__ . '/RateLimitMiddleware.php';
require_once __DIR__ . '/../config/auth.php';

class AuthMiddleware {
    
    /**
     * Check if user is authenticated
     */
    public static function authenticate($requireAuth = true) {
        global $PUBLIC_ROUTES;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Apply rate limiting for auth requests
        $currentRoute = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentRoute, '/api/auth/') === 0) {
            if (!RateLimitMiddleware::checkLimit('auth')) {
                return false;
            }
        }
        
        // Validate session security
        if (!SecurityMiddleware::validateSession()) {
            if ($requireAuth) {
                self::unauthorized('Session expired');
                return false;
            }
        }
        
        // Check if route is public
        if (self::isPublicRoute($currentRoute)) {
            return true;
        }
        
        // Check for authentication token
        $token = self::getAuthToken();
        
        if (!$token) {
            if ($requireAuth) {
                self::unauthorized('Authentication required');
                return false;
            }
            return true;
        }
        
        // Validate token
        $user = self::validateToken($token);
        
        if (!$user) {
            if ($requireAuth) {
                self::unauthorized('Invalid or expired token');
                return false;
            }
            return true;
        }
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            self::unauthorized('Account is not active');
            return false;
        }
        
        // Update last activity
        self::updateLastActivity($user['id']);
        
        // Set user in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['last_activity'] = time();
        
        return $user;
    }
    
    /**
     * Check if user has required role or permission
     */
    public static function authorize($requiredRoles = [], $permission = null) {
        $user = self::authenticate();
        
        if (!$user) {
            return false;
        }
        
        $userRole = $user['role'] ?? 'user';
        
        // Check specific permission if provided
        if ($permission && !hasPermission($userRole, $permission)) {
            self::forbidden('Insufficient permissions for this action');
            return false;
        }
        
        // Check role requirements
        if (empty($requiredRoles)) {
            return true;
        }
        
        if (is_string($requiredRoles)) {
            $requiredRoles = [$requiredRoles];
        }
        
        // Check if user has any of the required roles
        if (!in_array($userRole, $requiredRoles)) {
            // Check role hierarchy
            $hasPrivilege = false;
            foreach ($requiredRoles as $requiredRole) {
                if (hasRolePrivilege($userRole, $requiredRole)) {
                    $hasPrivilege = true;
                    break;
                }
            }
            
            if (!$hasPrivilege) {
                self::forbidden('Insufficient role privileges');
                return false;
            }
        }
        
        return true;
    }
        
        return true;
    }
    
    /**
     * Get authentication token from request
     */
    private static function getAuthToken() {
        // Check Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        // Check session
        if (isset($_SESSION['auth_token'])) {
            return $_SESSION['auth_token'];
        }
        
        // Check cookie
        if (isset($_COOKIE['auth_token'])) {
            return $_COOKIE['auth_token'];
        }
        
        // Check POST/GET parameter
        return $_POST['token'] ?? $_GET['token'] ?? null;
    }
    
    /**
     * Validate authentication token
     */
    private static function validateToken($token) {
        try {
            // Decode JWT token (simplified version)
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            $header = json_decode(base64_decode($parts[0]), true);
            $payload = json_decode(base64_decode($parts[1]), true);
            $signature = $parts[2];
            
            // Verify signature
            $expectedSignature = base64_encode(hash_hmac('sha256', $parts[0] . '.' . $parts[1], self::getJWTSecret(), true));
            
            if (!hash_equals($expectedSignature, $signature)) {
                return false;
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            // Get user from database
            $userModel = new User();
            $user = $userModel->find($payload['user_id']);
            
            if (!$user || $user['status'] !== 'active') {
                return false;
            }
            
            return $user;
            
        } catch (Exception $e) {
            error_log('Token validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate JWT token
     */
    public static function generateToken($userId, $expiresIn = 86400) { // 24 hours default
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload = json_encode([
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + $expiresIn,
            'iss' => $_SERVER['HTTP_HOST'] ?? 'bhrc.local'
        ]);
        
        $base64Header = base64_encode($header);
        $base64Payload = base64_encode($payload);
        
        $signature = base64_encode(hash_hmac('sha256', $base64Header . '.' . $base64Payload, self::getJWTSecret(), true));
        
        return $base64Header . '.' . $base64Payload . '.' . $signature;
    }
    
    /**
     * Get JWT secret key
     */
    private static function getJWTSecret() {
        return defined('JWT_SECRET') ? JWT_SECRET : 'bhrc_default_secret_key_change_in_production';
    }
    
    /**
     * Check if route is public
     */
    private static function isPublicRoute($route) {
        global $PUBLIC_ROUTES;
        
        foreach ($PUBLIC_ROUTES as $publicRoute) {
            if (strpos($route, $publicRoute) === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Login user and create session
     */
    public static function login($user, $rememberMe = false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Generate auth token
        $token = self::generateToken($user['id'], $rememberMe ? 2592000 : 86400); // 30 days or 24 hours
        $_SESSION['auth_token'] = $token;
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $cookieExpiry = time() + 2592000; // 30 days
            setcookie('auth_token', $token, $cookieExpiry, '/', '', true, true);
            setcookie('remember_user', $user['email'], $cookieExpiry, '/', '', true, true);
        }
        
        // Update user's last login
        $userModel = new User();
        $userModel->update($user['id'], [
            'last_login' => date('Y-m-d H:i:s'),
            'login_count' => ($user['login_count'] ?? 0) + 1
        ]);
        
        // Log successful login
        SecurityMiddleware::logSecurityEvent('user_login', [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'remember_me' => $rememberMe
        ]);
        
        return $token;
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Clear cookies
        if (isset($_COOKIE['auth_token'])) {
            setcookie('auth_token', '', time() - 3600, '/', '', true, true);
        }
        
        if (isset($_COOKIE['remember_user'])) {
            setcookie('remember_user', '', time() - 3600, '/', '', true, true);
        }
        
        // Log logout
        if ($userId) {
            SecurityMiddleware::logSecurityEvent('user_logout', [
                'user_id' => $userId
            ]);
        }
        
        return true;
    }
    
    /**
     * Get current authenticated user
     */
    public static function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $userModel = new User();
        return $userModel->find($_SESSION['user_id']);
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user has specific permission
     */
    public static function hasPermission($permission) {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        $rolePermissions = [
            'super_admin' => ['*'], // All permissions
            'admin' => [
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'members.view', 'members.create', 'members.edit', 'members.approve',
                'complaints.view', 'complaints.edit', 'complaints.respond',
                'donations.view', 'donations.edit',
                'events.view', 'events.create', 'events.edit', 'events.delete',
                'gallery.view', 'gallery.create', 'gallery.edit', 'gallery.delete',
                'settings.view', 'settings.edit'
            ],
            'moderator' => [
                'members.view', 'members.edit',
                'complaints.view', 'complaints.respond',
                'events.view', 'events.create', 'events.edit',
                'gallery.view', 'gallery.create', 'gallery.edit'
            ],
            'member' => [
                'profile.view', 'profile.edit',
                'complaints.create', 'complaints.view_own',
                'donations.create', 'donations.view_own',
                'events.view', 'gallery.view'
            ],
            'user' => [
                'profile.view', 'profile.edit',
                'complaints.create',
                'events.view', 'gallery.view'
            ]
        ];
        
        $userRole = $user['role'] ?? 'user';
        $permissions = $rolePermissions[$userRole] ?? [];
        
        // Super admin has all permissions
        if (in_array('*', $permissions)) {
            return true;
        }
        
        return in_array($permission, $permissions);
    }
    
    /**
     * Require specific permission
     */
    public static function requirePermission($permission) {
        if (!self::hasPermission($permission)) {
            self::forbidden('Permission denied: ' . $permission);
            return false;
        }
        return true;
    }
    
    /**
     * Check if user owns resource
     */
    public static function ownsResource($resourceUserId) {
        $currentUser = self::getCurrentUser();
        
        if (!$currentUser) {
            return false;
        }
        
        // Admin and super admin can access all resources
        if (in_array($currentUser['role'], ['admin', 'super_admin'])) {
            return true;
        }
        
        return $currentUser['id'] == $resourceUserId;
    }
    
    /**
     * Rate limiting for authentication attempts
     */
    public static function checkAuthRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) { // 15 minutes
        return SecurityMiddleware::checkRateLimit("auth_{$identifier}", $maxAttempts, $timeWindow);
    }
    
    /**
     * Send unauthorized response
     */
    private static function unauthorized($message = 'Unauthorized') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Unauthorized',
            'message' => $message,
            'code' => 401
        ]);
        exit;
    }
    
    /**
     * Send forbidden response
     */
    private static function forbidden($message = 'Forbidden') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Forbidden',
            'message' => $message,
            'code' => 403
        ]);
        exit;
    }
    
    /**
     * Validate API key (for external integrations)
     */
    public static function validateApiKey($apiKey = null) {
        $apiKey = $apiKey ?? ($_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? $_POST['api_key']);
        
        if (!$apiKey) {
            return false;
        }
        
        // In production, store API keys in database with proper hashing
        $validApiKeys = [
            'bhrc_api_key_2024' => ['name' => 'BHRC Mobile App', 'permissions' => ['read', 'write']],
            'bhrc_readonly_key' => ['name' => 'BHRC Website', 'permissions' => ['read']]
        ];
        
        return isset($validApiKeys[$apiKey]) ? $validApiKeys[$apiKey] : false;
    }
    
    /**
     * Two-factor authentication check
     */
    public static function requireTwoFactor($userId) {
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user || !$user['two_factor_enabled']) {
            return true; // 2FA not required
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if 2FA is already verified for this session
        if (isset($_SESSION['two_factor_verified']) && $_SESSION['two_factor_verified'] === true) {
            return true;
        }
        
        // Require 2FA verification
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'requires_2fa' => true,
            'message' => 'Two-factor authentication required'
        ]);
        exit;
    }
    
    /**
     * Update user's last activity timestamp
     */
    private static function updateLastActivity($userId) {
        try {
            $userModel = new User();
            $userModel->update($userId, [
                'last_activity' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log('Failed to update last activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if session has expired
     */
    public static function isSessionExpired() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $sessionLifetime = SESSION_LIFETIME;
        
        return (time() - $lastActivity) > $sessionLifetime;
    }
    
    /**
     * Refresh authentication token
     */
    public static function refreshToken($currentToken) {
        $user = self::validateToken($currentToken);
        
        if (!$user) {
            return false;
        }
        
        // Generate new token
        $newToken = self::generateToken($user['id'], JWT_EXPIRY);
        
        // Update session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['auth_token'] = $newToken;
        
        return $newToken;
    }
    
    /**
     * Send error response for unauthorized access
     */
    private static function unauthorized($message = 'Unauthorized') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => 401
        ]);
        exit;
    }
    
    /**
     * Send error response for forbidden access
     */
    private static function forbidden($message = 'Forbidden') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => 403
        ]);
        exit;
    }
}
    
    /**
     * Verify two-factor authentication code
     */
    public static function verifyTwoFactor($userId, $code) {
        // This would integrate with Google Authenticator or similar
        // For now, we'll use a simple time-based code
        
        $userModel = new User();
        $user = $userModel->find($userId);
        
        if (!$user || !$user['two_factor_enabled']) {
            return false;
        }
        
        // In production, use proper TOTP library
        $secret = $user['two_factor_secret'];
        $timeSlice = floor(time() / 30);
        $expectedCode = substr(hash_hmac('sha1', $timeSlice, $secret), -6);
        
        if ($code === $expectedCode) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['two_factor_verified'] = true;
            return true;
        }
        
        return false;
    }
}
?>