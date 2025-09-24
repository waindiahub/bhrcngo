<?php

require_once __DIR__ . '/../config/Database.php';

class Activity {
    private $db;
    private $table = 'activities';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new activity
     */
    public function create($data) {
        $connection = $this->db->getConnection();
        
        try {
            // Validate required fields
            $required = ['title'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }
            
            // Set default values
            $data['status'] = $data['status'] ?? 'planned';
            $data['featured'] = $data['featured'] ?? false;
            $data['participants_count'] = $data['participants_count'] ?? 0;
            $data['beneficiaries_count'] = $data['beneficiaries_count'] ?? 0;
            
            // Handle JSON fields
            if (isset($data['tags']) && is_array($data['tags'])) {
                $data['tags'] = json_encode($data['tags']);
            }
            
            if (isset($data['gallery_images']) && is_array($data['gallery_images'])) {
                $data['gallery_images'] = json_encode($data['gallery_images']);
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
            throw new Exception("Failed to create activity: " . $e->getMessage());
        }
    }
    
    /**
     * Get activity by ID
     */
    public function getById($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT a.*, u.name as created_by_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.id = ?
        ");
        
        $stmt->execute([$id]);
        $activity = $stmt->fetch();
        
        if ($activity) {
            // Decode JSON fields
            if ($activity['tags']) {
                $activity['tags'] = json_decode($activity['tags'], true);
            }
            if ($activity['gallery_images']) {
                $activity['gallery_images'] = json_decode($activity['gallery_images'], true);
            }
        }
        
        return $activity;
    }
    
    /**
     * Update activity
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
            
            if (isset($data['gallery_images']) && is_array($data['gallery_images'])) {
                $data['gallery_images'] = json_encode($data['gallery_images']);
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
            throw new Exception("Failed to update activity: " . $e->getMessage());
        }
    }
    
    /**
     * Delete activity
     */
    public function delete($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get all activities with pagination and filtering
     */
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $connection = $this->db->getConnection();
        
        $offset = ($page - 1) * $limit;
        $where = ["1=1"];
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $where[] = "a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "a.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['featured'])) {
            $where[] = "a.featured = ?";
            $params[] = $filters['featured'] === 'true' ? 1 : 0;
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(a.title LIKE ? OR a.description LIKE ? OR a.location LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "a.activity_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "a.activity_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $connection->prepare("
            SELECT COUNT(*) FROM {$this->table} a WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get activities
        $stmt = $connection->prepare("
            SELECT a.*, u.name as created_by_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE {$whereClause}
            ORDER BY a.activity_date DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        $activities = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($activities as &$activity) {
            if ($activity['tags']) {
                $activity['tags'] = json_decode($activity['tags'], true);
            }
            if ($activity['gallery_images']) {
                $activity['gallery_images'] = json_decode($activity['gallery_images'], true);
            }
        }
        
        return [
            'activities' => $activities,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get recent activities
     */
    public function getRecent($limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT a.*, u.name as created_by_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.status IN ('completed', 'ongoing')
            ORDER BY a.activity_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $activities = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($activities as &$activity) {
            if ($activity['tags']) {
                $activity['tags'] = json_decode($activity['tags'], true);
            }
            if ($activity['gallery_images']) {
                $activity['gallery_images'] = json_decode($activity['gallery_images'], true);
            }
        }
        
        return $activities;
    }
    
    /**
     * Get featured activities
     */
    public function getFeatured($limit = 5) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT a.*, u.name as created_by_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.featured = 1
            ORDER BY a.activity_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $activities = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($activities as &$activity) {
            if ($activity['tags']) {
                $activity['tags'] = json_decode($activity['tags'], true);
            }
            if ($activity['gallery_images']) {
                $activity['gallery_images'] = json_decode($activity['gallery_images'], true);
            }
        }
        
        return $activities;
    }
    
    /**
     * Get activity statistics
     */
    public function getStats() {
        $connection = $this->db->getConnection();
        
        $stats = [];
        
        // Total activities
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Completed activities
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'completed'");
        $stats['completed'] = $stmt->fetchColumn();
        
        // Ongoing activities
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'ongoing'");
        $stats['ongoing'] = $stmt->fetchColumn();
        
        // Activities by status
        $stmt = $connection->query("
            SELECT status, COUNT(*) as count 
            FROM {$this->table} 
            GROUP BY status
        ");
        $stats['by_status'] = $stmt->fetchAll();
        
        // Activities by category
        $stmt = $connection->query("
            SELECT category, COUNT(*) as count 
            FROM {$this->table} 
            WHERE category IS NOT NULL
            GROUP BY category
            ORDER BY count DESC
        ");
        $stats['by_category'] = $stmt->fetchAll();
        
        // Total participants
        $stmt = $connection->query("SELECT SUM(participants_count) FROM {$this->table}");
        $stats['total_participants'] = $stmt->fetchColumn() ?: 0;
        
        // Total beneficiaries
        $stmt = $connection->query("SELECT SUM(beneficiaries_count) FROM {$this->table}");
        $stats['total_beneficiaries'] = $stmt->fetchColumn() ?: 0;
        
        // Recent activities (last 30 days)
        $stmt = $connection->query("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['recent'] = $stmt->fetchColumn();
        
        // Activities this year
        $stmt = $connection->query("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE YEAR(activity_date) = YEAR(NOW())
        ");
        $stats['this_year'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    /**
     * Get activities by category
     */
    public function getByCategory($category, $limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT a.*, u.name as created_by_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.category = ?
            ORDER BY a.activity_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$category, $limit]);
        $activities = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($activities as &$activity) {
            if ($activity['tags']) {
                $activity['tags'] = json_decode($activity['tags'], true);
            }
            if ($activity['gallery_images']) {
                $activity['gallery_images'] = json_decode($activity['gallery_images'], true);
            }
        }
        
        return $activities;
    }
    
    /**
     * Search activities
     */
    public function search($query, $limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT a.*, u.name as created_by_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.title LIKE ? OR a.description LIKE ? OR a.location LIKE ?
            ORDER BY a.activity_date DESC
            LIMIT ?
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get activity categories
     */
    public function getCategories() {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->query("
            SELECT DISTINCT category, COUNT(*) as count
            FROM {$this->table} 
            WHERE category IS NOT NULL
            GROUP BY category
            ORDER BY count DESC
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get activities by status
     */
    public function getByStatus($status, $limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT a.*, u.name as created_by_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.status = ?
            ORDER BY a.activity_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$status, $limit]);
        $activities = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($activities as &$activity) {
            if ($activity['tags']) {
                $activity['tags'] = json_decode($activity['tags'], true);
            }
            if ($activity['gallery_images']) {
                $activity['gallery_images'] = json_decode($activity['gallery_images'], true);
            }
        }
        
        return $activities;
    }
    
    /**
     * Update activity status
     */
    public function updateStatus($id, $status) {
        return $this->update($id, ['status' => $status]);
    }
    
    /**
     * Get impact summary
     */
    public function getImpactSummary() {
        $connection = $this->db->getConnection();
        
        $summary = [];
        
        // Total impact by year
        $stmt = $connection->query("
            SELECT YEAR(activity_date) as year,
                   COUNT(*) as activities_count,
                   SUM(participants_count) as total_participants,
                   SUM(beneficiaries_count) as total_beneficiaries
            FROM {$this->table}
            WHERE status = 'completed'
            AND activity_date IS NOT NULL
            GROUP BY YEAR(activity_date)
            ORDER BY year DESC
            LIMIT 5
        ");
        $summary['by_year'] = $stmt->fetchAll();
        
        // Impact by category
        $stmt = $connection->query("
            SELECT category,
                   COUNT(*) as activities_count,
                   SUM(participants_count) as total_participants,
                   SUM(beneficiaries_count) as total_beneficiaries
            FROM {$this->table}
            WHERE status = 'completed'
            AND category IS NOT NULL
            GROUP BY category
            ORDER BY activities_count DESC
        ");
        $summary['by_category'] = $stmt->fetchAll();
        
        return $summary;
    }
}

?>