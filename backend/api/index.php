<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include required files
require_once __DIR__ . '/../auth/Auth.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/ComplaintController.php';
require_once __DIR__ . '/../controllers/EventController.php';
require_once __DIR__ . '/../controllers/ActivityController.php';
require_once __DIR__ . '/../controllers/GalleryController.php';
require_once __DIR__ . '/../controllers/MemberController.php';
require_once __DIR__ . '/../controllers/DonationController.php';

// Initialize authentication
$auth = new Auth();

// Check remember token if not logged in
if (!$auth->isLoggedIn()) {
    $auth->checkRememberToken();
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Clean the path - remove both /api and /backend/api prefixes
$path = str_replace(['/backend/api', '/api'], '', $path);
$pathParts = array_filter(explode('/', $path));

// Route the request
try {
    $response = routeRequest($method, $pathParts, $auth);
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}

/**
 * Route API requests
 */
function routeRequest($method, $pathParts, $auth) {
    if (empty($pathParts)) {
        return ['message' => 'BHRC API v1.0', 'status' => 'active'];
    }
    
    $resource = $pathParts[0];
    $id = isset($pathParts[1]) ? $pathParts[1] : null;
    $action = isset($pathParts[2]) ? $pathParts[2] : null;
    
    switch ($resource) {
        case 'auth':
            return handleAuthRoutes($method, $id, $action, $auth);
            
        case 'users':
            return handleUserRoutes($method, $id, $action, $auth);
            
        case 'complaints':
            return handleComplaintRoutes($method, $id, $action, $auth);
            
        case 'events':
            return handleEventRoutes($method, $id, $action, $auth);
            
        case 'activities':
            return handleActivityRoutes($method, $id, $action, $auth);
            
        case 'gallery':
            return handleGalleryRoutes($method, $id, $action, $auth);
            
        case 'members':
            return handleMemberRoutes($method, $id, $action, $auth);
            
        case 'donations':
            return handleDonationRoutes($method, $id, $action, $auth);
            
        case 'newsletter':
            return handleNewsletterRoutes($method, $id, $action, $auth);
            
        case 'statistics':
            return handleStatisticsRoutes($method, $id, $action, $auth);
            
        default:
            http_response_code(404);
            return ['success' => false, 'message' => 'Endpoint not found'];
    }
}

/**
 * Handle authentication routes
 */
function handleAuthRoutes($method, $id, $action, $auth) {
    $data = getRequestData();
    
    switch ($method) {
        case 'POST':
            switch ($id) {
                case 'register':
                    return $auth->register($data);
                    
                case 'login':
                    return $auth->login(
                        $data['identifier'] ?? '',
                        $data['password'] ?? '',
                        $data['remember_me'] ?? false
                    );
                    
                case 'logout':
                    return $auth->logout();
                    
                case 'send-otp':
                    return $auth->sendOTP(
                        $data['identifier'] ?? '',
                        $data['type'] ?? 'verification'
                    );
                    
                case 'verify-otp':
                    return $auth->verifyOTP(
                        $data['identifier'] ?? '',
                        $data['otp'] ?? '',
                        $data['type'] ?? 'verification'
                    );
                    
                case 'reset-password':
                    return $auth->resetPassword($data['password'] ?? '');
                    
                case 'change-password':
                    return $auth->changePassword(
                        $data['current_password'] ?? '',
                        $data['new_password'] ?? ''
                    );
                    
                default:
                    http_response_code(404);
                    return ['success' => false, 'message' => 'Auth endpoint not found'];
            }
            
        case 'GET':
            switch ($id) {
                case 'me':
                    $auth->requireAuth();
                    $user = $auth->getCurrentUser();
                    unset($user['password']);
                    return ['success' => true, 'user' => $user];
                    
                case 'permissions':
                    $auth->requireAuth();
                    $user = $auth->getCurrentUser();
                    return [
                        'success' => true,
                        'permissions' => $auth->getUserPermissions($user['role'])
                    ];
                    
                default:
                    http_response_code(404);
                    return ['success' => false, 'message' => 'Auth endpoint not found'];
            }
            
        default:
            http_response_code(405);
            return ['success' => false, 'message' => 'Method not allowed'];
    }
}

/**
 * Handle user routes
 */
function handleUserRoutes($method, $id, $action, $auth) {
    $controller = new UserController();
    $data = getRequestData();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                if ($action === 'profile') {
                    return $controller->getProfile($id);
                } else {
                    $auth->requireAnyRole(['admin', 'super_admin']);
                    return $controller->getById($id);
                }
            } else {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->getAll($_GET);
            }
            
        case 'POST':
            if ($id === 'profile') {
                $auth->requireAuth();
                return $controller->updateProfile($data);
            } else {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->create($data);
            }
            
        case 'PUT':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->update($id, $data);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->delete($id);
            }
            break;
    }
    
    http_response_code(405);
    return ['success' => false, 'message' => 'Method not allowed'];
}

/**
 * Handle complaint routes
 */
function handleComplaintRoutes($method, $id, $action, $auth) {
    $controller = new ComplaintController();
    $data = getRequestData();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                if ($action === 'status') {
                    return $controller->getStatus($id);
                } else {
                    $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                    return $controller->getById($id);
                }
            } else {
                if (isset($_GET['public'])) {
                    return $controller->getPublic($_GET);
                } else {
                    $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                    return $controller->getAll($_GET);
                }
            }
            
        case 'POST':
            if ($id === 'submit') {
                return $controller->submit($data);
            } else {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->create($data);
            }
            
        case 'PUT':
            if ($id && $action === 'status') {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->updateStatus($id, $data);
            } elseif ($id) {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->update($id, $data);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->delete($id);
            }
            break;
    }
    
    http_response_code(405);
    return ['success' => false, 'message' => 'Method not allowed'];
}

/**
 * Handle event routes
 */
function handleEventRoutes($method, $id, $action, $auth) {
    $controller = new EventController();
    $data = getRequestData();
    
    switch ($method) {
        case 'GET':
            if ($id === 'recent') {
                // Handle recent events endpoint
                $limit = $_GET['limit'] ?? 10;
                return $controller->getRecent($limit);
            } elseif ($id) {
                if ($action === 'register') {
                    return $controller->getRegistrations($id);
                } else {
                    return $controller->getById($id);
                }
            } else {
                return $controller->getAll($_GET);
            }
            
        case 'POST':
            if ($id === 'register') {
                return $controller->register($data);
            } elseif ($id) {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->update($id, $data);
            } else {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->create($data);
            }
            
        case 'PUT':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->update($id, $data);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->delete($id);
            }
            break;
    }
    
    http_response_code(405);
    return ['success' => false, 'message' => 'Method not allowed'];
}

/**
 * Handle activity routes
 */
function handleActivityRoutes($method, $id, $action, $auth) {
    $controller = new ActivityController();
    $data = getRequestData();
    
    switch ($method) {
        case 'GET':
            if ($id === 'statistics') {
                return $controller->getActivityStats();
            } elseif ($id) {
                return $controller->getById($id);
            } else {
                return $controller->getAll($_GET);
            }
            
        case 'POST':
            $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
            return $controller->create($data);
            
        case 'PUT':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->update($id, $data);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->delete($id);
            }
            break;
    }
    
    http_response_code(405);
    return ['success' => false, 'message' => 'Method not allowed'];
}

/**
 * Handle gallery routes
 */
function handleGalleryRoutes($method, $id, $action, $auth) {
    $controller = new GalleryController();
    $data = getRequestData();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                return $controller->getById($id);
            } else {
                return $controller->getAll($_GET);
            }
            
        case 'POST':
            if ($id === 'upload') {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->upload($_FILES, $data);
            } else {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->create($data);
            }
            
        case 'PUT':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin', 'moderator']);
                return $controller->update($id, $data);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->delete($id);
            }
            break;
    }
    
    http_response_code(405);
    return ['success' => false, 'message' => 'Method not allowed'];
}

/**
 * Handle member routes
 */
function handleMemberRoutes($method, $id, $action, $auth) {
    require_once __DIR__ . '/../services/EmailService.php';
    $db = new Database();
    $emailService = new EmailService();
    $controller = new MemberController($db->getConnection(), $emailService);
    $data = getRequestData();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                return $controller->getById($id);
            } else {
                return $controller->getAll($_GET);
            }
            
        case 'POST':
            if ($id === 'apply') {
                return $controller->apply($data);
            } elseif ($id === 'newsletter') {
                return $controller->subscribeNewsletter($data);
            } else {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->create($data);
            }
            
        case 'PUT':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->update($id, $data);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->delete($id);
            }
            break;
    }
    
    http_response_code(405);
    return ['success' => false, 'message' => 'Method not allowed'];
}

/**
 * Handle donation routes
 */
function handleDonationRoutes($method, $id, $action, $auth) {
    $controller = new DonationController();
    $data = getRequestData();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                if ($action === 'receipt') {
                    return $controller->getReceipt($id);
                } else {
                    $auth->requireAnyRole(['admin', 'super_admin']);
                    return $controller->getById($id);
                }
            } else {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->getAll($_GET);
            }
            
        case 'POST':
            if ($id === 'process') {
                return $controller->process($data);
            } elseif ($id === 'callback') {
                return $controller->paymentCallback($data);
            } else {
                return $controller->create($data);
            }
            
        case 'PUT':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->update($id, $data);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $auth->requireAnyRole(['admin', 'super_admin']);
                return $controller->delete($id);
            }
            break;
    }
    
    http_response_code(405);
    return ['success' => false, 'message' => 'Method not allowed'];
}

/**
 * Handle newsletter routes
 */
function handleNewsletterRoutes($method, $id, $action, $auth) {
    $data = getRequestData();
    
    switch ($method) {
        case 'POST':
            if ($id === 'subscribe') {
                // Handle newsletter subscription
                require_once __DIR__ . '/../models/Database.php';
                $db = new Database();
                $connection = $db->getConnection();
                
                try {
                    // Validate email
                    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Valid email is required");
                    }
                    
                    // Check if already subscribed
                    $stmt = $connection->prepare("
                        SELECT id FROM newsletter_subscribers WHERE email = ?
                    ");
                    $stmt->execute([$data['email']]);
                    
                    if ($stmt->fetchColumn()) {
                        return [
                            'success' => false,
                            'message' => 'Email already subscribed'
                        ];
                    }
                    
                    // Subscribe
                    $stmt = $connection->prepare("
                        INSERT INTO newsletter_subscribers (email, name, subscribed_at, status) 
                        VALUES (?, ?, NOW(), 'active')
                    ");
                    $stmt->execute([
                        $data['email'],
                        $data['name'] ?? ''
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Successfully subscribed to newsletter'
                    ];
                    
                } catch (Exception $e) {
                    return [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }
            break;
    }
    
    http_response_code(405);
    return ['success' => false, 'message' => 'Method not allowed'];
}

/**
 * Handle statistics routes
 */
function handleStatisticsRoutes($method, $id, $action, $auth) {
    require_once __DIR__ . '/../controllers/SystemController.php';
    $controller = new SystemController();
    
    switch ($method) {
        case 'GET':
            // Public statistics endpoint
            return $controller->getPublicStatistics();
            
        default:
            http_response_code(405);
            return ['success' => false, 'message' => 'Method not allowed'];
    }
}

/**
 * Get request data
 */
function getRequestData() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST;
    }
    
    return $data ?: [];
}

/**
 * Log API request
 */
function logApiRequest($method, $path, $response) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $method,
        'path' => $path,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'response_code' => http_response_code(),
        'success' => $response['success'] ?? false
    ];
    
    error_log('API Request: ' . json_encode($logData));
}

?>