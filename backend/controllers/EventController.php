<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../utils/FileUpload.php';
require_once __DIR__ . '/../services/EmailService.php';

class EventController {
    private $db;
    private $fileUpload;
    private $emailService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->fileUpload = new FileUpload();
        $this->emailService = new EmailService();
    }
    
    /**
     * Get recent events
     */
    public function getRecent($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT e.*, 
                       COUNT(er.id) as registration_count,
                       CASE 
                           WHEN e.event_date > NOW() THEN 'upcoming'
                           WHEN DATE(e.event_date) = CURDATE() THEN 'ongoing'
                           ELSE 'past'
                       END as status
                FROM events e 
                LEFT JOIN event_registrations er ON e.id = er.event_id 
                WHERE e.is_active = 1
                GROUP BY e.id
                ORDER BY e.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendResponse([
                'success' => true,
                'data' => $events,
                'count' => count($events)
            ]);
            
        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Failed to fetch recent events: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all events
     */
    public function getAll($params = []) {
        return $this->getEvents();
    }
    
    /**
     * Get event by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT e.*, 
                       COUNT(er.id) as registration_count,
                       CASE 
                           WHEN e.event_date > NOW() THEN 'upcoming'
                           WHEN DATE(e.event_date) = CURDATE() THEN 'ongoing'
                           ELSE 'past'
                       END as status
                FROM events e 
                LEFT JOIN event_registrations er ON e.id = er.event_id 
                WHERE e.id = ? AND e.is_active = 1
                GROUP BY e.id
            ");
            $stmt->execute([$id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$event) {
                return $this->sendResponse(['error' => 'Event not found'], 404);
            }
            
            return $this->sendResponse([
                'success' => true,
                'data' => $event
            ]);
        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Failed to fetch event: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get event registrations
     */
    public function getRegistrations($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT er.*, u.name, u.email, u.phone 
                FROM event_registrations er
                LEFT JOIN users u ON er.user_id = u.id
                WHERE er.event_id = ?
                ORDER BY er.created_at DESC
            ");
            $stmt->execute([$id]);
            $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->sendResponse([
                'success' => true,
                'data' => $registrations
            ]);
        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Failed to fetch registrations: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Register for event
     */
    public function register($data) {
        try {
            $eventId = $data['event_id'] ?? null;
            $userId = $data['user_id'] ?? null;
            
            if (!$eventId || !$userId) {
                return $this->sendResponse(['error' => 'Event ID and User ID are required'], 400);
            }
            
            // Check if already registered
            $stmt = $this->db->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
            $stmt->execute([$eventId, $userId]);
            if ($stmt->fetch()) {
                return $this->sendResponse(['error' => 'Already registered for this event'], 400);
            }
            
            // Register user
            $stmt = $this->db->prepare("
                INSERT INTO event_registrations (event_id, user_id, registration_date, status) 
                VALUES (?, ?, NOW(), 'confirmed')
            ");
            $stmt->execute([$eventId, $userId]);
            
            return $this->sendResponse([
                'success' => true,
                'message' => 'Successfully registered for event'
            ]);
        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Failed to register: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Create event
     */
    public function create($data) {
        return $this->createEvent();
    }
    
    /**
     * Update event
     */
    public function update($id, $data) {
        return $this->updateEvent($id);
    }
    
    /**
     * Delete event
     */
    public function delete($id) {
        return $this->deleteEvent($id);
    }
    
    /**
     * Helper method to send JSON response
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Get all events with optional filtering
     */
    public function getEvents() {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $type = isset($_GET['type']) ? $_GET['type'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            $search = isset($_GET['search']) ? $_GET['search'] : null;
            
            $offset = ($page - 1) * $limit;
            
            // Build query
            $query = "SELECT e.*, 
                             COUNT(er.id) as registration_count,
                             CASE 
                                 WHEN e.event_date > NOW() THEN 'upcoming'
                                 WHEN DATE(e.event_date) = CURDATE() THEN 'ongoing'
                                 ELSE 'past'
                             END as status
                      FROM events e 
                      LEFT JOIN event_registrations er ON e.id = er.event_id 
                      WHERE e.is_active = 1";
            
            $params = [];
            
            // Add filters
            if ($type) {
                $query .= " AND e.type = ?";
                $params[] = $type;
            }
            
            if ($search) {
                $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $query .= " GROUP BY e.id";
            
            // Add status filter after GROUP BY
            if ($status) {
                if ($status === 'upcoming') {
                    $query .= " HAVING status = 'upcoming'";
                } elseif ($status === 'past') {
                    $query .= " HAVING status = 'past'";
                } elseif ($status === 'ongoing') {
                    $query .= " HAVING status = 'ongoing'";
                }
            }
            
            $query .= " ORDER BY e.event_date ASC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $events = $this->db->fetchAll($query, $params);
            
            // Get total count for pagination
            $countQuery = "SELECT COUNT(DISTINCT e.id) as total 
                          FROM events e 
                          WHERE e.is_active = 1";
            
            $countParams = [];
            if ($type) {
                $countQuery .= " AND e.type = ?";
                $countParams[] = $type;
            }
            
            if ($search) {
                $countQuery .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
                $searchTerm = "%{$search}%";
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
            }
            
            $totalResult = $this->db->fetch($countQuery, $countParams);
            $total = $totalResult['total'];
            
            // Format events data
            foreach ($events as &$event) {
                $event['image'] = $event['image'] ? '/uploads/events/' . $event['image'] : null;
                $event['can_register'] = $event['status'] === 'upcoming' && 
                                        ($event['max_participants'] == 0 || $event['registration_count'] < $event['max_participants']);
            }
            
            $this->sendResponse([
                'success' => true,
                'events' => $events,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'items_per_page' => $limit
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'message' => 'Failed to fetch events: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single event by ID
     */
    public function getEvent($id) {
        try {
            $query = "SELECT e.*, 
                             COUNT(er.id) as registration_count,
                             CASE 
                                 WHEN e.event_date > NOW() THEN 'upcoming'
                                 WHEN DATE(e.event_date) = CURDATE() THEN 'ongoing'
                                 ELSE 'past'
                             END as status
                      FROM events e 
                      LEFT JOIN event_registrations er ON e.id = er.event_id 
                      WHERE e.id = ? AND e.is_active = 1
                      GROUP BY e.id";
            
            $event = $this->db->fetch($query, [$id]);
            
            if (!$event) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Event not found'
                ], 404);
                return;
            }
            
            $event['image'] = $event['image'] ? '/uploads/events/' . $event['image'] : null;
            $event['can_register'] = $event['status'] === 'upcoming' && 
                                    ($event['max_participants'] == 0 || $event['registration_count'] < $event['max_participants']);
            
            $this->sendResponse([
                'success' => true,
                'event' => $event
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'message' => 'Failed to fetch event: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create new event (Admin only)
     */
    public function createEvent() {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
                return;
            }
            
            $data = $this->getRequestData();
            
            // Validate required fields
            $required = ['title', 'description', 'event_date', 'location', 'type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->sendResponse([
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ], 400);
                    return;
                }
            }
            
            // Handle file upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->fileUpload->uploadImage($_FILES['image'], 'events');
                if ($uploadResult['success']) {
                    $imagePath = $uploadResult['filename'];
                } else {
                    $this->sendResponse([
                        'success' => false,
                        'message' => 'Image upload failed: ' . $uploadResult['message']
                    ], 400);
                    return;
                }
            }
            
            // Insert event
            $query = "INSERT INTO events (title, description, event_date, location, type, image, 
                                        max_participants, registration_deadline, contact_email, 
                                        contact_phone, is_active, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            
            $params = [
                $data['title'],
                $data['description'],
                $data['event_date'],
                $data['location'],
                $data['type'],
                $imagePath,
                $data['max_participants'] ?? 0,
                $data['registration_deadline'] ?? null,
                $data['contact_email'] ?? null,
                $data['contact_phone'] ?? null
            ];
            
            $eventId = $this->db->insert($query, $params);
            
            if ($eventId) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Event created successfully',
                    'event_id' => $eventId
                ]);
            } else {
                throw new Exception('Failed to create event');
            }
            
        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'message' => 'Failed to create event: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update event (Admin only)
     */
    public function updateEvent($id) {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
                return;
            }
            
            $data = $this->getRequestData();
            
            // Check if event exists
            $existingEvent = $this->db->fetch("SELECT * FROM events WHERE id = ?", [$id]);
            if (!$existingEvent) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Event not found'
                ], 404);
                return;
            }
            
            // Handle file upload
            $imagePath = $existingEvent['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->fileUpload->uploadImage($_FILES['image'], 'events');
                if ($uploadResult['success']) {
                    // Delete old image
                    if ($imagePath) {
                        $this->fileUpload->deleteFile('events/' . $imagePath);
                    }
                    $imagePath = $uploadResult['filename'];
                } else {
                    $this->sendResponse([
                        'success' => false,
                        'message' => 'Image upload failed: ' . $uploadResult['message']
                    ], 400);
                    return;
                }
            }
            
            // Update event
            $query = "UPDATE events SET 
                        title = ?, description = ?, event_date = ?, location = ?, 
                        type = ?, image = ?, max_participants = ?, 
                        registration_deadline = ?, contact_email = ?, contact_phone = ?,
                        updated_at = NOW()
                      WHERE id = ?";
            
            $params = [
                $data['title'] ?? $existingEvent['title'],
                $data['description'] ?? $existingEvent['description'],
                $data['event_date'] ?? $existingEvent['event_date'],
                $data['location'] ?? $existingEvent['location'],
                $data['type'] ?? $existingEvent['type'],
                $imagePath,
                $data['max_participants'] ?? $existingEvent['max_participants'],
                $data['registration_deadline'] ?? $existingEvent['registration_deadline'],
                $data['contact_email'] ?? $existingEvent['contact_email'],
                $data['contact_phone'] ?? $existingEvent['contact_phone'],
                $id
            ];
            
            $result = $this->db->execute($query, $params);
            
            if ($result) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Event updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update event');
            }
            
        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'message' => 'Failed to update event: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete event (Admin only)
     */
    public function deleteEvent($id) {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
                return;
            }
            
            // Check if event exists
            $event = $this->db->fetch("SELECT * FROM events WHERE id = ?", [$id]);
            if (!$event) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Event not found'
                ], 404);
                return;
            }
            
            // Soft delete
            $result = $this->db->execute("UPDATE events SET is_active = 0, updated_at = NOW() WHERE id = ?", [$id]);
            
            if ($result) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Event deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete event');
            }
            
        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'message' => 'Failed to delete event: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Register for event
     */
    public function registerForEvent() {
        try {
            $data = $this->getRequestData();
            
            // Validate required fields
            $required = ['event_id', 'name', 'email', 'phone'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->sendResponse([
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ], 400);
                    return;
                }
            }
            
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Invalid email address'
                ], 400);
                return;
            }
            
            // Check if event exists and is upcoming
            $event = $this->db->fetch(
                "SELECT * FROM events WHERE id = ? AND is_active = 1 AND event_date > NOW()",
                [$data['event_id']]
            );
            
            if (!$event) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Event not found or registration closed'
                ], 404);
                return;
            }
            
            // Check registration deadline
            if ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Registration deadline has passed'
                ], 400);
                return;
            }
            
            // Check if already registered
            $existingRegistration = $this->db->fetch(
                "SELECT id FROM event_registrations WHERE event_id = ? AND email = ?",
                [$data['event_id'], $data['email']]
            );
            
            if ($existingRegistration) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'You are already registered for this event'
                ], 400);
                return;
            }
            
            // Check capacity
            if ($event['max_participants'] > 0) {
                $currentRegistrations = $this->db->fetch(
                    "SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?",
                    [$data['event_id']]
                );
                
                if ($currentRegistrations['count'] >= $event['max_participants']) {
                    $this->sendResponse([
                        'success' => false,
                        'message' => 'Event is full. Registration closed.'
                    ], 400);
                    return;
                }
            }
            
            // Insert registration
            $query = "INSERT INTO event_registrations (event_id, name, email, phone, organization, 
                                                      designation, message, registration_date) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $data['event_id'],
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['organization'] ?? null,
                $data['designation'] ?? null,
                $data['message'] ?? null
            ];
            
            $registrationId = $this->db->insert($query, $params);
            
            if ($registrationId) {
                // Send confirmation email
                try {
                    $this->emailService->sendEventRegistrationConfirmation(
                        $data['email'],
                        $data['name'],
                        $event
                    );
                } catch (Exception $e) {
                    // Log email error but don't fail the registration
                    error_log("Failed to send registration confirmation email: " . $e->getMessage());
                }
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Registration successful! Confirmation email sent.',
                    'registration_id' => $registrationId
                ]);
            } else {
                throw new Exception('Failed to register for event');
            }
            
        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get event registrations (Admin only)
     */
    public function getEventRegistrations($eventId) {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                $this->sendResponse([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
                return;
            }
            
            $query = "SELECT er.*, e.title as event_title 
                      FROM event_registrations er 
                      JOIN events e ON er.event_id = e.id 
                      WHERE er.event_id = ? 
                      ORDER BY er.registration_date DESC";
            
            $registrations = $this->db->fetchAll($query, [$eventId]);
            
            $this->sendResponse([
                'success' => true,
                'registrations' => $registrations
            ]);
            
        } catch (Exception $e) {
            $this->sendResponse([
                'success' => false,
                'message' => 'Failed to fetch registrations: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get event types
     */
    public function getEventTypes() {
        $types = [
            'workshop' => 'Workshop',
            'seminar' => 'Seminar',
            'awareness' => 'Awareness Program',
            'meeting' => 'Meeting',
            'conference' => 'Conference',
            'training' => 'Training'
        ];
        
        $this->sendResponse([
            'success' => true,
            'types' => $types
        ]);
    }
    
    /**
     * Helper method to check admin authentication
     */
    private function isAdmin() {
        session_start();
        return isset($_SESSION['admin_id']) && $_SESSION['admin_logged_in'] === true;
    }
    
    /**
     * Helper method to get request data
     */
    private function getRequestData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            return array_merge($_POST, $_GET);
        }
    }
    
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    $controller = new EventController();
    $requestUri = $_SERVER['REQUEST_URI'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Parse the request
    $path = parse_url($requestUri, PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Route the request
    switch ($method) {
        case 'GET':
            if (isset($pathParts[3]) && is_numeric($pathParts[3])) {
                if (isset($pathParts[4]) && $pathParts[4] === 'registrations') {
                    $controller->getEventRegistrations($pathParts[3]);
                } else {
                    $controller->getEvent($pathParts[3]);
                }
            } elseif (isset($_GET['types'])) {
                $controller->getEventTypes();
            } else {
                $controller->getEvents();
            }
            break;
            
        case 'POST':
            if (isset($pathParts[4]) && $pathParts[4] === 'register') {
                $controller->registerForEvent();
            } else {
                $controller->createEvent();
            }
            break;
            
        case 'PUT':
            if (isset($pathParts[3]) && is_numeric($pathParts[3])) {
                $controller->updateEvent($pathParts[3]);
            }
            break;
            
        case 'DELETE':
            if (isset($pathParts[3]) && is_numeric($pathParts[3])) {
                $controller->deleteEvent($pathParts[3]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} else {
    // Handle preflight requests
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    http_response_code(200);
}
?>