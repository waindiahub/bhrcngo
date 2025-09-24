<?php
/**
 * Gallery Controller
 * Handles gallery operations including photos, videos, and media management
 */

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../utils/FileUpload.php';
require_once __DIR__ . '/../services/EmailService.php';

class GalleryController {
    private $db;
    private $fileUpload;
    private $emailService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->fileUpload = new FileUpload();
        $this->emailService = new EmailService();
    }
    
    /**
     * Get all gallery items (API method)
     */
    public function getAll($params = []) {
        try {
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 12;
            $category = isset($params['category']) ? $params['category'] : null;
            $type = isset($params['type']) ? $params['type'] : null;
            
            $filters = [];
            if ($category) $filters['category'] = $category;
            if ($type) $filters['type'] = $type;
            
            $galleryModel = new Gallery();
            $result = $galleryModel->getAll($page, $limit, $filters);
            
            return $this->sendResponse($result);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch gallery items: ' . $e->getMessage());
        }
    }
    
    /**
     * Get gallery item by ID (API method)
     */
    public function getById($id) {
        try {
            if (!$id) {
                return $this->sendError('Gallery item ID is required');
            }
            
            $galleryModel = new Gallery();
            $item = $galleryModel->getById($id);
            
            if (!$item) {
                return $this->sendError('Gallery item not found', 404);
            }
            
            return $this->sendResponse($item);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch gallery item: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new gallery item (API method)
     */
    public function create($data) {
        try {
            // Validate required fields
            $required = ['title', 'category', 'type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->sendError("Field '$field' is required");
                }
            }
            
            $galleryModel = new Gallery();
            $itemId = $galleryModel->create($data);
            
            if ($itemId) {
                $item = $galleryModel->getById($itemId);
                return $this->sendResponse($item, 'Gallery item created successfully');
            } else {
                return $this->sendError('Failed to create gallery item');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to create gallery item: ' . $e->getMessage());
        }
    }
    
    /**
     * Update gallery item (API method)
     */
    public function update($id, $data) {
        try {
            if (!$id) {
                return $this->sendError('Gallery item ID is required');
            }
            
            $galleryModel = new Gallery();
            $existingItem = $galleryModel->getById($id);
            if (!$existingItem) {
                return $this->sendError('Gallery item not found', 404);
            }
            
            $success = $galleryModel->update($id, $data);
            
            if ($success) {
                $item = $galleryModel->getById($id);
                return $this->sendResponse($item, 'Gallery item updated successfully');
            } else {
                return $this->sendError('Failed to update gallery item');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to update gallery item: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete gallery item (API method)
     */
    public function delete($id) {
        try {
            if (!$id) {
                return $this->sendError('Gallery item ID is required');
            }
            
            $galleryModel = new Gallery();
            $item = $galleryModel->getById($id);
            if (!$item) {
                return $this->sendError('Gallery item not found', 404);
            }
            
            $success = $galleryModel->delete($id);
            
            if ($success) {
                return $this->sendResponse(null, 'Gallery item deleted successfully');
            } else {
                return $this->sendError('Failed to delete gallery item');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to delete gallery item: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload gallery media (API method)
     */
    public function upload($files, $data = []) {
        try {
            if (!isset($files['file'])) {
                return $this->sendError('No file provided');
            }
            
            $uploadResult = $this->fileUpload->uploadImage($files['file'], 'gallery');
            
            if ($uploadResult['success']) {
                return $this->sendResponse([
                    'filename' => $uploadResult['filename'],
                    'url' => $uploadResult['url']
                ], 'File uploaded successfully');
            } else {
                return $this->sendError('File upload failed: ' . $uploadResult['message']);
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to upload file: ' . $e->getMessage());
        }
    }
    
    /**
     * Apply for gallery submission (API method)
     */
    public function apply($data) {
        try {
            // Validate required fields
            $required = ['name', 'email', 'title', 'description'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->sendError("Field '$field' is required");
                }
            }
            
            // Create application record
            $applicationData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $stmt = $this->db->prepare("INSERT INTO gallery_applications (name, email, title, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute(array_values($applicationData));
            
            if ($success) {
                // Send notification email
                $this->emailService->sendGalleryApplicationNotification($data);
                return $this->sendResponse(null, 'Application submitted successfully');
            } else {
                return $this->sendError('Failed to submit application');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to submit application: ' . $e->getMessage());
        }
    }
    
    /**
     * Send API response
     */
    private function sendResponse($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }
    
    /**
     * Send API error response
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        return [
            'success' => false,
            'message' => $message
        ];
    }
    
    /**
     * Handle incoming requests
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if exists
        $path = str_replace('/api/gallery', '', $path);
        
        switch ($method) {
            case 'GET':
                if ($path === '' || $path === '/') {
                    $this->getGalleryItems();
                } elseif ($path === '/stats') {
                    $this->getStatistics();
                } elseif (preg_match('/^\/(\d+)$/', $path, $matches)) {
                    $this->getGalleryItem($matches[1]);
                } else {
                    $this->sendResponse(['error' => 'Not found'], 404);
                }
                break;
                
            case 'POST':
                if ($path === '' || $path === '/') {
                    $this->createGalleryItem();
                } else {
                    $this->sendResponse(['error' => 'Not found'], 404);
                }
                break;
                
            case 'PUT':
                if (preg_match('/^\/(\d+)$/', $path, $matches)) {
                    $this->updateGalleryItem($matches[1]);
                } else {
                    $this->sendResponse(['error' => 'Not found'], 404);
                }
                break;
                
            case 'DELETE':
                if (preg_match('/^\/(\d+)$/', $path, $matches)) {
                    $this->deleteGalleryItem($matches[1]);
                } else {
                    $this->sendResponse(['error' => 'Not found'], 404);
                }
                break;
                
            default:
                $this->sendResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * Get gallery items with filtering and pagination
     */
    public function getGalleryItems() {
        try {
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 12;
            $offset = ($page - 1) * $limit;
            
            $category = isset($_GET['category']) ? $this->sanitizeInput($_GET['category']) : '';
            $type = isset($_GET['type']) ? $this->sanitizeInput($_GET['type']) : '';
            $search = isset($_GET['search']) ? $this->sanitizeInput($_GET['search']) : '';
            
            // Build WHERE clause
            $whereConditions = ['status = ?'];
            $params = ['active'];
            
            if (!empty($category) && $category !== 'all') {
                $whereConditions[] = 'category = ?';
                $params[] = $category;
            }
            
            if (!empty($type) && $type !== 'all') {
                $whereConditions[] = 'type = ?';
                $params[] = $type;
            }
            
            if (!empty($search)) {
                $whereConditions[] = '(title LIKE ? OR description LIKE ? OR tags LIKE ?)';
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM gallery WHERE {$whereClause}";
            $totalResult = $this->db->query($countQuery, $params);
            $total = $totalResult[0]['total'] ?? 0;
            
            // Get items
            $query = "
                SELECT 
                    id, title, description, type, category, file_path, thumbnail_path,
                    tags, views, created_at, updated_at
                FROM gallery 
                WHERE {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $items = $this->db->query($query, $params);
            
            // Process items
            $processedItems = array_map([$this, 'processGalleryItem'], $items);
            
            $this->sendResponse([
                'success' => true,
                'items' => $processedItems,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => intval($total),
                    'total_pages' => ceil($total / $limit),
                    'has_more' => ($page * $limit) < $total
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Gallery fetch error: " . $e->getMessage());
            $this->sendResponse(['error' => 'Failed to fetch gallery items'], 500);
        }
    }
    
    /**
     * Get single gallery item
     */
    public function getGalleryItem($id) {
        try {
            $query = "
                SELECT 
                    id, title, description, type, category, file_path, thumbnail_path,
                    tags, views, created_at, updated_at
                FROM gallery 
                WHERE id = ? AND status = 'active'
            ";
            
            $result = $this->db->query($query, [$id]);
            
            if (empty($result)) {
                $this->sendResponse(['error' => 'Gallery item not found'], 404);
                return;
            }
            
            $item = $this->processGalleryItem($result[0]);
            
            // Increment view count
            $this->incrementViews($id);
            
            $this->sendResponse([
                'success' => true,
                'item' => $item
            ]);
            
        } catch (Exception $e) {
            error_log("Gallery item fetch error: " . $e->getMessage());
            $this->sendResponse(['error' => 'Failed to fetch gallery item'], 500);
        }
    }
    
    /**
     * Create new gallery item
     */
    public function createGalleryItem() {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                $this->sendResponse(['error' => 'Unauthorized'], 401);
                return;
            }
            
            $data = $this->getRequestData();
            
            // Validate required fields
            $requiredFields = ['title', 'description', 'type', 'category'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->sendResponse(['error' => "Field '{$field}' is required"], 400);
                    return;
                }
            }
            
            // Validate type
            if (!in_array($data['type'], ['photo', 'video'])) {
                $this->sendResponse(['error' => 'Invalid type. Must be photo or video'], 400);
                return;
            }
            
            // Validate category
            $validCategories = ['events', 'activities', 'meetings', 'awards', 'other'];
            if (!in_array($data['category'], $validCategories)) {
                $this->sendResponse(['error' => 'Invalid category'], 400);
                return;
            }
            
            // Handle file upload
            $filePath = null;
            $thumbnailPath = null;
            
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleFileUpload($_FILES['file'], $data['type']);
                if (!$uploadResult['success']) {
                    $this->sendResponse(['error' => $uploadResult['message']], 400);
                    return;
                }
                
                $filePath = $uploadResult['file_path'];
                $thumbnailPath = $uploadResult['thumbnail_path'] ?? null;
            } else {
                $this->sendResponse(['error' => 'File upload is required'], 400);
                return;
            }
            
            // Insert into database
            $query = "
                INSERT INTO gallery (
                    title, description, type, category, file_path, thumbnail_path,
                    tags, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ";
            
            $params = [
                $this->sanitizeInput($data['title']),
                $this->sanitizeInput($data['description']),
                $data['type'],
                $data['category'],
                $filePath,
                $thumbnailPath,
                isset($data['tags']) ? $this->sanitizeInput($data['tags']) : ''
            ];
            
            $result = $this->db->execute($query, $params);
            
            if ($result) {
                $itemId = $this->db->getLastInsertId();
                
                // Get the created item
                $createdItem = $this->db->query(
                    "SELECT * FROM gallery WHERE id = ?", 
                    [$itemId]
                )[0];
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Gallery item created successfully',
                    'item' => $this->processGalleryItem($createdItem)
                ], 201);
            } else {
                $this->sendResponse(['error' => 'Failed to create gallery item'], 500);
            }
            
        } catch (Exception $e) {
            error_log("Gallery creation error: " . $e->getMessage());
            $this->sendResponse(['error' => 'Failed to create gallery item'], 500);
        }
    }
    
    /**
     * Update gallery item
     */
    public function updateGalleryItem($id) {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                $this->sendResponse(['error' => 'Unauthorized'], 401);
                return;
            }
            
            // Check if item exists
            $existingItem = $this->db->query(
                "SELECT * FROM gallery WHERE id = ?", 
                [$id]
            );
            
            if (empty($existingItem)) {
                $this->sendResponse(['error' => 'Gallery item not found'], 404);
                return;
            }
            
            $data = $this->getRequestData();
            $updateFields = [];
            $params = [];
            
            // Update allowed fields
            $allowedFields = ['title', 'description', 'category', 'tags', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $this->sanitizeInput($data[$field]);
                }
            }
            
            // Handle file upload if provided
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleFileUpload($_FILES['file'], $existingItem[0]['type']);
                if ($uploadResult['success']) {
                    // Delete old files
                    $this->deleteFiles($existingItem[0]['file_path'], $existingItem[0]['thumbnail_path']);
                    
                    $updateFields[] = "file_path = ?";
                    $params[] = $uploadResult['file_path'];
                    
                    if ($uploadResult['thumbnail_path']) {
                        $updateFields[] = "thumbnail_path = ?";
                        $params[] = $uploadResult['thumbnail_path'];
                    }
                }
            }
            
            if (empty($updateFields)) {
                $this->sendResponse(['error' => 'No valid fields to update'], 400);
                return;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $id;
            
            $query = "UPDATE gallery SET " . implode(', ', $updateFields) . " WHERE id = ?";
            
            $result = $this->db->execute($query, $params);
            
            if ($result) {
                // Get updated item
                $updatedItem = $this->db->query(
                    "SELECT * FROM gallery WHERE id = ?", 
                    [$id]
                )[0];
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Gallery item updated successfully',
                    'item' => $this->processGalleryItem($updatedItem)
                ]);
            } else {
                $this->sendResponse(['error' => 'Failed to update gallery item'], 500);
            }
            
        } catch (Exception $e) {
            error_log("Gallery update error: " . $e->getMessage());
            $this->sendResponse(['error' => 'Failed to update gallery item'], 500);
        }
    }
    
    /**
     * Delete gallery item
     */
    public function deleteGalleryItem($id) {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                $this->sendResponse(['error' => 'Unauthorized'], 401);
                return;
            }
            
            // Get item details for file cleanup
            $item = $this->db->query(
                "SELECT file_path, thumbnail_path FROM gallery WHERE id = ?", 
                [$id]
            );
            
            if (empty($item)) {
                $this->sendResponse(['error' => 'Gallery item not found'], 404);
                return;
            }
            
            // Delete from database
            $result = $this->db->execute(
                "DELETE FROM gallery WHERE id = ?", 
                [$id]
            );
            
            if ($result) {
                // Delete associated files
                $this->deleteFiles($item[0]['file_path'], $item[0]['thumbnail_path']);
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Gallery item deleted successfully'
                ]);
            } else {
                $this->sendResponse(['error' => 'Failed to delete gallery item'], 500);
            }
            
        } catch (Exception $e) {
            error_log("Gallery deletion error: " . $e->getMessage());
            $this->sendResponse(['error' => 'Failed to delete gallery item'], 500);
        }
    }
    
    /**
     * Get gallery statistics
     */
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total counts by type
            $typeStats = $this->db->query("
                SELECT type, COUNT(*) as count 
                FROM gallery 
                WHERE status = 'active' 
                GROUP BY type
            ");
            
            foreach ($typeStats as $stat) {
                $stats[$stat['type'] . 's'] = intval($stat['count']);
            }
            
            // Total counts by category
            $categoryStats = $this->db->query("
                SELECT category, COUNT(*) as count 
                FROM gallery 
                WHERE status = 'active' 
                GROUP BY category
            ");
            
            foreach ($categoryStats as $stat) {
                $stats[$stat['category']] = intval($stat['count']);
            }
            
            // Total views
            $viewStats = $this->db->query("
                SELECT SUM(views) as total_views 
                FROM gallery 
                WHERE status = 'active'
            ");
            
            $stats['views'] = intval($viewStats[0]['total_views'] ?? 0);
            
            // Recent activity
            $recentItems = $this->db->query("
                SELECT COUNT(*) as count 
                FROM gallery 
                WHERE status = 'active' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            
            $stats['recent_uploads'] = intval($recentItems[0]['count'] ?? 0);
            
            $this->sendResponse([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Gallery stats error: " . $e->getMessage());
            $this->sendResponse(['error' => 'Failed to fetch statistics'], 500);
        }
    }
    
    /**
     * Handle file upload
     */
    private function handleFileUpload($file, $type) {
        try {
            $uploadDir = __DIR__ . '/../../uploads/gallery/';
            
            // Set allowed types based on media type
            if ($type === 'photo') {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 10 * 1024 * 1024; // 10MB
            } else { // video
                $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
                $maxSize = 100 * 1024 * 1024; // 100MB
            }
            
            $this->fileUpload->setUploadPath($uploadDir);
            $this->fileUpload->setAllowedTypes($allowedTypes);
            $this->fileUpload->setMaxFileSize($maxSize);
            
            $result = $this->fileUpload->upload($file);
            
            if (!$result['success']) {
                return $result;
            }
            
            $filePath = 'uploads/gallery/' . $result['filename'];
            $thumbnailPath = null;
            
            // Generate thumbnail for images
            if ($type === 'photo') {
                $thumbnailResult = $this->fileUpload->createThumbnail(
                    $uploadDir . $result['filename'],
                    $uploadDir . 'thumbs/',
                    300,
                    300
                );
                
                if ($thumbnailResult['success']) {
                    $thumbnailPath = 'uploads/gallery/thumbs/' . $thumbnailResult['filename'];
                }
            }
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'thumbnail_path' => $thumbnailPath
            ];
            
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'File upload failed'
            ];
        }
    }
    
    /**
     * Delete files from filesystem
     */
    private function deleteFiles($filePath, $thumbnailPath = null) {
        try {
            $basePath = __DIR__ . '/../../';
            
            if ($filePath && file_exists($basePath . $filePath)) {
                unlink($basePath . $filePath);
            }
            
            if ($thumbnailPath && file_exists($basePath . $thumbnailPath)) {
                unlink($basePath . $thumbnailPath);
            }
        } catch (Exception $e) {
            error_log("File deletion error: " . $e->getMessage());
        }
    }
    
    /**
     * Process gallery item for response
     */
    private function processGalleryItem($item) {
        return [
            'id' => intval($item['id']),
            'title' => $item['title'],
            'description' => $item['description'],
            'type' => $item['type'],
            'category' => $item['category'],
            'url' => $this->getFileUrl($item['file_path']),
            'thumbnail' => $item['thumbnail_path'] ? $this->getFileUrl($item['thumbnail_path']) : $this->getFileUrl($item['file_path']),
            'tags' => $item['tags'] ? explode(',', $item['tags']) : [],
            'views' => intval($item['views'] ?? 0),
            'date' => $item['created_at'],
            'updated_at' => $item['updated_at']
        ];
    }
    
    /**
     * Get full URL for file
     */
    private function getFileUrl($path) {
        if (!$path) return null;
        
        $baseUrl = $this->getBaseUrl();
        return $baseUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Get base URL
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . '://' . $host;
    }
    
    /**
     * Increment view count
     */
    private function incrementViews($id) {
        try {
            $this->db->execute(
                "UPDATE gallery SET views = views + 1 WHERE id = ?", 
                [$id]
            );
        } catch (Exception $e) {
            error_log("View increment error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin() {
        session_start();
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Get request data
     */
    private function getRequestData() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        
        return array_merge($_POST, $_GET);
    }
    
    /**
     * Sanitize input
     */
    private function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    

}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}

// Initialize controller if accessed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $controller = new GalleryController();
    $controller->handleRequest();
}
?>