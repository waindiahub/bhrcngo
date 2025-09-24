<?php

require_once __DIR__ . '/../config/Database.php';

class Gallery {
    private $db;
    private $table = 'gallery';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new gallery item
     */
    public function create($data) {
        $connection = $this->db->getConnection();
        
        try {
            // Validate required fields
            $required = ['title', 'type', 'file_path', 'upload_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }
            
            // Set default values
            $data['status'] = $data['status'] ?? 'active';
            $data['featured'] = $data['featured'] ?? false;
            
            // Handle JSON fields
            if (isset($data['tags']) && is_array($data['tags'])) {
                $data['tags'] = json_encode($data['tags']);
            }
            
            $fields = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $stmt = $connection->prepare("
                INSERT INTO {$this->table} ({$fields}) 
                VALUES ({$placeholders})
            ");
            
            $stmt->execute($data);
            
            return $connection->lastInsertId();
            
        } catch (Exception $e) {
            throw new Exception("Failed to create gallery item: " . $e->getMessage());
        }
    }
    
    /**
     * Get gallery item by ID
     */
    public function getById($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT g.*, u.name as uploaded_by_name,
                   e.title as event_title,
                   a.title as activity_title
            FROM {$this->table} g
            LEFT JOIN users u ON g.uploaded_by = u.id
            LEFT JOIN events e ON g.event_id = e.id
            LEFT JOIN activities a ON g.activity_id = a.id
            WHERE g.id = ?
        ");
        
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        if ($item && $item['tags']) {
            $item['tags'] = json_decode($item['tags'], true);
        }
        
        return $item;
    }
    
    /**
     * Update gallery item
     */
    public function update($id, $data) {
        $connection = $this->db->getConnection();
        
        try {
            // Remove fields that shouldn't be updated directly
            unset($data['id'], $data['created_at']);
            
            // Handle JSON fields
            if (isset($data['tags']) && is_array($data['tags'])) {
                $data['tags'] = json_encode($data['tags']);
            }
            
            if (empty($data)) {
                throw new Exception("No data to update");
            }
            
            $fields = [];
            foreach (array_keys($data) as $field) {
                $fields[] = "{$field} = :{$field}";
            }
            
            $stmt = $connection->prepare("
                UPDATE {$this->table} 
                SET " . implode(', ', $fields) . " 
                WHERE id = :id
            ");
            
            $data['id'] = $id;
            return $stmt->execute($data);
            
        } catch (Exception $e) {
            throw new Exception("Failed to update gallery item: " . $e->getMessage());
        }
    }
    
    /**
     * Delete gallery item (soft delete)
     */
    public function delete($id) {
        return $this->update($id, ['status' => 'deleted']);
    }
    
    /**
     * Hard delete gallery item
     */
    public function hardDelete($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get all gallery items with pagination and filtering
     */
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $connection = $this->db->getConnection();
        
        $offset = ($page - 1) * $limit;
        $where = ["g.status != 'deleted'"];
        $params = [];
        
        // Apply filters
        if (!empty($filters['type'])) {
            $where[] = "g.type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "g.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "g.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['featured'])) {
            $where[] = "g.featured = ?";
            $params[] = $filters['featured'] === 'true' ? 1 : 0;
        }
        
        if (!empty($filters['event_id'])) {
            $where[] = "g.event_id = ?";
            $params[] = $filters['event_id'];
        }
        
        if (!empty($filters['activity_id'])) {
            $where[] = "g.activity_id = ?";
            $params[] = $filters['activity_id'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(g.title LIKE ? OR g.description LIKE ? OR g.caption LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "g.upload_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "g.upload_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Only show active items for public view
        if (isset($filters['public']) && $filters['public']) {
            $where[] = "g.status = 'active'";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $connection->prepare("
            SELECT COUNT(*) FROM {$this->table} g WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get gallery items
        $stmt = $connection->prepare("
            SELECT g.*, u.name as uploaded_by_name,
                   e.title as event_title,
                   a.title as activity_title
            FROM {$this->table} g
            LEFT JOIN users u ON g.uploaded_by = u.id
            LEFT JOIN events e ON g.event_id = e.id
            LEFT JOIN activities a ON g.activity_id = a.id
            WHERE {$whereClause}
            ORDER BY g.upload_date DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        $items = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($items as &$item) {
            if ($item['tags']) {
                $item['tags'] = json_decode($item['tags'], true);
            }
        }
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get featured gallery items
     */
    public function getFeatured($limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT g.*, u.name as uploaded_by_name,
                   e.title as event_title,
                   a.title as activity_title
            FROM {$this->table} g
            LEFT JOIN users u ON g.uploaded_by = u.id
            LEFT JOIN events e ON g.event_id = e.id
            LEFT JOIN activities a ON g.activity_id = a.id
            WHERE g.featured = 1 AND g.status = 'active'
            ORDER BY g.upload_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $items = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($items as &$item) {
            if ($item['tags']) {
                $item['tags'] = json_decode($item['tags'], true);
            }
        }
        
        return $items;
    }
    
    /**
     * Get recent gallery items
     */
    public function getRecent($limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT g.*, u.name as uploaded_by_name,
                   e.title as event_title,
                   a.title as activity_title
            FROM {$this->table} g
            LEFT JOIN users u ON g.uploaded_by = u.id
            LEFT JOIN events e ON g.event_id = e.id
            LEFT JOIN activities a ON g.activity_id = a.id
            WHERE g.status = 'active'
            ORDER BY g.upload_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $items = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($items as &$item) {
            if ($item['tags']) {
                $item['tags'] = json_decode($item['tags'], true);
            }
        }
        
        return $items;
    }
    
    /**
     * Get gallery statistics
     */
    public function getStats() {
        $connection = $this->db->getConnection();
        
        $stats = [];
        
        // Total items
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table} WHERE status != 'deleted'");
        $stats['total'] = $stmt->fetchColumn();
        
        // Active items
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'");
        $stats['active'] = $stmt->fetchColumn();
        
        // Items by type
        $stmt = $connection->query("
            SELECT type, COUNT(*) as count 
            FROM {$this->table} 
            WHERE status != 'deleted'
            GROUP BY type
        ");
        $stats['by_type'] = $stmt->fetchAll();
        
        // Items by category
        $stmt = $connection->query("
            SELECT category, COUNT(*) as count 
            FROM {$this->table} 
            WHERE category IS NOT NULL AND status != 'deleted'
            GROUP BY category
            ORDER BY count DESC
        ");
        $stats['by_category'] = $stmt->fetchAll();
        
        // Recent uploads (last 30 days)
        $stmt = $connection->query("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE upload_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND status != 'deleted'
        ");
        $stats['recent'] = $stmt->fetchColumn();
        
        // Total file size
        $stmt = $connection->query("
            SELECT SUM(file_size) FROM {$this->table} 
            WHERE status != 'deleted'
        ");
        $stats['total_size'] = $stmt->fetchColumn() ?: 0;
        
        return $stats;
    }
    
    /**
     * Get gallery items by type
     */
    public function getByType($type, $limit = 20) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT g.*, u.name as uploaded_by_name,
                   e.title as event_title,
                   a.title as activity_title
            FROM {$this->table} g
            LEFT JOIN users u ON g.uploaded_by = u.id
            LEFT JOIN events e ON g.event_id = e.id
            LEFT JOIN activities a ON g.activity_id = a.id
            WHERE g.type = ? AND g.status = 'active'
            ORDER BY g.upload_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$type, $limit]);
        $items = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($items as &$item) {
            if ($item['tags']) {
                $item['tags'] = json_decode($item['tags'], true);
            }
        }
        
        return $items;
    }
    
    /**
     * Get gallery items by category
     */
    public function getByCategory($category, $limit = 20) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT g.*, u.name as uploaded_by_name,
                   e.title as event_title,
                   a.title as activity_title
            FROM {$this->table} g
            LEFT JOIN users u ON g.uploaded_by = u.id
            LEFT JOIN events e ON g.event_id = e.id
            LEFT JOIN activities a ON g.activity_id = a.id
            WHERE g.category = ? AND g.status = 'active'
            ORDER BY g.upload_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$category, $limit]);
        $items = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($items as &$item) {
            if ($item['tags']) {
                $item['tags'] = json_decode($item['tags'], true);
            }
        }
        
        return $items;
    }
    
    /**
     * Get gallery items by event
     */
    public function getByEvent($eventId, $limit = 50) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT g.*, u.name as uploaded_by_name
            FROM {$this->table} g
            LEFT JOIN users u ON g.uploaded_by = u.id
            WHERE g.event_id = ? AND g.status = 'active'
            ORDER BY g.upload_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$eventId, $limit]);
        $items = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($items as &$item) {
            if ($item['tags']) {
                $item['tags'] = json_decode($item['tags'], true);
            }
        }
        
        return $items;
    }
    
    /**
     * Get gallery items by activity
     */
    public function getByActivity($activityId, $limit = 50) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT g.*, u.name as uploaded_by_name
            FROM {$this->table} g
            LEFT JOIN users u ON g.uploaded_by = u.id
            WHERE g.activity_id = ? AND g.status = 'active'
            ORDER BY g.upload_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$activityId, $limit]);
        $items = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($items as &$item) {
            if ($item['tags']) {
                $item['tags'] = json_decode($item['tags'], true);
            }
        }
        
        return $items;
    }
    
    /**
     * Search gallery items
     */
    public function search($query, $limit = 20) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT g.*, u.name as uploaded_by_name,
                   e.title as event_title,
                   a.title as activity_title
            FROM {$this->table} g
            LEFT JOIN users u ON g.uploaded_by = u.id
            LEFT JOIN events e ON g.event_id = e.id
            LEFT JOIN activities a ON g.activity_id = a.id
            WHERE (g.title LIKE ? OR g.description LIKE ? OR g.caption LIKE ?)
            AND g.status = 'active'
            ORDER BY g.upload_date DESC
            LIMIT ?
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get gallery categories
     */
    public function getCategories() {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->query("
            SELECT DISTINCT category, COUNT(*) as count
            FROM {$this->table} 
            WHERE category IS NOT NULL AND status = 'active'
            GROUP BY category
            ORDER BY count DESC
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Upload file and create gallery item
     */
    public function uploadFile($fileData, $itemData) {
        try {
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm'];
            if (!in_array($fileData['type'], $allowedTypes)) {
                throw new Exception("File type not allowed");
            }
            
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($fileData['size'] > $maxSize) {
                throw new Exception("File size too large");
            }
            
            // Generate unique filename
            $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            
            // Determine upload directory based on type
            $uploadDir = $fileData['type'] === 'video/mp4' || $fileData['type'] === 'video/webm' 
                ? '../uploads/videos/' 
                : '../uploads/images/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filePath = $uploadDir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
                throw new Exception("Failed to upload file");
            }
            
            // Create thumbnail for images
            $thumbnailPath = null;
            if (strpos($fileData['type'], 'image/') === 0) {
                $thumbnailPath = $this->createThumbnail($filePath, $uploadDir . 'thumbs/');
            }
            
            // Get image dimensions
            $dimensions = null;
            if (strpos($fileData['type'], 'image/') === 0) {
                $imageInfo = getimagesize($filePath);
                if ($imageInfo) {
                    $dimensions = $imageInfo[0] . 'x' . $imageInfo[1];
                }
            }
            
            // Prepare item data
            $itemData['file_path'] = $filePath;
            $itemData['thumbnail_path'] = $thumbnailPath;
            $itemData['type'] = strpos($fileData['type'], 'image/') === 0 ? 'image' : 'video';
            $itemData['file_size'] = $fileData['size'];
            $itemData['dimensions'] = $dimensions;
            $itemData['upload_date'] = date('Y-m-d');
            
            // Create gallery item
            return $this->create($itemData);
            
        } catch (Exception $e) {
            // Clean up uploaded file if creation failed
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }
            if (isset($thumbnailPath) && file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
            
            throw new Exception("Upload failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create thumbnail for image
     */
    private function createThumbnail($sourcePath, $thumbDir, $width = 300, $height = 200) {
        if (!file_exists($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        
        $filename = basename($sourcePath);
        $thumbPath = $thumbDir . 'thumb_' . $filename;
        
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return null;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($width / $sourceWidth, $height / $sourceHeight);
        $newWidth = $sourceWidth * $ratio;
        $newHeight = $sourceHeight * $ratio;
        
        // Create source image
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return null;
        }
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        
        // Save thumbnail
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($thumbnail, $thumbPath, 85);
                break;
            case 'image/png':
                imagepng($thumbnail, $thumbPath);
                break;
            case 'image/gif':
                imagegif($thumbnail, $thumbPath);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        
        return $thumbPath;
    }
}

?>