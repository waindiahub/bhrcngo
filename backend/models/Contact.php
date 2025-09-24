<?php
/**
 * Contact Model
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/BaseModel.php';

class Contact extends BaseModel {
    protected $table = 'contact_inquiries';
    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message', 'category',
        'status', 'priority', 'assigned_to', 'response', 'responded_at',
        'responded_by', 'ip_address', 'user_agent', 'source'
    ];
    
    /**
     * Submit new contact inquiry
     */
    public function submitInquiry($data) {
        $errors = $this->validate($data, [
            'name' => 'required|max:100',
            'email' => 'required|email|max:255',
            'subject' => 'required|max:255',
            'message' => 'required|max:2000'
        ]);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Sanitize and prepare data
        $inquiryData = [
            'name' => htmlspecialchars(trim($data['name'])),
            'email' => filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL),
            'phone' => isset($data['phone']) ? htmlspecialchars(trim($data['phone'])) : null,
            'subject' => htmlspecialchars(trim($data['subject'])),
            'message' => htmlspecialchars(trim($data['message'])),
            'category' => $data['category'] ?? 'general',
            'status' => 'new',
            'priority' => $this->determinePriority($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'source' => $data['source'] ?? 'website'
        ];
        
        $inquiry = $this->create($inquiryData);
        
        if ($inquiry) {
            // Send notification to admin
            $this->notifyAdmin($inquiry);
            
            return ['success' => true, 'inquiry' => $inquiry];
        }
        
        return ['success' => false, 'message' => 'Failed to submit inquiry'];
    }
    
    /**
     * Get inquiries with filters
     */
    public function getInquiries($filters = [], $limit = 20, $page = 1) {
        $offset = ($page - 1) * $limit;
        $conditions = [];
        $params = [];
        
        // Build WHERE conditions
        if (!empty($filters['status'])) {
            $conditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $conditions[] = "category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $conditions[] = "assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} 
                ORDER BY 
                    CASE priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                    END,
                    created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        return dbFetchAll($sql, $params);
    }
    
    /**
     * Respond to inquiry
     */
    public function respondToInquiry($inquiryId, $response, $respondedBy) {
        $inquiry = $this->find($inquiryId);
        
        if (!$inquiry) {
            return ['success' => false, 'message' => 'Inquiry not found'];
        }
        
        $updateData = [
            'response' => htmlspecialchars(trim($response)),
            'responded_at' => date('Y-m-d H:i:s'),
            'responded_by' => $respondedBy,
            'status' => 'responded'
        ];
        
        $updated = $this->update($inquiryId, $updateData);
        
        if ($updated) {
            // Send response email to user
            $this->sendResponseEmail($inquiry, $response);
            
            return ['success' => true, 'message' => 'Response sent successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to send response'];
    }
    
    /**
     * Update inquiry status
     */
    public function updateStatus($inquiryId, $status, $assignedTo = null) {
        $validStatuses = ['new', 'in_progress', 'responded', 'closed', 'spam'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        $updateData = ['status' => $status];
        
        if ($assignedTo !== null) {
            $updateData['assigned_to'] = $assignedTo;
        }
        
        $updated = $this->update($inquiryId, $updateData);
        
        if ($updated) {
            return ['success' => true, 'message' => 'Status updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update status'];
    }
    
    /**
     * Get contact statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total inquiries
        $stats['total'] = $this->count();
        
        // By status
        $statusStats = $this->getStatusStats();
        $stats['by_status'] = $statusStats;
        
        // By priority
        $priorityStats = $this->getPriorityStats();
        $stats['by_priority'] = $priorityStats;
        
        // By category
        $categoryStats = $this->getCategoryStats();
        $stats['by_category'] = $categoryStats;
        
        // This month
        $thisMonth = date('Y-m-01');
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at >= ?";
        $result = dbFetch($sql, [$thisMonth]);
        $stats['this_month'] = $result['count'] ?? 0;
        
        // Response time average (in hours)
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, responded_at)) as avg_response_time 
                FROM {$this->table} 
                WHERE responded_at IS NOT NULL";
        $result = dbFetch($sql);
        $stats['avg_response_time'] = round($result['avg_response_time'] ?? 0, 2);
        
        // Pending inquiries
        $stats['pending'] = $this->count(['status' => ['new', 'in_progress']]);
        
        return $stats;
    }
    
    /**
     * Get status statistics
     */
    private function getStatusStats() {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $results = dbFetchAll($sql);
        
        $stats = [];
        foreach ($results as $result) {
            $stats[$result['status']] = $result['count'];
        }
        
        return $stats;
    }
    
    /**
     * Get priority statistics
     */
    private function getPriorityStats() {
        $sql = "SELECT priority, COUNT(*) as count FROM {$this->table} GROUP BY priority";
        $results = dbFetchAll($sql);
        
        $stats = [];
        foreach ($results as $result) {
            $stats[$result['priority']] = $result['count'];
        }
        
        return $stats;
    }
    
    /**
     * Get category statistics
     */
    private function getCategoryStats() {
        $sql = "SELECT category, COUNT(*) as count FROM {$this->table} GROUP BY category";
        $results = dbFetchAll($sql);
        
        $stats = [];
        foreach ($results as $result) {
            $stats[$result['category']] = $result['count'];
        }
        
        return $stats;
    }
    
    /**
     * Determine priority based on inquiry data
     */
    private function determinePriority($data) {
        $urgentKeywords = ['urgent', 'emergency', 'immediate', 'asap', 'critical'];
        $highKeywords = ['important', 'serious', 'complaint', 'issue', 'problem'];
        
        $text = strtolower($data['subject'] . ' ' . $data['message']);
        
        foreach ($urgentKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return 'urgent';
            }
        }
        
        foreach ($highKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return 'high';
            }
        }
        
        return 'medium';
    }
    
    /**
     * Send notification to admin
     */
    private function notifyAdmin($inquiry) {
        $subject = "New Contact Inquiry: " . $inquiry['subject'];
        $message = "
            <h3>New Contact Inquiry Received</h3>
            <p><strong>Name:</strong> {$inquiry['name']}</p>
            <p><strong>Email:</strong> {$inquiry['email']}</p>
            <p><strong>Phone:</strong> {$inquiry['phone']}</p>
            <p><strong>Subject:</strong> {$inquiry['subject']}</p>
            <p><strong>Category:</strong> {$inquiry['category']}</p>
            <p><strong>Priority:</strong> {$inquiry['priority']}</p>
            <p><strong>Message:</strong></p>
            <p>{$inquiry['message']}</p>
            <p><strong>Submitted:</strong> {$inquiry['created_at']}</p>
        ";
        
        $headers = [
            'From: BHRC Website <noreply@bhrcindia.in>',
            'Reply-To: ' . $inquiry['email'],
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        // Send to admin email
        mail('admin@bhrcindia.in', $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Send response email to user
     */
    private function sendResponseEmail($inquiry, $response) {
        $subject = "Re: " . $inquiry['subject'];
        $message = "
            <h3>Response to Your Inquiry</h3>
            <p>Dear {$inquiry['name']},</p>
            <p>Thank you for contacting BHRC. Here is our response to your inquiry:</p>
            <div style='background: #f5f5f5; padding: 15px; margin: 15px 0; border-left: 4px solid #007cba;'>
                {$response}
            </div>
            <p>If you have any further questions, please don't hesitate to contact us.</p>
            <p>Best regards,<br>BHRC Team</p>
            <hr>
            <p><small>Your original message: {$inquiry['message']}</small></p>
        ";
        
        $headers = [
            'From: BHRC <noreply@bhrcindia.in>',
            'Reply-To: admin@bhrcindia.in',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        mail($inquiry['email'], $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Get recent inquiries
     */
    public function getRecentInquiries($limit = 10) {
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY created_at DESC 
                LIMIT {$limit}";
        
        return dbFetchAll($sql);
    }
    
    /**
     * Mark inquiry as spam
     */
    public function markAsSpam($inquiryId) {
        return $this->updateStatus($inquiryId, 'spam');
    }
    
    /**
     * Get inquiries by category
     */
    public function getByCategory($category, $limit = 20) {
        return $this->findAll(['category' => $category], 'created_at DESC', $limit);
    }
    
    /**
     * Export inquiries
     */
    public function exportInquiries($filters = [], $format = 'csv') {
        $inquiries = $this->getInquiries($filters, 1000); // Get up to 1000 records
        
        switch ($format) {
            case 'json':
                return json_encode($inquiries);
            case 'csv':
            default:
                return $this->convertToCSV($inquiries);
        }
    }
    
    /**
     * Convert inquiries to CSV
     */
    private function convertToCSV($inquiries) {
        if (empty($inquiries)) {
            return '';
        }
        
        $csv = "Name,Email,Phone,Subject,Category,Status,Priority,Created At,Responded At\n";
        
        foreach ($inquiries as $inquiry) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $inquiry['name'],
                $inquiry['email'],
                $inquiry['phone'] ?? '',
                str_replace(',', ';', $inquiry['subject']),
                $inquiry['category'],
                $inquiry['status'],
                $inquiry['priority'],
                $inquiry['created_at'],
                $inquiry['responded_at'] ?? ''
            );
        }
        
        return $csv;
    }
    
    /**
     * Create new inquiry (alias for submitInquiry)
     */
    public function createInquiry($data) {
        return $this->submitInquiry($data);
    }
    
    /**
     * Get count of inquiries with optional filters
     */
    public function getInquiriesCount($status = null, $search = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        $conditions = [];
        
        if ($status && $status !== 'all') {
            $conditions[] = "status = ?";
            $params[] = $status;
        }
        
        if ($search) {
            $conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $result = dbFetch($sql, $params);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Get inquiry by ID
     */
    public function getInquiryById($id) {
        return $this->find($id);
    }
    
    /**
     * Update inquiry
     */
    public function updateInquiry($id, $data) {
        return $this->update($id, $data);
    }
    
    /**
     * Get inquiry responses
     */
    public function getInquiryResponses($inquiryId) {
        $sql = "SELECT cr.*, u.name as responder_name 
                FROM contact_responses cr 
                LEFT JOIN users u ON cr.responded_by = u.id 
                WHERE cr.inquiry_id = ? 
                ORDER BY cr.created_at ASC";
        
        return dbFetchAll($sql, [$inquiryId]);
    }
    
    /**
     * Create response for inquiry
     */
    public function createResponse($data) {
        $responseData = [
            'inquiry_id' => $data['inquiry_id'],
            'response' => htmlspecialchars(trim($data['response'])),
            'responded_by' => $data['responded_by'],
            'response_type' => $data['response_type'] ?? 'reply',
            'is_internal' => $data['is_internal'] ?? false,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO contact_responses (inquiry_id, response, responded_by, response_type, is_internal, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $responseData['inquiry_id'],
            $responseData['response'],
            $responseData['responded_by'],
            $responseData['response_type'],
            $responseData['is_internal'],
            $responseData['created_at']
        ];
        
        if (dbExecute($sql, $params)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
}
?>