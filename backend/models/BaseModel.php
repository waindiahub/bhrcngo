<?php
/**
 * Base Model Class
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/../config/Database.php';

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find a record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $result = dbFetch($sql, [$id]);
        
        if ($result) {
            return $this->hideFields($result);
        }
        
        return null;
    }
    
    /**
     * Find a record by ID (alias for find)
     */
    public function findById($id) {
        return $this->find($id);
    }
    
    /**
     * Find all records with optional conditions
     */
    public function findAll($conditions = [], $orderBy = null, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $placeholders = str_repeat('?,', count($value) - 1) . '?';
                    $whereClause[] = "{$field} IN ({$placeholders})";
                    $params = array_merge($params, $value);
                } else {
                    $whereClause[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $results = dbFetchAll($sql, $params);
        
        return array_map([$this, 'hideFields'], $results);
    }
    
    /**
     * Create a new record
     */
    public function create($data) {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        
        try {
            $stmt = dbQuery($sql, array_values($data));
            $id = $this->db->lastInsertId();
            
            // Log the action
            $this->logAction('create', $id, null, $data);
            
            return $this->find($id);
        } catch (Exception $e) {
            error_log("Create error in {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a record
     */
    public function update($id, $data) {
        $oldData = $this->find($id);
        if (!$oldData) {
            return false;
        }
        
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        $params = array_merge(array_values($data), [$id]);
        
        try {
            $stmt = dbQuery($sql, $params);
            
            // Log the action
            $this->logAction('update', $id, $oldData, $data);
            
            return $this->find($id);
        } catch (Exception $e) {
            error_log("Update error in {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a record
     */
    public function delete($id) {
        $oldData = $this->find($id);
        if (!$oldData) {
            return false;
        }
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        
        try {
            $stmt = dbQuery($sql, [$id]);
            
            // Log the action
            $this->logAction('delete', $id, $oldData, null);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Delete error in {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Count records with optional conditions
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $result = dbFetch($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Paginate records
     */
    public function paginate($page = 1, $perPage = 20, $conditions = [], $orderBy = null) {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        $data = $this->findAll($conditions, $orderBy, $perPage, $offset);
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    /**
     * Execute raw SQL query
     */
    public function query($sql, $params = []) {
        return dbQuery($sql, $params);
    }
    
    /**
     * Filter data to only include fillable fields
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Hide sensitive fields from output
     */
    protected function hideFields($data) {
        if (empty($this->hidden) || !is_array($data)) {
            return $data;
        }
        
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }
    
    /**
     * Log actions for audit trail
     */
    protected function logAction($action, $recordId, $oldValues, $newValues) {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        $sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $_SESSION['user_id'],
            $action,
            $this->table,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        try {
            dbQuery($sql, $params);
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
    
    /**
     * Validate data before save
     */
    protected function validate($data, $rules = []) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }
            
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = ucfirst($field) . ' must be a valid email';
            }
            
            if (preg_match('/max:(\d+)/', $rule, $matches) && strlen($value) > $matches[1]) {
                $errors[$field] = ucfirst($field) . ' must not exceed ' . $matches[1] . ' characters';
            }
            
            if (preg_match('/min:(\d+)/', $rule, $matches) && strlen($value) < $matches[1]) {
                $errors[$field] = ucfirst($field) . ' must be at least ' . $matches[1] . ' characters';
            }
        }
        
        return $errors;
    }
    
    /**
     * Get public complaints with filtering and pagination
     */
    public function getPublicComplaints($params = []) {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        $where = ["status = 'public' OR visibility = 'public'"];
        $queryParams = [];
        
        // Add filters
        if (!empty($params['category'])) {
            $where[] = "category = ?";
            $queryParams[] = $params['category'];
        }
        
        if (!empty($params['status'])) {
            $where[] = "status = ?";
            $queryParams[] = $params['status'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
        $totalResult = dbFetch($countSql, $queryParams);
        $total = $totalResult ? (int)$totalResult['total'] : 0;
        
        // Get paginated results
        $sql = "SELECT * FROM {$this->table} WHERE {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $queryParams[] = $limit;
        $queryParams[] = $offset;
        
        $results = dbFetchAll($sql, $queryParams);
        $complaints = array_map([$this, 'hideFields'], $results);
        
        return [
            'data' => $complaints,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Find a record by a specific field
     */
    public function findBy($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        $result = dbFetch($sql, [$value]);
        
        if ($result) {
            return $this->hideFields($result);
        }
        
        return null;
    }
    
    /**
     * Update a record by a specific field
     */
    public function updateBy($field, $value, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $val) {
            if (in_array($key, $this->fillable) || empty($this->fillable)) {
                $fields[] = "{$key} = ?";
                $params[] = $val;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        if ($this->timestamps) {
            $fields[] = "updated_at = NOW()";
        }
        
        $params[] = $value;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$field} = ?";
        
        return dbExecute($sql, $params);
    }
    
    /**
     * Delete a record by a specific field
     */
    public function deleteBy($field, $value) {
        $sql = "DELETE FROM {$this->table} WHERE {$field} = ?";
        return dbExecute($sql, [$value]);
    }
}
?>