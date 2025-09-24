<?php
/**
 * Notification Model
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/BaseModel.php';

class Notification extends BaseModel {
    protected $table = 'notifications';
    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'category', 'priority',
        'read_at', 'action_url', 'action_text', 'data', 'expires_at',
        'sent_via', 'created_by'
    ];
    
    /**
     * Create notification
     */
    public function createNotification($data) {
        $errors = $this->validate($data, [
            'user_id' => 'required|integer',
            'title' => 'required|max:255',
            'message' => 'required|max:1000',
            'type' => 'required|in:info,success,warning,error,announcement'
        ]);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $notificationData = [
            'user_id' => $data['user_id'],
            'title' => htmlspecialchars(trim($data['title'])),
            'message' => htmlspecialchars(trim($data['message'])),
            'type' => $data['type'],
            'category' => $data['category'] ?? 'general',
            'priority' => $data['priority'] ?? 'medium',
            'action_url' => $data['action_url'] ?? null,
            'action_text' => $data['action_text'] ?? null,
            'data' => isset($data['data']) ? json_encode($data['data']) : null,
            'expires_at' => $data['expires_at'] ?? null,
            'sent_via' => $data['sent_via'] ?? 'web',
            'created_by' => $_SESSION['user_id'] ?? null
        ];
        
        $notification = $this->create($notificationData);
        
        if ($notification) {
            // Send real-time notification if user is online
            $this->sendRealTimeNotification($notification);
            
            return ['success' => true, 'notification' => $notification];
        }
        
        return ['success' => false, 'message' => 'Failed to create notification'];
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $filters = [], $limit = 20, $page = 1) {
        $offset = ($page - 1) * $limit;
        $conditions = ['user_id = ?'];
        $params = [$userId];
        
        // Add filters
        if (!empty($filters['type'])) {
            $conditions[] = "type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['category'])) {
            $conditions[] = "category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['unread_only'])) {
            $conditions[] = "read_at IS NULL";
        }
        
        if (!empty($filters['priority'])) {
            $conditions[] = "priority = ?";
            $params[] = $filters['priority'];
        }
        
        // Exclude expired notifications
        $conditions[] = "(expires_at IS NULL OR expires_at > NOW())";
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
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
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId = null) {
        $conditions = ['id' => $notificationId];
        if ($userId) {
            $conditions['user_id'] = $userId;
        }
        
        $notification = $this->findAll($conditions);
        if (empty($notification)) {
            return ['success' => false, 'message' => 'Notification not found'];
        }
        
        $updated = $this->update($notificationId, [
            'read_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($updated) {
            return ['success' => true, 'message' => 'Notification marked as read'];
        }
        
        return ['success' => false, 'message' => 'Failed to mark as read'];
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE {$this->table} 
                SET read_at = NOW() 
                WHERE user_id = ? AND read_at IS NULL";
        
        $result = dbQuery($sql, [$userId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'All notifications marked as read'];
        }
        
        return ['success' => false, 'message' => 'Failed to mark notifications as read'];
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification($notificationId, $userId = null) {
        $conditions = ['id' => $notificationId];
        if ($userId) {
            $conditions['user_id'] = $userId;
        }
        
        $notification = $this->findAll($conditions);
        if (empty($notification)) {
            return ['success' => false, 'message' => 'Notification not found'];
        }
        
        $deleted = $this->delete($notificationId);
        
        if ($deleted) {
            return ['success' => true, 'message' => 'Notification deleted'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete notification'];
    }
    
    /**
     * Get unread count for user
     */
    public function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE user_id = ? AND read_at IS NULL 
                AND (expires_at IS NULL OR expires_at > NOW())";
        
        $result = dbFetch($sql, [$userId]);
        return $result['count'] ?? 0;
    }
    
    /**
     * Send notification to multiple users
     */
    public function sendToUsers($userIds, $title, $message, $type = 'info', $options = []) {
        $sent = 0;
        $errors = [];
        
        foreach ($userIds as $userId) {
            $data = array_merge([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type
            ], $options);
            
            $result = $this->createNotification($data);
            
            if ($result['success']) {
                $sent++;
            } else {
                $errors[] = [
                    'user_id' => $userId,
                    'error' => $result['message'] ?? 'Unknown error'
                ];
            }
        }
        
        return [
            'sent' => $sent,
            'errors' => $errors,
            'total' => count($userIds)
        ];
    }
    
    /**
     * Send notification to all users with specific role
     */
    public function sendToRole($role, $title, $message, $type = 'info', $options = []) {
        $sql = "SELECT id FROM users WHERE role = ? AND status = 'active'";
        $users = dbFetchAll($sql, [$role]);
        
        $userIds = array_column($users, 'id');
        
        return $this->sendToUsers($userIds, $title, $message, $type, $options);
    }
    
    /**
     * Send notification to all active users
     */
    public function sendToAllUsers($title, $message, $type = 'announcement', $options = []) {
        $sql = "SELECT id FROM users WHERE status = 'active'";
        $users = dbFetchAll($sql);
        
        $userIds = array_column($users, 'id');
        
        return $this->sendToUsers($userIds, $title, $message, $type, $options);
    }
    
    /**
     * Get notification statistics
     */
    public function getStats($userId = null) {
        $stats = [];
        
        if ($userId) {
            // User-specific stats
            $stats['total'] = $this->count(['user_id' => $userId]);
            $stats['unread'] = $this->getUnreadCount($userId);
            $stats['read'] = $stats['total'] - $stats['unread'];
            
            // By type
            $sql = "SELECT type, COUNT(*) as count FROM {$this->table} 
                    WHERE user_id = ? GROUP BY type";
            $results = dbFetchAll($sql, [$userId]);
            $stats['by_type'] = [];
            foreach ($results as $result) {
                $stats['by_type'][$result['type']] = $result['count'];
            }
        } else {
            // System-wide stats
            $stats['total'] = $this->count();
            
            // By type
            $sql = "SELECT type, COUNT(*) as count FROM {$this->table} GROUP BY type";
            $results = dbFetchAll($sql);
            $stats['by_type'] = [];
            foreach ($results as $result) {
                $stats['by_type'][$result['type']] = $result['count'];
            }
            
            // By priority
            $sql = "SELECT priority, COUNT(*) as count FROM {$this->table} GROUP BY priority";
            $results = dbFetchAll($sql);
            $stats['by_priority'] = [];
            foreach ($results as $result) {
                $stats['by_priority'][$result['priority']] = $result['count'];
            }
            
            // This month
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')";
            $result = dbFetch($sql);
            $stats['this_month'] = $result['count'] ?? 0;
        }
        
        return $stats;
    }
    
    /**
     * Clean up expired notifications
     */
    public function cleanupExpired() {
        $sql = "DELETE FROM {$this->table} WHERE expires_at < NOW()";
        $result = dbQuery($sql);
        
        return $result ? ['success' => true, 'message' => 'Expired notifications cleaned up'] :
                        ['success' => false, 'message' => 'Failed to cleanup notifications'];
    }
    
    /**
     * Send real-time notification (WebSocket/SSE)
     */
    private function sendRealTimeNotification($notification) {
        // This would integrate with WebSocket server or Server-Sent Events
        // For now, we'll just log it
        error_log("Real-time notification sent to user {$notification['user_id']}: {$notification['title']}");
    }
    
    /**
     * Send push notification
     */
    public function sendPushNotification($userId, $title, $message, $data = []) {
        // Get user's push subscription
        $subscription = $this->getUserPushSubscription($userId);
        
        if (!$subscription) {
            return ['success' => false, 'message' => 'No push subscription found'];
        }
        
        // This would integrate with push notification service (FCM, etc.)
        // For now, we'll simulate it
        $payload = [
            'title' => $title,
            'body' => $message,
            'data' => $data,
            'icon' => '/assets/images/logo.png',
            'badge' => '/assets/images/badge.png'
        ];
        
        // Log the push notification
        error_log("Push notification sent to user {$userId}: " . json_encode($payload));
        
        return ['success' => true, 'message' => 'Push notification sent'];
    }
    
    /**
     * Get user's push subscription
     */
    private function getUserPushSubscription($userId) {
        $sql = "SELECT push_subscription FROM users WHERE id = ?";
        $result = dbFetch($sql, [$userId]);
        
        return $result ? json_decode($result['push_subscription'], true) : null;
    }
    
    /**
     * Get recent notifications for dashboard
     */
    public function getRecentNotifications($limit = 10) {
        $sql = "SELECT n.*, u.name as user_name 
                FROM {$this->table} n
                LEFT JOIN users u ON n.user_id = u.id
                ORDER BY n.created_at DESC 
                LIMIT {$limit}";
        
        return dbFetchAll($sql);
    }
    
    /**
     * Search notifications
     */
    public function searchNotifications($query, $userId = null, $limit = 20) {
        $conditions = ["(title LIKE ? OR message LIKE ?)"];
        $params = ["%{$query}%", "%{$query}%"];
        
        if ($userId) {
            $conditions[] = "user_id = ?";
            $params[] = $userId;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} 
                ORDER BY created_at DESC 
                LIMIT {$limit}";
        
        return dbFetchAll($sql, $params);
    }
}

/**
 * Notification Preferences Model
 */
class NotificationPreference extends BaseModel {
    protected $table = 'notification_preferences';
    protected $fillable = [
        'user_id', 'category', 'email_enabled', 'push_enabled', 
        'sms_enabled', 'in_app_enabled', 'frequency'
    ];
    
    /**
     * Get user preferences
     */
    public function getUserPreferences($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ?";
        $preferences = dbFetchAll($sql, [$userId]);
        
        // Convert to associative array
        $result = [];
        foreach ($preferences as $pref) {
            $result[$pref['category']] = [
                'email_enabled' => (bool)$pref['email_enabled'],
                'push_enabled' => (bool)$pref['push_enabled'],
                'sms_enabled' => (bool)$pref['sms_enabled'],
                'in_app_enabled' => (bool)$pref['in_app_enabled'],
                'frequency' => $pref['frequency']
            ];
        }
        
        return $result;
    }
    
    /**
     * Update user preferences
     */
    public function updatePreferences($userId, $preferences) {
        $updated = 0;
        
        foreach ($preferences as $category => $settings) {
            $data = [
                'user_id' => $userId,
                'category' => $category,
                'email_enabled' => $settings['email_enabled'] ?? false,
                'push_enabled' => $settings['push_enabled'] ?? false,
                'sms_enabled' => $settings['sms_enabled'] ?? false,
                'in_app_enabled' => $settings['in_app_enabled'] ?? true,
                'frequency' => $settings['frequency'] ?? 'immediate'
            ];
            
            // Check if preference exists
            $existing = $this->findAll(['user_id' => $userId, 'category' => $category]);
            
            if (!empty($existing)) {
                $this->update($existing[0]['id'], $data);
            } else {
                $this->create($data);
            }
            
            $updated++;
        }
        
        return ['success' => true, 'updated' => $updated];
    }
    
    /**
     * Get default preferences
     */
    public function getDefaultPreferences() {
        return [
            'events' => [
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
                'in_app_enabled' => true,
                'frequency' => 'immediate'
            ],
            'announcements' => [
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
                'in_app_enabled' => true,
                'frequency' => 'immediate'
            ],
            'complaints' => [
                'email_enabled' => true,
                'push_enabled' => false,
                'sms_enabled' => false,
                'in_app_enabled' => true,
                'frequency' => 'daily'
            ],
            'system' => [
                'email_enabled' => false,
                'push_enabled' => false,
                'sms_enabled' => false,
                'in_app_enabled' => true,
                'frequency' => 'immediate'
            ]
        ];
    }
}
?>