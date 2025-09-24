<?php
/**
 * Database Configuration and Connection Class
 * BHRC - Bharatiya Human Rights Council
 */

class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;
    
    private function __construct() {
        // Load database configuration
        $config = require __DIR__ . '/config.php';
        $dbConfig = $config['database'];
        
        $this->host = $dbConfig['host'];
        $this->database = $dbConfig['database'];
        $this->username = $dbConfig['username'];
        $this->password = $dbConfig['password'];
        $this->charset = $dbConfig['charset'];
        
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query and return results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Query execution failed");
        }
    }
    
    /**
     * Execute a statement (INSERT, UPDATE, DELETE)
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Execute error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Statement execution failed");
        }
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Check if in transaction
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }
    
    /**
     * Escape string for SQL
     */
    public function escape($string) {
        return $this->connection->quote($string);
    }
    
    /**
     * Get database info
     */
    public function getInfo() {
        return [
            'host' => $this->host,
            'database' => $this->database,
            'charset' => $this->charset,
            'version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION)
        ];
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Prepare a statement
     */
    public function prepare($sql) {
        try {
            return $this->connection->prepare($sql);
        } catch (PDOException $e) {
            error_log("Prepare error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Statement preparation failed");
        }
    }
    
    /**
     * Fetch all results from query
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch single result from query
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert data and return last insert ID
     */
    public function insert($sql, $params = []) {
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $this->lastInsertId();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Global database helper functions
 */

/**
 * Get database instance
 */
function db() {
    return Database::getInstance();
}

/**
 * Execute query and return all results
 */
function dbQuery($sql, $params = []) {
    return Database::getInstance()->query($sql, $params);
}

/**
 * Execute query and return first result
 */
function dbFetch($sql, $params = []) {
    $results = Database::getInstance()->query($sql, $params);
    return !empty($results) ? $results[0] : null;
}

/**
 * Execute statement (INSERT, UPDATE, DELETE)
 */
function dbExecute($sql, $params = []) {
    return Database::getInstance()->execute($sql, $params);
}

/**
 * Get last insert ID
 */
function dbLastInsertId() {
    return Database::getInstance()->lastInsertId();
}

/**
 * Begin transaction
 */
function dbBegin() {
    return Database::getInstance()->beginTransaction();
}

/**
 * Commit transaction
 */
function dbCommit() {
    return Database::getInstance()->commit();
}

/**
 * Rollback transaction
 */
function dbRollback() {
    return Database::getInstance()->rollback();
}

/**
 * Execute multiple queries in transaction
 */
function dbTransaction($callback) {
    $db = Database::getInstance();
    
    try {
        $db->beginTransaction();
        
        $result = $callback($db);
        
        $db->commit();
        return $result;
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Sanitize input for database
 */
function dbSanitize($input) {
    if (is_array($input)) {
        return array_map('dbSanitize', $input);
    }
    
    if (is_string($input)) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
    
    return $input;
}

/**
 * Build WHERE clause from conditions array
 */
function dbBuildWhere($conditions) {
    if (empty($conditions)) {
        return ['', []];
    }
    
    $whereClause = [];
    $params = [];
    
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
    
    return [' WHERE ' . implode(' AND ', $whereClause), $params];
}

/**
 * Build ORDER BY clause
 */
function dbBuildOrderBy($orderBy) {
    if (empty($orderBy)) {
        return '';
    }
    
    if (is_string($orderBy)) {
        return " ORDER BY {$orderBy}";
    }
    
    if (is_array($orderBy)) {
        $orderClauses = [];
        foreach ($orderBy as $field => $direction) {
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $orderClauses[] = "{$field} {$direction}";
        }
        return " ORDER BY " . implode(', ', $orderClauses);
    }
    
    return '';
}

/**
 * Build LIMIT clause
 */
function dbBuildLimit($limit, $offset = 0) {
    if (empty($limit)) {
        return '';
    }
    
    $limitClause = " LIMIT {$limit}";
    
    if ($offset > 0) {
        $limitClause .= " OFFSET {$offset}";
    }
    
    return $limitClause;
}

/**
 * Check if table exists
 */
function dbTableExists($tableName) {
    $sql = "SHOW TABLES LIKE ?";
    $result = dbFetch($sql, [$tableName]);
    return !empty($result);
}

/**
 * Get table columns
 */
function dbGetColumns($tableName) {
    $sql = "DESCRIBE {$tableName}";
    return dbQuery($sql);
}

/**
 * Check if column exists in table
 */
function dbColumnExists($tableName, $columnName) {
    $columns = dbGetColumns($tableName);
    foreach ($columns as $column) {
        if ($column['Field'] === $columnName) {
            return true;
        }
    }
    return false;
}
?>