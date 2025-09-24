<?php
/**
 * Rate Limiting Middleware
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/../config/Database.php';

class RateLimitMiddleware {
    
    private static $limits = [
        'default' => ['requests' => 100, 'window' => 3600], // 100 requests per hour
        'auth' => ['requests' => 10, 'window' => 3600], // 10 auth attempts per hour
        'api' => ['requests' => 1000, 'window' => 3600], // 1000 API calls per hour
        'upload' => ['requests' => 20, 'window' => 3600], // 20 uploads per hour
        'contact' => ['requests' => 5, 'window' => 3600] // 5 contact submissions per hour
    ];
    
    /**
     * Check rate limit for request
     */
    public static function checkLimit($type = 'default', $identifier = null) {
        if (!isset(self::$limits[$type])) {
            $type = 'default';
        }
        
        $limit = self::$limits[$type];
        $identifier = $identifier ?: self::getIdentifier();
        
        // Get current usage
        $usage = self::getUsage($type, $identifier, $limit['window']);
        
        if ($usage >= $limit['requests']) {
            self::rateLimitExceeded($type, $limit);
            return false;
        }
        
        // Record this request
        self::recordRequest($type, $identifier);
        
        // Set rate limit headers
        self::setRateLimitHeaders($type, $limit, $usage + 1);
        
        return true;
    }
    
    /**
     * Get unique identifier for rate limiting
     */
    private static function getIdentifier() {
        // Use user ID if authenticated
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            return 'user_' . $_SESSION['user_id'];
        }
        
        // Use IP address as fallback
        return 'ip_' . self::getClientIP();
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get current usage for identifier
     */
    private static function getUsage($type, $identifier, $window) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $windowStart = date('Y-m-d H:i:s', time() - $window);
            
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM rate_limits 
                WHERE type = ? AND identifier = ? AND created_at >= ?
            ");
            
            $stmt->execute([$type, $identifier, $windowStart]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($result['count'] ?? 0);
            
        } catch (Exception $e) {
            error_log('Rate limit check error: ' . $e->getMessage());
            return 0; // Allow request on error
        }
    }
    
    /**
     * Record a request
     */
    private static function recordRequest($type, $identifier) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO rate_limits (type, identifier, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $type,
                $identifier,
                self::getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
        } catch (Exception $e) {
            error_log('Rate limit record error: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean old rate limit records
     */
    public static function cleanup() {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Delete records older than 24 hours
            $stmt = $db->prepare("
                DELETE FROM rate_limits 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log('Rate limit cleanup error: ' . $e->getMessage());
        }
    }
    
    /**
     * Set rate limit headers
     */
    private static function setRateLimitHeaders($type, $limit, $usage) {
        $remaining = max(0, $limit['requests'] - $usage);
        $resetTime = time() + $limit['window'];
        
        header("X-RateLimit-Limit: {$limit['requests']}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: {$resetTime}");
        header("X-RateLimit-Window: {$limit['window']}");
    }
    
    /**
     * Handle rate limit exceeded
     */
    private static function rateLimitExceeded($type, $limit) {
        $resetTime = time() + $limit['window'];
        
        header("X-RateLimit-Limit: {$limit['requests']}");
        header("X-RateLimit-Remaining: 0");
        header("X-RateLimit-Reset: {$resetTime}");
        header("X-RateLimit-Window: {$limit['window']}");
        
        http_response_code(429);
        
        $response = [
            'error' => true,
            'message' => 'Rate limit exceeded',
            'type' => $type,
            'limit' => $limit['requests'],
            'window' => $limit['window'],
            'reset_at' => date('Y-m-d H:i:s', $resetTime)
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    /**
     * Create rate limits table if not exists
     */
    public static function createTable() {
        try {
            $db = Database::getInstance()->getConnection();
            
            $sql = "
                CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    type VARCHAR(50) NOT NULL,
                    identifier VARCHAR(100) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_type_identifier_time (type, identifier, created_at),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $db->exec($sql);
            
        } catch (Exception $e) {
            error_log('Rate limit table creation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get rate limit status for identifier
     */
    public static function getStatus($type = 'default', $identifier = null) {
        if (!isset(self::$limits[$type])) {
            $type = 'default';
        }
        
        $limit = self::$limits[$type];
        $identifier = $identifier ?: self::getIdentifier();
        $usage = self::getUsage($type, $identifier, $limit['window']);
        
        return [
            'type' => $type,
            'limit' => $limit['requests'],
            'used' => $usage,
            'remaining' => max(0, $limit['requests'] - $usage),
            'window' => $limit['window'],
            'reset_at' => time() + $limit['window']
        ];
    }
}

// Initialize rate limits table
RateLimitMiddleware::createTable();