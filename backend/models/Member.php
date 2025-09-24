<?php
/**
 * Member Model
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/BaseModel.php';

class Member extends BaseModel {
    protected $table = 'members';
    protected $fillable = [
        'user_id', 'membership_id', 'membership_type', 'full_name', 'father_name',
        'date_of_birth', 'gender', 'address', 'city', 'state', 'pincode',
        'phone', 'email', 'occupation', 'qualification', 'aadhar_number',
        'pan_number', 'photo', 'signature', 'id_proof', 'address_proof',
        'status', 'approved_by', 'approved_at', 'membership_fee', 'payment_status',
        'payment_date', 'certificate_issued', 'certificate_number'
    ];
    
    /**
     * Create new membership application
     */
    public function createMembership($data) {
        // Generate unique membership ID
        $data['membership_id'] = $this->generateMembershipId();
        
        // Validate required fields
        $errors = $this->validate($data, [
            'full_name' => 'required|max:255',
            'father_name' => 'required|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|max:500',
            'city' => 'required|max:100',
            'state' => 'required|max:100',
            'pincode' => 'required|max:10',
            'phone' => 'required|max:20',
            'email' => 'required|email|max:255',
            'occupation' => 'required|max:255',
            'qualification' => 'required|max:255',
            'aadhar_number' => 'required|max:12',
            'membership_type' => 'required|in:regular,life,patron,honorary'
        ]);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if Aadhar number already exists
        if ($this->findByAadhar($data['aadhar_number'])) {
            return ['success' => false, 'errors' => ['aadhar_number' => 'Aadhar number already registered']];
        }
        
        // Set default values
        $data['status'] = 'pending';
        $data['payment_status'] = 'pending';
        
        $member = $this->create($data);
        
        if ($member) {
            return ['success' => true, 'member' => $member, 'membership_id' => $data['membership_id']];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to create membership application']];
    }
    
    /**
     * Generate unique membership ID
     */
    private function generateMembershipId() {
        $year = date('Y');
        $prefix = "BHRC{$year}";
        
        // Get the last membership ID for this year
        $sql = "SELECT membership_id FROM {$this->table} WHERE membership_id LIKE ? ORDER BY id DESC LIMIT 1";
        $result = dbFetch($sql, ["{$prefix}%"]);
        
        if ($result) {
            $lastNumber = (int)substr($result['membership_id'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Find member by Aadhar number
     */
    public function findByAadhar($aadharNumber) {
        $sql = "SELECT * FROM {$this->table} WHERE aadhar_number = ?";
        return dbFetch($sql, [$aadharNumber]);
    }
    
    /**
     * Find member by membership ID
     */
    public function findByMembershipId($membershipId) {
        $sql = "SELECT * FROM {$this->table} WHERE membership_id = ?";
        return dbFetch($sql, [$membershipId]);
    }
    
    /**
     * Approve membership
     */
    public function approveMembership($memberId, $approvedBy, $certificateNumber = null) {
        $data = [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s')
        ];
        
        if ($certificateNumber) {
            $data['certificate_number'] = $certificateNumber;
            $data['certificate_issued'] = 1;
        }
        
        return $this->update($memberId, $data);
    }
    
    /**
     * Reject membership
     */
    public function rejectMembership($memberId, $rejectedBy, $reason = null) {
        $data = [
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason
        ];
        
        return $this->update($memberId, $data);
    }
    
    /**
     * Update payment status
     */
    public function updatePaymentStatus($memberId, $status, $paymentDate = null, $transactionId = null) {
        $data = [
            'payment_status' => $status,
            'payment_date' => $paymentDate ?: date('Y-m-d H:i:s')
        ];
        
        if ($transactionId) {
            $data['transaction_id'] = $transactionId;
        }
        
        return $this->update($memberId, $data);
    }
    
    /**
     * Issue certificate
     */
    public function issueCertificate($memberId, $certificateNumber) {
        $data = [
            'certificate_issued' => 1,
            'certificate_number' => $certificateNumber,
            'certificate_issued_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($memberId, $data);
    }
    
    /**
     * Get members by status
     */
    public function getMembersByStatus($status) {
        return $this->findAll(['status' => $status], 'created_at DESC');
    }
    
    /**
     * Get pending applications
     */
    public function getPendingApplications($limit = 50) {
        return $this->findAll(['status' => 'pending'], 'created_at ASC', $limit);
    }
    
    /**
     * Get membership statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total members
        $stats['total_members'] = $this->count();
        
        // Members by status
        $statuses = ['pending', 'approved', 'rejected', 'suspended'];
        foreach ($statuses as $status) {
            $stats['members_by_status'][$status] = $this->count(['status' => $status]);
        }
        
        // Members by type
        $types = ['regular', 'life', 'patron', 'honorary'];
        foreach ($types as $type) {
            $stats['members_by_type'][$type] = $this->count(['membership_type' => $type, 'status' => 'approved']);
        }
        
        // Payment statistics
        $stats['payment_pending'] = $this->count(['payment_status' => 'pending']);
        $stats['payment_completed'] = $this->count(['payment_status' => 'completed']);
        
        // Recent applications (last 30 days)
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = dbFetch($sql);
        $stats['recent_applications'] = $result ? (int)$result['count'] : 0;
        
        // Certificates issued
        $stats['certificates_issued'] = $this->count(['certificate_issued' => 1]);
        
        return $stats;
    }
    
    /**
     * Search members
     */
    public function search($query, $filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE (full_name LIKE ? OR membership_id LIKE ? OR email LIKE ? OR phone LIKE ? OR aadhar_number LIKE ?)";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%", "%{$query}%"];
        
        // Apply filters
        if (isset($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['membership_type'])) {
            $sql .= " AND membership_type = ?";
            $params[] = $filters['membership_type'];
        }
        
        if (isset($filters['state'])) {
            $sql .= " AND state = ?";
            $params[] = $filters['state'];
        }
        
        if (isset($filters['payment_status'])) {
            $sql .= " AND payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        $sql .= " ORDER BY full_name ASC";
        
        return dbFetchAll($sql, $params);
    }
    
    /**
     * Get member profile with user details
     */
    public function getMemberProfile($memberId) {
        $sql = "SELECT m.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
                FROM {$this->table} m 
                LEFT JOIN users u ON m.user_id = u.id 
                WHERE m.id = ?";
        
        return dbFetch($sql, [$memberId]);
    }
    
    /**
     * Get members for certificate generation
     */
    public function getMembersForCertificate() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'approved' 
                AND payment_status = 'completed' 
                AND certificate_issued = 0 
                ORDER BY approved_at ASC";
        
        return dbFetchAll($sql);
    }
    
    /**
     * Bulk update member status
     */
    public function bulkUpdateStatus($memberIds, $status, $updatedBy) {
        if (empty($memberIds) || !in_array($status, ['approved', 'rejected', 'suspended', 'active'])) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($memberIds) - 1) . '?';
        $sql = "UPDATE {$this->table} SET status = ?, approved_by = ?, approved_at = NOW(), updated_at = NOW() WHERE id IN ({$placeholders})";
        
        $params = array_merge([$status, $updatedBy], $memberIds);
        
        try {
            $stmt = dbQuery($sql, $params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Bulk member status update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get membership renewal candidates
     */
    public function getRenewalCandidates() {
        // Members whose membership expires in next 30 days (for life members, check after 5 years)
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'approved' 
                AND (
                    (membership_type = 'regular' AND approved_at <= DATE_SUB(NOW(), INTERVAL 11 MONTH))
                    OR (membership_type = 'life' AND approved_at <= DATE_SUB(NOW(), INTERVAL 4 YEAR 11 MONTH))
                )
                ORDER BY approved_at ASC";
        
        return dbFetchAll($sql);
    }
    
    /**
     * Export members data
     */
    public function exportMembers($filters = []) {
        $sql = "SELECT membership_id, full_name, father_name, date_of_birth, gender, 
                       address, city, state, pincode, phone, email, occupation, 
                       qualification, membership_type, status, payment_status, 
                       approved_at, certificate_number 
                FROM {$this->table} WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (isset($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['membership_type'])) {
            $sql .= " AND membership_type = ?";
            $params[] = $filters['membership_type'];
        }
        
        if (isset($filters['state'])) {
            $sql .= " AND state = ?";
            $params[] = $filters['state'];
        }
        
        $sql .= " ORDER BY full_name ASC";
        
        return dbFetchAll($sql, $params);
    }
    
    /**
     * Get total count of members
     */
    public function getTotalCount() {
        return $this->count();
    }
    
    /**
     * Get count of approved members
     */
    public function getApprovedCount() {
        return $this->count(['status' => 'approved']);
    }
}
?>