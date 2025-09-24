<?php

require_once __DIR__ . '/../config/Database.php';

class Event {
    private $db;
    private $table = 'events';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new event
     */
    public function create($data) {
        $connection = $this->db->getConnection();
        
        try {
            // Validate required fields
            $required = ['title', 'event_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }
            
            // Set default values
            $data['status'] = $data['status'] ?? 'draft';
            $data['featured'] = $data['featured'] ?? false;
            $data['registration_required'] = $data['registration_required'] ?? false;
            
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
            throw new Exception("Failed to create event: " . $e->getMessage());
        }
    }
    
    /**
     * Get event by ID
     */
    public function getById($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT e.*, u.name as created_by_name
            FROM {$this->table} e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.id = ?
        ");
        
        $stmt->execute([$id]);
        $event = $stmt->fetch();
        
        if ($event) {
            // Decode JSON fields
            if ($event['tags']) {
                $event['tags'] = json_decode($event['tags'], true);
            }
            if ($event['gallery_images']) {
                $event['gallery_images'] = json_decode($event['gallery_images'], true);
            }
        }
        
        return $event;
    }
    
    /**
     * Update event
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
            throw new Exception("Failed to update event: " . $e->getMessage());
        }
    }
    
    /**
     * Delete event
     */
    public function delete($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get all events with pagination and filtering
     */
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $connection = $this->db->getConnection();
        
        $offset = ($page - 1) * $limit;
        $where = ["1=1"];
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $where[] = "e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "e.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['featured'])) {
            $where[] = "e.featured = ?";
            $params[] = $filters['featured'] === 'true' ? 1 : 0;
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "e.event_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "e.event_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Only show published events for public view
        if (isset($filters['public']) && $filters['public']) {
            $where[] = "e.status = 'published'";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $connection->prepare("
            SELECT COUNT(*) FROM {$this->table} e WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get events
        $stmt = $connection->prepare("
            SELECT e.*, u.name as created_by_name,
                   (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) as registrations_count
            FROM {$this->table} e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE {$whereClause}
            ORDER BY e.event_date DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        $events = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($events as &$event) {
            if ($event['tags']) {
                $event['tags'] = json_decode($event['tags'], true);
            }
            if ($event['gallery_images']) {
                $event['gallery_images'] = json_decode($event['gallery_images'], true);
            }
        }
        
        return [
            'events' => $events,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get upcoming events
     */
    public function getUpcoming($limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT e.*, u.name as created_by_name,
                   (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) as registrations_count
            FROM {$this->table} e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.event_date >= CURDATE() 
            AND e.status = 'published'
            ORDER BY e.event_date ASC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $events = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($events as &$event) {
            if ($event['tags']) {
                $event['tags'] = json_decode($event['tags'], true);
            }
            if ($event['gallery_images']) {
                $event['gallery_images'] = json_decode($event['gallery_images'], true);
            }
        }
        
        return $events;
    }
    
    /**
     * Get featured events
     */
    public function getFeatured($limit = 5) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT e.*, u.name as created_by_name,
                   (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) as registrations_count
            FROM {$this->table} e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.featured = 1 
            AND e.status = 'published'
            ORDER BY e.event_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $events = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($events as &$event) {
            if ($event['tags']) {
                $event['tags'] = json_decode($event['tags'], true);
            }
            if ($event['gallery_images']) {
                $event['gallery_images'] = json_decode($event['gallery_images'], true);
            }
        }
        
        return $events;
    }
    
    /**
     * Get event statistics
     */
    public function getStats() {
        $connection = $this->db->getConnection();
        
        $stats = [];
        
        // Total events
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Published events
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'published'");
        $stats['published'] = $stmt->fetchColumn();
        
        // Upcoming events
        $stmt = $connection->query("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE event_date >= CURDATE() AND status = 'published'
        ");
        $stats['upcoming'] = $stmt->fetchColumn();
        
        // Events by status
        $stmt = $connection->query("
            SELECT status, COUNT(*) as count 
            FROM {$this->table} 
            GROUP BY status
        ");
        $stats['by_status'] = $stmt->fetchAll();
        
        // Events by category
        $stmt = $connection->query("
            SELECT category, COUNT(*) as count 
            FROM {$this->table} 
            WHERE category IS NOT NULL
            GROUP BY category
            ORDER BY count DESC
        ");
        $stats['by_category'] = $stmt->fetchAll();
        
        // Recent events (last 30 days)
        $stmt = $connection->query("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['recent'] = $stmt->fetchColumn();
        
        // Total registrations
        $stmt = $connection->query("
            SELECT COUNT(*) FROM event_registrations er
            JOIN {$this->table} e ON er.event_id = e.id
        ");
        $stats['total_registrations'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    /**
     * Get events by category
     */
    public function getByCategory($category, $limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT e.*, u.name as created_by_name,
                   (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id) as registrations_count
            FROM {$this->table} e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.category = ? 
            AND e.status = 'published'
            ORDER BY e.event_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$category, $limit]);
        $events = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($events as &$event) {
            if ($event['tags']) {
                $event['tags'] = json_decode($event['tags'], true);
            }
            if ($event['gallery_images']) {
                $event['gallery_images'] = json_decode($event['gallery_images'], true);
            }
        }
        
        return $events;
    }
    
    /**
     * Search events
     */
    public function search($query, $limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT e.*, u.name as created_by_name
            FROM {$this->table} e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)
            AND e.status = 'published'
            ORDER BY e.event_date DESC
            LIMIT ?
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get event categories
     */
    public function getCategories() {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->query("
            SELECT DISTINCT category, COUNT(*) as count
            FROM {$this->table} 
            WHERE category IS NOT NULL AND status = 'published'
            GROUP BY category
            ORDER BY count DESC
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Register for event
     */
    public function registerParticipant($eventId, $participantData) {
        $connection = $this->db->getConnection();
        
        try {
            // Check if event exists and allows registration
            $event = $this->getById($eventId);
            if (!$event) {
                throw new Exception("Event not found");
            }
            
            if (!$event['registration_required']) {
                throw new Exception("Registration not required for this event");
            }
            
            if ($event['registration_deadline'] && $event['registration_deadline'] < date('Y-m-d')) {
                throw new Exception("Registration deadline has passed");
            }
            
            // Check if already registered
            $stmt = $connection->prepare("
                SELECT COUNT(*) FROM event_registrations 
                WHERE event_id = ? AND participant_email = ?
            ");
            $stmt->execute([$eventId, $participantData['participant_email']]);
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Already registered for this event");
            }
            
            // Check capacity
            if ($event['max_participants']) {
                $stmt = $connection->prepare("
                    SELECT COUNT(*) FROM event_registrations WHERE event_id = ?
                ");
                $stmt->execute([$eventId]);
                
                if ($stmt->fetchColumn() >= $event['max_participants']) {
                    throw new Exception("Event is full");
                }
            }
            
            // Register participant
            $participantData['event_id'] = $eventId;
            $participantData['status'] = 'registered';
            
            $fields = implode(', ', array_keys($participantData));
            $placeholders = ':' . implode(', :', array_keys($participantData));
            
            $stmt = $connection->prepare("
                INSERT INTO event_registrations ({$fields}) 
                VALUES ({$placeholders})
            ");
            
            $stmt->execute($participantData);
            
            return $connection->lastInsertId();
            
        } catch (Exception $e) {
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get event registrations
     */
    public function getRegistrations($eventId) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT * FROM event_registrations 
            WHERE event_id = ?
            ORDER BY registration_date DESC
        ");
        
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total count of events
     */
    public function getTotalCount() {
        $connection = $this->db->getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Get count of upcoming events
     */
    public function getUpcomingCount() {
        $connection = $this->db->getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM {$this->table} WHERE event_date >= CURDATE() AND status = 'published'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Get count of completed events
     */
    public function getCompletedCount() {
        $connection = $this->db->getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM {$this->table} WHERE event_date < CURDATE() AND status = 'published'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}

?>