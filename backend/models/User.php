<?php
/**
 * User Model
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    protected $fillable = [
        'name', 'email', 'phone', 'password_hash', 'role', 'status',
        'email_verified', 'phone_verified', 'profile_image'
    ];
    protected $hidden = ['password_hash'];
    
    /**
     * Create a new user with password hashing
     */
    public function createUser($data) {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }
        
        // Validate required fields
        $errors = $this->validate($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|max:20',
            'password_hash' => 'required'
        ]);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if email already exists
        if ($this->findByEmail($data['email'])) {
            return ['success' => false, 'errors' => ['email' => 'Email already exists']];
        }
        
        // Check if phone already exists
        if ($this->findByPhone($data['phone'])) {
            return ['success' => false, 'errors' => ['phone' => 'Phone number already exists']];
        }
        
        $user = $this->create($data);
        
        if ($user) {
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to create user']];
    }
    
    /**
     * Authenticate user login
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Check if account is locked
        if ($this->isAccountLocked($user)) {
            return ['success' => false, 'message' => 'Account is temporarily locked due to multiple failed attempts'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->incrementLoginAttempts($user['id']);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Check account status
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }
        
        // Reset login attempts and update last login
        $this->resetLoginAttempts($user['id']);
        $this->updateLastLogin($user['id']);
        
        // Remove sensitive data
        unset($user['password_hash']);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $result = dbFetch($sql, [$email]);
        return $result ? $this->hideFields($result) : null;
    }
    
    /**
     * Find user by phone
     */
    public function findByPhone($phone) {
        $sql = "SELECT * FROM {$this->table} WHERE phone = ?";
        $result = dbFetch($sql, [$phone]);
        return $result ? $this->hideFields($result) : null;
    }
    
    /**
     * Get user by email (alias for findByEmail)
     */
    public function getByEmail($email) {
        return $this->findByEmail($email);
    }
    
    /**
     * Get user by phone (alias for findByPhone)
     */
    public function getByPhone($phone) {
        return $this->findByPhone($phone);
    }
    
    /**
     * Get all users with pagination and filtering
     */
    public function getAll($page = 1, $limit = 20, $filters = []) {
        $offset = ($page - 1) * $limit;
        $where = ["1=1"];
        $params = [];
        
        // Apply filters
        if (!empty($filters['role'])) {
            $where[] = "role = ?";
            $params[] = $filters['role'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
        $totalResult = dbFetch($countSql, $params);
        $total = $totalResult ? (int)$totalResult['total'] : 0;
        
        // Get paginated results
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $results = dbFetchAll($sql, $params);
        $users = array_map([$this, 'hideFields'], $results);
        
        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        $user = $this->find($id);
        return $user ? $this->hideFields($user) : null;
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $sql = "UPDATE {$this->table} SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        
        try {
            $stmt = dbQuery($sql, [$passwordHash, $userId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Password update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify email
     */
    public function verifyEmail($userId) {
        $sql = "UPDATE {$this->table} SET email_verified = 1, email_verified_at = NOW(), updated_at = NOW() WHERE id = ?";
        
        try {
            $stmt = dbQuery($sql, [$userId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify phone
     */
    public function verifyPhone($userId) {
        $sql = "UPDATE {$this->table} SET phone_verified = 1, phone_verified_at = NOW(), updated_at = NOW() WHERE id = ?";
        
        try {
            $stmt = dbQuery($sql, [$userId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Phone verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        // Remove sensitive fields
        unset($data['password'], $data['password_hash'], $data['role'], $data['status']);
        
        return $this->update($userId, $data);
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole($role) {
        return $this->findAll(['role' => $role, 'status' => 'active'], 'name ASC');
    }
    
    /**
     * Get user statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total users
        $stats['total_users'] = $this->count();
        
        // Active users
        $stats['active_users'] = $this->count(['status' => 'active']);
        
        // Users by role
        $roles = ['admin', 'moderator', 'member', 'volunteer', 'user'];
        foreach ($roles as $role) {
            $stats['users_by_role'][$role] = $this->count(['role' => $role, 'status' => 'active']);
        }
        
        // Recent registrations (last 30 days)
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = dbFetch($sql);
        $stats['recent_registrations'] = $result ? (int)$result['count'] : 0;
        
        return $stats;
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked($user) {
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return true;
        }
        return false;
    }
    
    /**
     * Increment login attempts
     */
    private function incrementLoginAttempts($userId) {
        $sql = "UPDATE {$this->table} SET login_attempts = login_attempts + 1 WHERE id = ?";
        dbQuery($sql, [$userId]);
        
        // Lock account if max attempts reached
        $user = $this->find($userId);
        if ($user && $user['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            $sql = "UPDATE {$this->table} SET locked_until = ? WHERE id = ?";
            dbQuery($sql, [$lockUntil, $userId]);
        }
    }
    
    /**
     * Reset login attempts
     */
    private function resetLoginAttempts($userId) {
        $sql = "UPDATE {$this->table} SET login_attempts = 0, locked_until = NULL WHERE id = ?";
        dbQuery($sql, [$userId]);
    }
    
    /**
     * Update last login timestamp
     */
    public function updateLastLogin($userId) {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?";
        dbQuery($sql, [$userId]);
    }
    
    /**
     * Search users
     */
    public function search($query, $filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $params = ["%{$query}%", "%{$query}%", "%{$query}%"];
        
        // Apply filters
        if (isset($filters['role'])) {
            $sql .= " AND role = ?";
            $params[] = $filters['role'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY name ASC";
        
        $results = dbFetchAll($sql, $params);
        return array_map([$this, 'hideFields'], $results);
    }
    
    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus($userIds, $status) {
        if (empty($userIds) || !in_array($status, ['active', 'inactive', 'suspended', 'pending'])) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})";
        
        $params = array_merge([$status], $userIds);
        
        try {
            $stmt = dbQuery($sql, $params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Bulk status update error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user profile with additional details
     */
    public function getProfile($id) {
        $sql = "SELECT id, name, email, phone, role, status, profile_image, 
                       email_verified, phone_verified, created_at, updated_at 
                FROM {$this->table} WHERE id = ?";
        
        try {
            $stmt = dbQuery($sql, [$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get profile error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get users with pagination and filters
     */
    public function getUsers($limit = 20, $offset = 0, $search = '', $role = '', $status = '') {
        $sql = "SELECT id, name, email, phone, role, status, profile_image, 
                       email_verified, phone_verified, created_at, updated_at 
                FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($role)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params = array_merge($params, [$limit, $offset]);
        
        try {
            $stmt = dbQuery($sql, $params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of users with filters
     */
    public function getUsersCount($search = '', $role = '', $status = '') {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($role)) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        try {
            $stmt = dbQuery($sql, $params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Get users count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get total count of users
     */
    public function getTotalCount() {
        return $this->getUsersCount();
    }
    
    /**
     * Get count of active users
     */
    public function getActiveCount() {
        try {
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'active'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Get active users count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        return $this->findById($id);
    }
}
?>