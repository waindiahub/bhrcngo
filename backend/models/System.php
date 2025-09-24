<?php
/**
 * System Model
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/BaseModel.php';

class System extends BaseModel {
    protected $table = 'system_logs';
    protected $fillable = [
        'level', 'message', 'context', 'user_id', 'ip_address',
        'user_agent', 'url', 'method', 'created_at'
    ];
    
    /**
     * Log system activity
     */
    public function log($level, $message, $context = [], $userId = null) {
        $logData = [
            'level' => $level,
            'message' => $message,
            'context' => json_encode($context),
            'user_id' => $userId ?? ($_SESSION['user_id'] ?? null),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($logData);
    }
    
    /**
     * Get system statistics
     */
    public function getSystemStats() {
        $stats = [];
        
        // Database statistics
        $stats['database'] = $this->getDatabaseStats();
        
        // User statistics
        $stats['users'] = $this->getUserStats();
        
        // Content statistics
        $stats['content'] = $this->getContentStats();
        
        // System health
        $stats['system'] = $this->getSystemHealth();
        
        // Recent activity
        $stats['recent_activity'] = $this->getRecentActivity();
        
        return $stats;
    }
    
    /**
     * Get database statistics
     */
    private function getDatabaseStats() {
        $stats = [];
        
        // Get table sizes
        $tables = ['users', 'members', 'events', 'complaints', 'donations', 
                  'gallery', 'contact_inquiries', 'newsletter_subscriptions'];
        
        foreach ($tables as $table) {
            $sql = "SELECT COUNT(*) as count FROM {$table}";
            $result = dbFetch($sql);
            $stats['tables'][$table] = $result['count'] ?? 0;
        }
        
        // Database size (approximate)
        $sql = "SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()";
        $result = dbFetch($sql);
        $stats['size_mb'] = $result['size_mb'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get user statistics
     */
    private function getUserStats() {
        $stats = [];
        
        // Total users
        $sql = "SELECT COUNT(*) as count FROM users";
        $result = dbFetch($sql);
        $stats['total'] = $result['count'] ?? 0;
        
        // Active users (logged in last 30 days)
        $sql = "SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = dbFetch($sql);
        $stats['active_30_days'] = $result['count'] ?? 0;
        
        // New users this month
        $sql = "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')";
        $result = dbFetch($sql);
        $stats['new_this_month'] = $result['count'] ?? 0;
        
        // Users by role
        $sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
        $results = dbFetchAll($sql);
        $stats['by_role'] = [];
        foreach ($results as $result) {
            $stats['by_role'][$result['role']] = $result['count'];
        }
        
        return $stats;
    }
    
    /**
     * Get content statistics
     */
    private function getContentStats() {
        $stats = [];
        
        // Events
        $sql = "SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                       SUM(CASE WHEN event_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming
                FROM events";
        $result = dbFetch($sql);
        $stats['events'] = $result;
        
        // Members
        $sql = "SELECT COUNT(*) as total,
                       SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
                FROM members";
        $result = dbFetch($sql);
        $stats['members'] = $result;
        
        // Complaints
        $sql = "SELECT COUNT(*) as total,
                       SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                       SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                FROM complaints";
        $result = dbFetch($sql);
        $stats['complaints'] = $result;
        
        // Gallery items
        $sql = "SELECT COUNT(*) as total FROM gallery";
        $result = dbFetch($sql);
        $stats['gallery'] = $result['total'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Get system health metrics
     */
    private function getSystemHealth() {
        $health = [];
        
        // Disk space
        $health['disk_space'] = $this->getDiskSpace();
        
        // Memory usage
        $health['memory'] = $this->getMemoryUsage();
        
        // System uptime
        $health['uptime'] = $this->getSystemUptime();
        
        // PHP version
        $health['php_version'] = PHP_VERSION;
        
        // Database connection
        $health['database_connection'] = $this->testDatabaseConnection();
        
        // Error log size
        $health['error_log_size'] = $this->getErrorLogSize();
        
        return $health;
    }
    
    /**
     * Get disk space information
     */
    private function getDiskSpace() {
        $bytes = disk_free_space(".");
        $total = disk_total_space(".");
        
        return [
            'free_bytes' => $bytes,
            'total_bytes' => $total,
            'free_gb' => round($bytes / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_percentage' => round((($total - $bytes) / $total) * 100, 2)
        ];
    }
    
    /**
     * Get memory usage
     */
    private function getMemoryUsage() {
        return [
            'current_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'limit' => ini_get('memory_limit')
        ];
    }
    
    /**
     * Get system uptime (approximate)
     */
    private function getSystemUptime() {
        // This is a simple approximation - in production you'd use system commands
        $startTime = filemtime(__FILE__);
        $uptime = time() - $startTime;
        
        return [
            'seconds' => $uptime,
            'formatted' => $this->formatUptime($uptime)
        ];
    }
    
    /**
     * Format uptime in human readable format
     */
    private function formatUptime($seconds) {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return "{$days} days, {$hours} hours, {$minutes} minutes";
    }
    
    /**
     * Test database connection
     */
    private function testDatabaseConnection() {
        try {
            $sql = "SELECT 1";
            $result = dbFetch($sql);
            return ['status' => 'connected', 'message' => 'Database connection successful'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get error log size
     */
    private function getErrorLogSize() {
        $logFile = ini_get('error_log');
        if ($logFile && file_exists($logFile)) {
            $size = filesize($logFile);
            return [
                'bytes' => $size,
                'mb' => round($size / 1024 / 1024, 2)
            ];
        }
        
        return ['bytes' => 0, 'mb' => 0];
    }
    
    /**
     * Get recent activity
     */
    public function getRecentActivity($limit = 20) {
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY created_at DESC 
                LIMIT {$limit}";
        
        return dbFetchAll($sql);
    }
    
    /**
     * Get logs with filters
     */
    public function getLogs($filters = [], $limit = 50, $page = 1) {
        $offset = ($page - 1) * $limit;
        $conditions = [];
        $params = [];
        
        if (!empty($filters['level'])) {
            $conditions[] = "level = ?";
            $params[] = $filters['level'];
        }
        
        if (!empty($filters['user_id'])) {
            $conditions[] = "user_id = ?";
            $params[] = $filters['user_id'];
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
            $conditions[] = "message LIKE ?";
            $params[] = "%{$filters['search']}%";
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} 
                ORDER BY created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        return dbFetchAll($sql, $params);
    }
    
    /**
     * Clear old logs
     */
    public function clearOldLogs($days = 30) {
        $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $result = dbQuery($sql, [$days]);
        
        return $result ? ['success' => true, 'message' => 'Old logs cleared'] : 
                        ['success' => false, 'message' => 'Failed to clear logs'];
    }
    
    /**
     * Create system backup
     */
    public function createBackup() {
        $backupDir = __DIR__ . '/../../backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/backup_{$timestamp}.sql";
        
        // This is a simplified backup - in production use mysqldump
        $tables = ['users', 'members', 'events', 'complaints', 'donations', 
                  'gallery', 'contact_inquiries', 'newsletter_subscriptions'];
        
        $backup = "-- BHRC Database Backup\n";
        $backup .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $backup .= $this->backupTable($table);
        }
        
        if (file_put_contents($backupFile, $backup)) {
            $this->log('info', 'Database backup created', ['file' => $backupFile]);
            return ['success' => true, 'file' => $backupFile];
        }
        
        return ['success' => false, 'message' => 'Failed to create backup'];
    }
    
    /**
     * Backup single table
     */
    private function backupTable($table) {
        $backup = "-- Table: {$table}\n";
        
        // Get table structure
        $sql = "SHOW CREATE TABLE {$table}";
        $result = dbFetch($sql);
        if ($result) {
            $backup .= $result['Create Table'] . ";\n\n";
        }
        
        // Get table data
        $sql = "SELECT * FROM {$table}";
        $rows = dbFetchAll($sql);
        
        if (!empty($rows)) {
            $backup .= "INSERT INTO {$table} VALUES\n";
            $values = [];
            
            foreach ($rows as $row) {
                $rowValues = array_map(function($value) {
                    return $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                }, array_values($row));
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            
            $backup .= implode(",\n", $values) . ";\n\n";
        }
        
        return $backup;
    }
    
    /**
     * Get available backups
     */
    public function getBackups() {
        $backupDir = __DIR__ . '/../../backups';
        if (!is_dir($backupDir)) {
            return [];
        }
        
        $backups = [];
        $files = glob($backupDir . '/backup_*.sql');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'created' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Sort by creation time (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });
        
        return $backups;
    }
    
    /**
     * Clear cache
     */
    public function clearCache() {
        $cacheDir = __DIR__ . '/../../cache';
        $cleared = 0;
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $cleared++;
                }
            }
        }
        
        $this->log('info', 'Cache cleared', ['files_cleared' => $cleared]);
        
        return ['success' => true, 'files_cleared' => $cleared];
    }
    
    /**
     * Get system configuration
     */
    public function getSystemConfig() {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'timezone' => date_default_timezone_get()
        ];
    }
}

/**
 * Activity Log Model
 */
class ActivityLog extends BaseModel {
    protected $table = 'activity_logs';
    protected $fillable = [
        'user_id', 'action', 'resource_type', 'resource_id', 
        'description', 'ip_address', 'user_agent', 'created_at'
    ];
    
    /**
     * Log user activity
     */
    public function logActivity($userId, $action, $resourceType, $resourceId = null, $description = null) {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($data);
    }
    
    /**
     * Get user activity
     */
    public function getUserActivity($userId, $limit = 20) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT {$limit}";
        
        return dbFetchAll($sql, [$userId]);
    }
    
    /**
     * Get recent activity across all users
     */
    public function getRecentActivity($limit = 50) {
        $sql = "SELECT al.*, u.name as user_name 
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC 
                LIMIT {$limit}";
        
        return dbFetchAll($sql);
    }
}
?>