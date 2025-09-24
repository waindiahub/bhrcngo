<?php
/**
 * API Routes
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/MemberController.php';
require_once __DIR__ . '/../controllers/ComplaintController.php';
require_once __DIR__ . '/../controllers/DonationController.php';
require_once __DIR__ . '/../controllers/EventController.php';
require_once __DIR__ . '/../controllers/GalleryController.php';
require_once __DIR__ . '/../controllers/ContactController.php';
require_once __DIR__ . '/../controllers/NewsletterController.php';

class ApiRouter {
    
    private $routes = [];
    private $middleware = [];
    
    public function __construct() {
        // Apply security headers to all requests
        SecurityMiddleware::applySecurityHeaders();
        
        // Initialize routes
        $this->initializeRoutes();
    }
    
    /**
     * Initialize all API routes
     */
    private function initializeRoutes() {
        
        // Public routes (no authentication required)
        $this->addRoute('GET', '/api/public/events', 'EventController@getPublicEvents');
        $this->addRoute('GET', '/api/public/activities', 'EventController@getPublicActivities');
        $this->addRoute('GET', '/api/public/gallery', 'GalleryController@getPublicGallery');
        $this->addRoute('POST', '/api/contact/submit', 'ContactController@submit');
        $this->addRoute('POST', '/api/newsletter/subscribe', 'NewsletterController@subscribe');
        
        // Public statistics and events routes
        $this->addRoute('GET', '/api/statistics', 'SystemController@getPublicStatistics');
        $this->addRoute('GET', '/api/events/recent', 'EventController@getRecent');
        
        // Authentication routes
        $this->addRoute('POST', '/api/auth/login', 'AuthController@login');
        $this->addRoute('POST', '/api/auth/register', 'AuthController@register');
        $this->addRoute('POST', '/api/auth/logout', 'AuthController@logout');
        $this->addRoute('POST', '/api/auth/forgot-password', 'AuthController@forgotPassword');
        $this->addRoute('POST', '/api/auth/reset-password', 'AuthController@resetPassword');
        $this->addRoute('POST', '/api/auth/change-password', 'AuthController@changePassword', ['auth']);
        $this->addRoute('POST', '/api/auth/verify-email', 'AuthController@verifyEmail');
        $this->addRoute('POST', '/api/auth/resend-verification', 'AuthController@resendVerification');
        $this->addRoute('GET', '/api/auth/user', 'AuthController@getUser', ['auth']);
        
        // User management routes
        $this->addRoute('GET', '/api/users', 'UserController@index', ['auth', 'permission:users.view']);
        $this->addRoute('GET', '/api/users/{id}', 'UserController@show', ['auth', 'permission:users.view']);
        $this->addRoute('POST', '/api/users', 'UserController@create', ['auth', 'permission:users.create']);
        $this->addRoute('PUT', '/api/users/{id}', 'UserController@update', ['auth', 'permission:users.edit']);
        $this->addRoute('DELETE', '/api/users/{id}', 'UserController@delete', ['auth', 'permission:users.delete']);
        $this->addRoute('POST', '/api/users/{id}/activate', 'UserController@activate', ['auth', 'permission:users.edit']);
        $this->addRoute('POST', '/api/users/{id}/deactivate', 'UserController@deactivate', ['auth', 'permission:users.edit']);
        
        // Profile routes
        $this->addRoute('GET', '/api/profile', 'UserController@getProfile', ['auth']);
        $this->addRoute('PUT', '/api/profile', 'UserController@updateProfile', ['auth']);
        $this->addRoute('POST', '/api/profile/avatar', 'UserController@uploadAvatar', ['auth']);
        
        // Member management routes
        $this->addRoute('GET', '/api/members', 'MemberController@index', ['auth', 'permission:members.view']);
        $this->addRoute('GET', '/api/members/{id}', 'MemberController@show', ['auth', 'permission:members.view']);
        $this->addRoute('POST', '/api/members', 'MemberController@create', ['auth']);
        $this->addRoute('PUT', '/api/members/{id}', 'MemberController@update', ['auth', 'permission:members.edit']);
        $this->addRoute('POST', '/api/members/{id}/approve', 'MemberController@approve', ['auth', 'permission:members.approve']);
        $this->addRoute('POST', '/api/members/{id}/reject', 'MemberController@reject', ['auth', 'permission:members.approve']);
        $this->addRoute('POST', '/api/members/{id}/certificate', 'MemberController@generateCertificate', ['auth', 'permission:members.approve']);
        $this->addRoute('GET', '/api/members/search', 'MemberController@search', ['auth', 'permission:members.view']);
        $this->addRoute('GET', '/api/members/stats', 'MemberController@getStats', ['auth', 'permission:members.view']);
        
        // Complaint management routes
        $this->addRoute('GET', '/api/complaints', 'ComplaintController@index', ['auth']);
        $this->addRoute('GET', '/api/complaints/{id}', 'ComplaintController@show', ['auth']);
        $this->addRoute('POST', '/api/complaints', 'ComplaintController@create', ['auth']);
        $this->addRoute('PUT', '/api/complaints/{id}', 'ComplaintController@update', ['auth']);
        $this->addRoute('POST', '/api/complaints/{id}/respond', 'ComplaintController@respond', ['auth', 'permission:complaints.respond']);
        $this->addRoute('POST', '/api/complaints/{id}/status', 'ComplaintController@updateStatus', ['auth', 'permission:complaints.edit']);
        $this->addRoute('POST', '/api/complaints/{id}/documents', 'ComplaintController@uploadDocument', ['auth']);
        $this->addRoute('GET', '/api/complaints/stats', 'ComplaintController@getStats', ['auth', 'permission:complaints.view']);
        
        // Donation routes
        $this->addRoute('GET', '/api/donations', 'DonationController@index', ['auth']);
        $this->addRoute('GET', '/api/donations/{id}', 'DonationController@show', ['auth']);
        $this->addRoute('POST', '/api/donations', 'DonationController@create', ['auth']);
        $this->addRoute('POST', '/api/donations/{id}/verify', 'DonationController@verify', ['auth', 'permission:donations.edit']);
        $this->addRoute('GET', '/api/donations/stats', 'DonationController@getStats', ['auth', 'permission:donations.view']);
        
        // Event management routes
        $this->addRoute('GET', '/api/events', 'EventController@index', ['auth']);
        $this->addRoute('GET', '/api/events/{id}', 'EventController@show', ['auth']);
        $this->addRoute('POST', '/api/events', 'EventController@create', ['auth', 'permission:events.create']);
        $this->addRoute('PUT', '/api/events/{id}', 'EventController@update', ['auth', 'permission:events.edit']);
        $this->addRoute('DELETE', '/api/events/{id}', 'EventController@delete', ['auth', 'permission:events.delete']);
        $this->addRoute('POST', '/api/events/{id}/register', 'EventController@register', ['auth']);
        $this->addRoute('POST', '/api/events/{id}/unregister', 'EventController@unregister', ['auth']);
        $this->addRoute('GET', '/api/events/{id}/participants', 'EventController@getParticipants', ['auth', 'permission:events.view']);
        
        // Gallery routes
        $this->addRoute('GET', '/api/gallery/albums', 'GalleryController@getAlbums', ['auth']);
        $this->addRoute('GET', '/api/gallery/albums/{id}', 'GalleryController@getAlbum', ['auth']);
        $this->addRoute('POST', '/api/gallery/albums', 'GalleryController@createAlbum', ['auth', 'permission:gallery.create']);
        $this->addRoute('PUT', '/api/gallery/albums/{id}', 'GalleryController@updateAlbum', ['auth', 'permission:gallery.edit']);
        $this->addRoute('DELETE', '/api/gallery/albums/{id}', 'GalleryController@deleteAlbum', ['auth', 'permission:gallery.delete']);
        $this->addRoute('POST', '/api/gallery/photos', 'GalleryController@uploadPhoto', ['auth', 'permission:gallery.create']);
        $this->addRoute('POST', '/api/gallery/videos', 'GalleryController@uploadVideo', ['auth', 'permission:gallery.create']);
        $this->addRoute('DELETE', '/api/gallery/photos/{id}', 'GalleryController@deletePhoto', ['auth', 'permission:gallery.delete']);
        $this->addRoute('DELETE', '/api/gallery/videos/{id}', 'GalleryController@deleteVideo', ['auth', 'permission:gallery.delete']);
        
        // Newsletter routes
        $this->addRoute('GET', '/api/newsletter/subscribers', 'NewsletterController@getSubscribers', ['auth', 'permission:newsletter.view']);
        $this->addRoute('POST', '/api/newsletter/unsubscribe', 'NewsletterController@unsubscribe');
        $this->addRoute('POST', '/api/newsletter/send', 'NewsletterController@sendNewsletter', ['auth', 'permission:newsletter.send']);
        
        // Contact routes
        $this->addRoute('GET', '/api/contact/inquiries', 'ContactController@getInquiries', ['auth', 'permission:contact.view']);
        $this->addRoute('GET', '/api/contact/inquiries/{id}', 'ContactController@getInquiry', ['auth', 'permission:contact.view']);
        $this->addRoute('POST', '/api/contact/inquiries/{id}/respond', 'ContactController@respond', ['auth', 'permission:contact.respond']);
        
        // System routes
        $this->addRoute('GET', '/api/system/stats', 'SystemController@getStats', ['auth', 'permission:system.view']);
        $this->addRoute('GET', '/api/system/logs', 'SystemController@getLogs', ['auth', 'permission:system.view']);
        $this->addRoute('POST', '/api/system/backup', 'SystemController@createBackup', ['auth', 'permission:system.admin']);
        
        // File upload routes
        $this->addRoute('POST', '/api/upload/image', 'FileController@uploadImage', ['auth']);
        $this->addRoute('POST', '/api/upload/document', 'FileController@uploadDocument', ['auth']);
        $this->addRoute('DELETE', '/api/upload/{id}', 'FileController@deleteFile', ['auth']);
        
        // OTP routes
        $this->addRoute('POST', '/api/otp/send', 'OTPController@send', ['auth']);
        $this->addRoute('POST', '/api/otp/verify', 'OTPController@verify', ['auth']);
        
        // Search routes
        $this->addRoute('GET', '/api/search', 'SearchController@search', ['auth']);
        $this->addRoute('GET', '/api/search/members', 'SearchController@searchMembers', ['auth', 'permission:members.view']);
        $this->addRoute('GET', '/api/search/complaints', 'SearchController@searchComplaints', ['auth']);
        
        // Notification routes
        $this->addRoute('GET', '/api/notifications', 'NotificationController@index', ['auth']);
        $this->addRoute('POST', '/api/notifications/{id}/read', 'NotificationController@markAsRead', ['auth']);
        $this->addRoute('POST', '/api/notifications/read-all', 'NotificationController@markAllAsRead', ['auth']);
    }
    
    /**
     * Add a route
     */
    private function addRoute($method, $path, $handler, $middleware = []) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }
    
    /**
     * Handle incoming request
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash
        $path = rtrim($path, '/');
        
        // Find matching route
        $matchedRoute = null;
        $params = [];
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $routePattern = $this->convertRouteToRegex($route['path']);
            
            if (preg_match($routePattern, $path, $matches)) {
                $matchedRoute = $route;
                
                // Extract parameters
                $paramNames = $this->extractParamNames($route['path']);
                for ($i = 1; $i < count($matches); $i++) {
                    if (isset($paramNames[$i - 1])) {
                        $params[$paramNames[$i - 1]] = $matches[$i];
                    }
                }
                break;
            }
        }
        
        if (!$matchedRoute) {
            $this->sendNotFound();
            return;
        }
        
        // Apply middleware
        if (!$this->applyMiddleware($matchedRoute['middleware'], $params)) {
            return;
        }
        
        // Call controller method
        $this->callController($matchedRoute['handler'], $params);
    }
    
    /**
     * Convert route path to regex pattern
     */
    private function convertRouteToRegex($path) {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Extract parameter names from route path
     */
    private function extractParamNames($path) {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        return $matches[1];
    }
    
    /**
     * Apply middleware to request
     */
    private function applyMiddleware($middleware, $params) {
        foreach ($middleware as $middlewareName) {
            if ($middlewareName === 'auth') {
                if (!AuthMiddleware::authenticate()) {
                    return false;
                }
            } elseif (strpos($middlewareName, 'permission:') === 0) {
                $permission = substr($middlewareName, 11);
                if (!AuthMiddleware::requirePermission($permission)) {
                    return false;
                }
            } elseif (strpos($middlewareName, 'role:') === 0) {
                $role = substr($middlewareName, 5);
                if (!AuthMiddleware::authorize([$role])) {
                    return false;
                }
            } elseif ($middlewareName === 'csrf') {
                if (!SecurityMiddleware::validateCSRF()) {
                    $this->sendError('Invalid CSRF token', 403);
                    return false;
                }
            } elseif ($middlewareName === 'rate_limit') {
                $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                if (!SecurityMiddleware::checkRateLimit($identifier)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Call controller method
     */
    private function callController($handler, $params) {
        list($controllerName, $methodName) = explode('@', $handler);
        
        if (!class_exists($controllerName)) {
            $this->sendError('Controller not found', 500);
            return;
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $methodName)) {
            $this->sendError('Method not found', 500);
            return;
        }
        
        try {
            // Pass parameters to controller method
            $result = call_user_func_array([$controller, $methodName], $params);
            
            if ($result !== null) {
                $this->sendResponse($result);
            }
            
        } catch (Exception $e) {
            error_log('Controller error: ' . $e->getMessage());
            $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => $statusCode
        ]);
    }
    
    /**
     * Send 404 response
     */
    private function sendNotFound() {
        $this->sendError('Route not found', 404);
    }
    
    /**
     * Get all routes (for documentation)
     */
    public function getRoutes() {
        return $this->routes;
    }
}

// Handle the request if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) === 'api.php') {
    $router = new ApiRouter();
    $router->handleRequest();
}
?>