<?php

require_once __DIR__ . '/../config/Database.php';

class Complaint {
    private $db;
    private $table = 'complaints';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new complaint
     */
    public function create($data) {
        $connection = $this->db->getConnection();
        
        try {
            // Validate required fields
            $required = ['complainant_name', 'complaint_type', 'description'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }
            
            // Generate unique complaint number
            $data['complaint_number'] = $this->generateComplaintNumber();
            
            // Set default values
            $data['priority'] = $data['priority'] ?? 'medium';
            $data['status'] = $data['status'] ?? 'pending';
            
            // Handle file uploads
            if (isset($data['evidence_files']) && is_array($data['evidence_files'])) {
                $data['evidence_files'] = json_encode($data['evidence_files']);
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
            throw new Exception("Failed to create complaint: " . $e->getMessage());
        }
    }
    
    /**
     * Get complaint by ID
     */
    public function getById($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT c.*, u.name as assigned_to_name
            FROM {$this->table} c
            LEFT JOIN users u ON c.assigned_to = u.id
            WHERE c.id = ?
        ");
        
        $stmt->execute([$id]);
        $complaint = $stmt->fetch();
        
        if ($complaint && $complaint['evidence_files']) {
            $complaint['evidence_files'] = json_decode($complaint['evidence_files'], true);
        }
        
        return $complaint;
    }
    
    /**
     * Get complaint by complaint number
     */
    public function getByComplaintNumber($complaintNumber) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT c.*, u.name as assigned_to_name
            FROM {$this->table} c
            LEFT JOIN users u ON c.assigned_to = u.id
            WHERE c.complaint_number = ?
        ");
        
        $stmt->execute([$complaintNumber]);
        $complaint = $stmt->fetch();
        
        if ($complaint && $complaint['evidence_files']) {
            $complaint['evidence_files'] = json_decode($complaint['evidence_files'], true);
        }
        
        return $complaint;
    }
    
    /**
     * Update complaint
     */
    public function update($id, $data) {
        $connection = $this->db->getConnection();
        
        try {
            // Remove fields that shouldn't be updated directly
            unset($data['id'], $data['complaint_number'], $data['created_at']);
            
            // Handle file uploads
            if (isset($data['evidence_files']) && is_array($data['evidence_files'])) {
                $data['evidence_files'] = json_encode($data['evidence_files']);
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
            throw new Exception("Failed to update complaint: " . $e->getMessage());
        }
    }
    
    /**
     * Delete complaint
     */
    public function delete($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get all complaints with pagination and filtering
     */
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $connection = $this->db->getConnection();
        
        $offset = ($page - 1) * $limit;
        $where = ["1=1"];
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $where[] = "c.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['complaint_type'])) {
            $where[] = "c.complaint_type = ?";
            $params[] = $filters['complaint_type'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $where[] = "c.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(c.complainant_name LIKE ? OR c.complaint_number LIKE ? OR c.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(c.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(c.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $connection->prepare("
            SELECT COUNT(*) FROM {$this->table} c WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get complaints
        $stmt = $connection->prepare("
            SELECT c.*, u.name as assigned_to_name
            FROM {$this->table} c
            LEFT JOIN users u ON c.assigned_to = u.id
            WHERE {$whereClause}
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        $complaints = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($complaints as &$complaint) {
            if ($complaint['evidence_files']) {
                $complaint['evidence_files'] = json_decode($complaint['evidence_files'], true);
            }
        }
        
        return [
            'complaints' => $complaints,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Assign complaint to user
     */
    public function assign($complaintId, $userId) {
        return $this->update($complaintId, ['assigned_to' => $userId]);
    }
    
    /**
     * Update complaint status
     */
    public function updateStatus($complaintId, $status, $resolution = null) {
        $data = ['status' => $status];
        
        if ($status === 'resolved' || $status === 'closed') {
            $data['resolved_at'] = date('Y-m-d H:i:s');
            if ($resolution) {
                $data['resolution'] = $resolution;
            }
        }
        
        return $this->update($complaintId, $data);
    }
    
    /**
     * Get complaint statistics
     */
    public function getStats() {
        $connection = $this->db->getConnection();
        
        $stats = [];
        
        // Total complaints
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Complaints by status
        $stmt = $connection->query("
            SELECT status, COUNT(*) as count 
            FROM {$this->table} 
            GROUP BY status
        ");
        $stats['by_status'] = $stmt->fetchAll();
        
        // Complaints by priority
        $stmt = $connection->query("
            SELECT priority, COUNT(*) as count 
            FROM {$this->table} 
            GROUP BY priority
        ");
        $stats['by_priority'] = $stmt->fetchAll();
        
        // Complaints by type
        $stmt = $connection->query("
            SELECT complaint_type, COUNT(*) as count 
            FROM {$this->table} 
            GROUP BY complaint_type
            ORDER BY count DESC
            LIMIT 10
        ");
        $stats['by_type'] = $stmt->fetchAll();
        
        // Recent complaints (last 30 days)
        $stmt = $connection->query("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['recent'] = $stmt->fetchColumn();
        
        // Resolved complaints (last 30 days)
        $stmt = $connection->query("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE status IN ('resolved', 'closed') 
            AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['resolved_recent'] = $stmt->fetchColumn();
        
        // Average resolution time (in days)
        $stmt = $connection->query("
            SELECT AVG(DATEDIFF(resolved_at, created_at)) as avg_resolution_days
            FROM {$this->table} 
            WHERE status IN ('resolved', 'closed') 
            AND resolved_at IS NOT NULL
        ");
        $stats['avg_resolution_days'] = round($stmt->fetchColumn(), 1);
        
        return $stats;
    }
    
    /**
     * Get complaints by complainant email
     */
    public function getByComplainantEmail($email) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT complaint_number, complainant_name, complaint_type, status, 
                   priority, created_at, updated_at
            FROM {$this->table} 
            WHERE complainant_email = ?
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }
    
    /**
     * Generate unique complaint number
     */
    private function generateComplaintNumber() {
        $prefix = 'BHRC';
        $year = date('Y');
        $month = date('m');
        
        $connection = $this->db->getConnection();
        
        // Get the last complaint number for this month
        $stmt = $connection->prepare("
            SELECT complaint_number 
            FROM {$this->table} 
            WHERE complaint_number LIKE ? 
            ORDER BY complaint_number DESC 
            LIMIT 1
        ");
        
        $pattern = "{$prefix}{$year}{$month}%";
        $stmt->execute([$pattern]);
        $lastComplaint = $stmt->fetch();
        
        if ($lastComplaint) {
            // Extract the sequence number and increment
            $lastNumber = substr($lastComplaint['complaint_number'], -4);
            $sequence = str_pad((int)$lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // First complaint of the month
            $sequence = '0001';
        }
        
        return "{$prefix}{$year}{$month}{$sequence}";
    }
    
    /**
     * Get complaint types
     */
    public function getComplaintTypes() {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->query("
            SELECT DISTINCT complaint_type, COUNT(*) as count
            FROM {$this->table} 
            GROUP BY complaint_type
            ORDER BY count DESC
        ");
        
        return $stmt->fetchAll();
    }
    
    /**
     * Search complaints
     */
    public function search($query, $limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT complaint_number, complainant_name, complaint_type, status, created_at
            FROM {$this->table} 
            WHERE complainant_name LIKE ? 
               OR complaint_number LIKE ? 
               OR description LIKE ?
               OR complaint_type LIKE ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get total count of complaints
     */
    public function getTotalCount() {
        $connection = $this->db->getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Get count of resolved complaints
     */
    public function getResolvedCount() {
        $connection = $this->db->getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = 'resolved'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}

?>