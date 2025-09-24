<?php

require_once __DIR__ . '/../config/Database.php';

class Donation {
    private $db;
    private $table = 'donations';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new donation
     */
    public function create($data) {
        $connection = $this->db->getConnection();
        
        try {
            // Validate required fields
            $required = ['donor_name', 'amount', 'donation_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }
            
            // Generate unique donation ID
            $data['donation_id'] = $this->generateDonationId();
            
            // Set default values
            $data['status'] = $data['status'] ?? 'pending';
            $data['donation_date'] = $data['donation_date'] ?? date('Y-m-d H:i:s');
            $data['currency'] = $data['currency'] ?? 'INR';
            $data['tax_exemption'] = $data['tax_exemption'] ?? true;
            
            // Handle JSON fields
            if (isset($data['payment_details']) && is_array($data['payment_details'])) {
                $data['payment_details'] = json_encode($data['payment_details']);
            }
            if (isset($data['donor_details']) && is_array($data['donor_details'])) {
                $data['donor_details'] = json_encode($data['donor_details']);
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
            throw new Exception("Failed to create donation: " . $e->getMessage());
        }
    }
    
    /**
     * Get donation by ID
     */
    public function getById($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT d.*, u.name as processed_by_name
            FROM {$this->table} d
            LEFT JOIN users u ON d.processed_by = u.id
            WHERE d.id = ?
        ");
        
        $stmt->execute([$id]);
        $donation = $stmt->fetch();
        
        if ($donation) {
            // Decode JSON fields
            if ($donation['payment_details']) {
                $donation['payment_details'] = json_decode($donation['payment_details'], true);
            }
            if ($donation['donor_details']) {
                $donation['donor_details'] = json_decode($donation['donor_details'], true);
            }
        }
        
        return $donation;
    }
    
    /**
     * Get donation by donation ID
     */
    public function getByDonationId($donationId) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT d.*, u.name as processed_by_name
            FROM {$this->table} d
            LEFT JOIN users u ON d.processed_by = u.id
            WHERE d.donation_id = ?
        ");
        
        $stmt->execute([$donationId]);
        $donation = $stmt->fetch();
        
        if ($donation) {
            // Decode JSON fields
            if ($donation['payment_details']) {
                $donation['payment_details'] = json_decode($donation['payment_details'], true);
            }
            if ($donation['donor_details']) {
                $donation['donor_details'] = json_decode($donation['donor_details'], true);
            }
        }
        
        return $donation;
    }
    
    /**
     * Update donation
     */
    public function update($id, $data) {
        $connection = $this->db->getConnection();
        
        try {
            // Remove fields that shouldn't be updated directly
            unset($data['id'], $data['donation_id'], $data['created_at']);
            
            // Handle JSON fields
            if (isset($data['payment_details']) && is_array($data['payment_details'])) {
                $data['payment_details'] = json_encode($data['payment_details']);
            }
            if (isset($data['donor_details']) && is_array($data['donor_details'])) {
                $data['donor_details'] = json_encode($data['donor_details']);
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
            throw new Exception("Failed to update donation: " . $e->getMessage());
        }
    }
    
    /**
     * Delete donation (soft delete)
     */
    public function delete($id) {
        return $this->update($id, ['status' => 'cancelled']);
    }
    
    /**
     * Hard delete donation
     */
    public function hardDelete($id) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get all donations with pagination and filtering
     */
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $connection = $this->db->getConnection();
        
        $offset = ($page - 1) * $limit;
        $where = ["status != 'deleted'"];
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['donation_type'])) {
            $where[] = "donation_type = ?";
            $params[] = $filters['donation_type'];
        }
        
        if (!empty($filters['payment_method'])) {
            $where[] = "payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        
        if (!empty($filters['currency'])) {
            $where[] = "currency = ?";
            $params[] = $filters['currency'];
        }
        
        if (!empty($filters['min_amount'])) {
            $where[] = "amount >= ?";
            $params[] = $filters['min_amount'];
        }
        
        if (!empty($filters['max_amount'])) {
            $where[] = "amount <= ?";
            $params[] = $filters['max_amount'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(donor_name LIKE ? OR donor_email LIKE ? OR donation_id LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "donation_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "donation_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['tax_exemption'])) {
            $where[] = "tax_exemption = ?";
            $params[] = $filters['tax_exemption'] === 'true' ? 1 : 0;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countStmt = $connection->prepare("
            SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Get donations
        $stmt = $connection->prepare("
            SELECT d.*, u.name as processed_by_name
            FROM {$this->table} d
            LEFT JOIN users u ON d.processed_by = u.id
            WHERE {$whereClause}
            ORDER BY d.donation_date DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        
        $donations = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($donations as &$donation) {
            if ($donation['payment_details']) {
                $donation['payment_details'] = json_decode($donation['payment_details'], true);
            }
            if ($donation['donor_details']) {
                $donation['donor_details'] = json_decode($donation['donor_details'], true);
            }
        }
        
        return [
            'donations' => $donations,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get recent donations
     */
    public function getRecent($limit = 10) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT d.*, u.name as processed_by_name
            FROM {$this->table} d
            LEFT JOIN users u ON d.processed_by = u.id
            WHERE d.status = 'completed'
            ORDER BY d.donation_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        $donations = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($donations as &$donation) {
            if ($donation['payment_details']) {
                $donation['payment_details'] = json_decode($donation['payment_details'], true);
            }
            if ($donation['donor_details']) {
                $donation['donor_details'] = json_decode($donation['donor_details'], true);
            }
        }
        
        return $donations;
    }
    
    /**
     * Get donations by donor email
     */
    public function getByDonorEmail($email, $limit = 20) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT d.*, u.name as processed_by_name
            FROM {$this->table} d
            LEFT JOIN users u ON d.processed_by = u.id
            WHERE d.donor_email = ?
            ORDER BY d.donation_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$email, $limit]);
        $donations = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($donations as &$donation) {
            if ($donation['payment_details']) {
                $donation['payment_details'] = json_decode($donation['payment_details'], true);
            }
            if ($donation['donor_details']) {
                $donation['donor_details'] = json_decode($donation['donor_details'], true);
            }
        }
        
        return $donations;
    }
    
    /**
     * Get donation statistics
     */
    public function getStats() {
        $connection = $this->db->getConnection();
        
        $stats = [];
        
        // Total donations
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table} WHERE status != 'deleted'");
        $stats['total_donations'] = $stmt->fetchColumn();
        
        // Completed donations
        $stmt = $connection->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'completed'");
        $stats['completed_donations'] = $stmt->fetchColumn();
        
        // Total amount raised
        $stmt = $connection->query("SELECT SUM(amount) FROM {$this->table} WHERE status = 'completed'");
        $stats['total_amount'] = $stmt->fetchColumn() ?: 0;
        
        // Average donation amount
        $stmt = $connection->query("SELECT AVG(amount) FROM {$this->table} WHERE status = 'completed'");
        $stats['average_amount'] = round($stmt->fetchColumn() ?: 0, 2);
        
        // Donations by type
        $stmt = $connection->query("
            SELECT donation_type, COUNT(*) as count, SUM(amount) as total_amount
            FROM {$this->table} 
            WHERE status = 'completed'
            GROUP BY donation_type
            ORDER BY total_amount DESC
        ");
        $stats['by_type'] = $stmt->fetchAll();
        
        // Donations by payment method
        $stmt = $connection->query("
            SELECT payment_method, COUNT(*) as count, SUM(amount) as total_amount
            FROM {$this->table} 
            WHERE status = 'completed' AND payment_method IS NOT NULL
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ");
        $stats['by_payment_method'] = $stmt->fetchAll();
        
        // Monthly donations (last 12 months)
        $stmt = $connection->query("
            SELECT 
                DATE_FORMAT(donation_date, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(amount) as total_amount
            FROM {$this->table} 
            WHERE donation_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            AND status = 'completed'
            GROUP BY DATE_FORMAT(donation_date, '%Y-%m')
            ORDER BY month DESC
        ");
        $stats['monthly'] = $stmt->fetchAll();
        
        // Recent donations (last 30 days)
        $stmt = $connection->query("
            SELECT COUNT(*) FROM {$this->table} 
            WHERE donation_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND status = 'completed'
        ");
        $stats['recent_count'] = $stmt->fetchColumn();
        
        // Recent amount (last 30 days)
        $stmt = $connection->query("
            SELECT SUM(amount) FROM {$this->table} 
            WHERE donation_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND status = 'completed'
        ");
        $stats['recent_amount'] = $stmt->fetchColumn() ?: 0;
        
        // Top donors
        $stmt = $connection->query("
            SELECT donor_name, donor_email, COUNT(*) as donation_count, SUM(amount) as total_amount
            FROM {$this->table} 
            WHERE status = 'completed'
            GROUP BY donor_email
            ORDER BY total_amount DESC
            LIMIT 10
        ");
        $stats['top_donors'] = $stmt->fetchAll();
        
        return $stats;
    }
    
    /**
     * Process payment callback
     */
    public function processPaymentCallback($donationId, $paymentData) {
        $connection = $this->db->getConnection();
        
        try {
            $connection->beginTransaction();
            
            // Get donation
            $donation = $this->getByDonationId($donationId);
            if (!$donation) {
                throw new Exception("Donation not found");
            }
            
            // Update payment details
            $updateData = [
                'payment_id' => $paymentData['payment_id'] ?? null,
                'payment_status' => $paymentData['status'] ?? 'pending',
                'payment_details' => json_encode($paymentData),
                'processed_at' => date('Y-m-d H:i:s')
            ];
            
            // Update status based on payment status
            if ($paymentData['status'] === 'success') {
                $updateData['status'] = 'completed';
            } elseif ($paymentData['status'] === 'failed') {
                $updateData['status'] = 'failed';
            }
            
            $this->update($donation['id'], $updateData);
            
            $connection->commit();
            
            return true;
            
        } catch (Exception $e) {
            $connection->rollBack();
            throw new Exception("Payment processing failed: " . $e->getMessage());
        }
    }
    
    /**
     * Generate unique donation ID
     */
    private function generateDonationId() {
        $prefix = 'BHRC';
        $year = date('Y');
        $month = date('m');
        
        // Get the last donation ID for this month
        $connection = $this->db->getConnection();
        $stmt = $connection->prepare("
            SELECT donation_id FROM {$this->table} 
            WHERE donation_id LIKE ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        
        $pattern = $prefix . $year . $month . '%';
        $stmt->execute([$pattern]);
        $lastId = $stmt->fetchColumn();
        
        if ($lastId) {
            // Extract the sequence number and increment
            $sequence = intval(substr($lastId, -4)) + 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate donation receipt
     */
    public function generateReceipt($donationId) {
        $donation = $this->getByDonationId($donationId);
        
        if (!$donation || $donation['status'] !== 'completed') {
            throw new Exception("Invalid donation for receipt generation");
        }
        
        // Receipt data
        $receipt = [
            'donation_id' => $donation['donation_id'],
            'donor_name' => $donation['donor_name'],
            'donor_email' => $donation['donor_email'],
            'amount' => $donation['amount'],
            'currency' => $donation['currency'],
            'donation_date' => $donation['donation_date'],
            'donation_type' => $donation['donation_type'],
            'tax_exemption' => $donation['tax_exemption'],
            'receipt_number' => $this->generateReceiptNumber($donation['id']),
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        // Update donation with receipt number
        $this->update($donation['id'], [
            'receipt_number' => $receipt['receipt_number'],
            'receipt_generated' => true
        ]);
        
        return $receipt;
    }
    
    /**
     * Generate receipt number
     */
    private function generateReceiptNumber($donationId) {
        return 'RCPT' . date('Y') . str_pad($donationId, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Search donations
     */
    public function search($query, $limit = 20) {
        $connection = $this->db->getConnection();
        
        $stmt = $connection->prepare("
            SELECT d.*, u.name as processed_by_name
            FROM {$this->table} d
            LEFT JOIN users u ON d.processed_by = u.id
            WHERE (d.donor_name LIKE ? OR d.donor_email LIKE ? OR d.donation_id LIKE ?)
            AND d.status != 'deleted'
            ORDER BY d.donation_date DESC
            LIMIT ?
        ");
        
        $searchTerm = '%' . $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit]);
        
        $donations = $stmt->fetchAll();
        
        // Decode JSON fields
        foreach ($donations as &$donation) {
            if ($donation['payment_details']) {
                $donation['payment_details'] = json_decode($donation['payment_details'], true);
            }
            if ($donation['donor_details']) {
                $donation['donor_details'] = json_decode($donation['donor_details'], true);
            }
        }
        
        return $donations;
    }
    
    /**
     * Get donation summary for dashboard
     */
    public function getDashboardSummary() {
        $connection = $this->db->getConnection();
        
        $summary = [];
        
        // Today's donations
        $stmt = $connection->query("
            SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total
            FROM {$this->table} 
            WHERE DATE(donation_date) = CURDATE() AND status = 'completed'
        ");
        $today = $stmt->fetch();
        $summary['today'] = $today;
        
        // This month's donations
        $stmt = $connection->query("
            SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total
            FROM {$this->table} 
            WHERE MONTH(donation_date) = MONTH(CURDATE()) 
            AND YEAR(donation_date) = YEAR(CURDATE()) 
            AND status = 'completed'
        ");
        $thisMonth = $stmt->fetch();
        $summary['this_month'] = $thisMonth;
        
        // This year's donations
        $stmt = $connection->query("
            SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total
            FROM {$this->table} 
            WHERE YEAR(donation_date) = YEAR(CURDATE()) 
            AND status = 'completed'
        ");
        $thisYear = $stmt->fetch();
        $summary['this_year'] = $thisYear;
        
        // Pending donations
        $stmt = $connection->query("
            SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending'
        ");
        $summary['pending'] = $stmt->fetchColumn();
        
        return $summary;
    }
    
    /**
     * Get total count of donations
     */
    public function getTotalCount() {
        $connection = $this->db->getConnection();
        $stmt = $connection->prepare("SELECT COUNT(*) FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Get total amount of donations
     */
    public function getTotalAmount() {
        $connection = $this->db->getConnection();
        $stmt = $connection->prepare("SELECT COALESCE(SUM(amount), 0) FROM {$this->table} WHERE status = 'completed'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}

?>