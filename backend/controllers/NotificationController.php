<?php
/**
 * Notification Controller
 * Handles notification management, sending, and tracking
 */

require_once __DIR__ . '/BaseController.php';

class NotificationController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get notifications for current user
     */
    public function getNotifications() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $unreadOnly = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;
            
            if (!$userId) {
                $this->sendError('User not authenticated');
                return;
            }
            
            $notifications = $this->getUserNotifications($userId, $page, $limit, $unreadOnly);
            
            $this->sendResponse([
                'success' => true,
                'data' => $notifications
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch notifications: ' . $e->getMessage());
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead() {
        try {
            $notificationId = $_POST['notification_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->sendError('User not authenticated');
                return;
            }
            
            if (!$notificationId) {
                $this->sendError('Notification ID is required');
                return;
            }
            
            $success = $this->markNotificationAsRead($notificationId, $userId);
            
            if ($success) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            } else {
                $this->sendError('Failed to mark notification as read');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to mark notification as read: ' . $e->getMessage());
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->sendError('User not authenticated');
                return;
            }
            
            $count = $this->markAllNotificationsAsRead($userId);
            
            $this->sendResponse([
                'success' => true,
                'message' => "{$count} notifications marked as read"
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to mark all notifications as read: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification() {
        try {
            $notificationId = $_POST['notification_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->sendError('User not authenticated');
                return;
            }
            
            if (!$notificationId) {
                $this->sendError('Notification ID is required');
                return;
            }
            
            $success = $this->deleteUserNotification($notificationId, $userId);
            
            if ($success) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Notification deleted'
                ]);
            } else {
                $this->sendError('Failed to delete notification');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to delete notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Send notification to user(s)
     */
    public function sendNotification() {
        try {
            $title = $_POST['title'] ?? '';
            $message = $_POST['message'] ?? '';
            $type = $_POST['type'] ?? 'info';
            $recipients = $_POST['recipients'] ?? []; // Array of user IDs
            $sendToAll = isset($_POST['send_to_all']) ? (bool)$_POST['send_to_all'] : false;
            $actionUrl = $_POST['action_url'] ?? null;
            
            if (empty($title) || empty($message)) {
                $this->sendError('Title and message are required');
                return;
            }
            
            // Validate notification type
            $validTypes = ['info', 'success', 'warning', 'error', 'announcement'];
            if (!in_array($type, $validTypes)) {
                $this->sendError('Invalid notification type');
                return;
            }
            
            $sentCount = 0;
            
            if ($sendToAll) {
                // Send to all users
                $sentCount = $this->sendNotificationToAll($title, $message, $type, $actionUrl);
            } else {
                // Send to specific users
                if (empty($recipients)) {
                    $this->sendError('Recipients are required when not sending to all');
                    return;
                }
                
                $sentCount = $this->sendNotificationToUsers($recipients, $title, $message, $type, $actionUrl);
            }
            
            $this->sendResponse([
                'success' => true,
                'message' => "Notification sent to {$sentCount} users"
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to send notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Get notification statistics
     */
    public function getStats() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->sendError('User not authenticated');
                return;
            }
            
            $stats = $this->getNotificationStats($userId);
            
            $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get notification stats: ' . $e->getMessage());
        }
    }
    
    /**
     * Update notification preferences
     */
    public function updatePreferences() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $preferences = $_POST['preferences'] ?? [];
            
            if (!$userId) {
                $this->sendError('User not authenticated');
                return;
            }
            
            $success = $this->updateNotificationPreferences($userId, $preferences);
            
            if ($success) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Notification preferences updated'
                ]);
            } else {
                $this->sendError('Failed to update preferences');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to update preferences: ' . $e->getMessage());
        }
    }
    
    /**
     * Get notification preferences
     */
    public function getPreferences() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $this->sendError('User not authenticated');
                return;
            }
            
            $preferences = $this->getNotificationPreferences($userId);
            
            $this->sendResponse([
                'success' => true,
                'data' => $preferences
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get preferences: ' . $e->getMessage());
        }
    }
    
    /**
     * Send push notification
     */
    public function sendPushNotification() {
        try {
            $userId = $_POST['user_id'] ?? null;
            $title = $_POST['title'] ?? '';
            $message = $_POST['message'] ?? '';
            $data = $_POST['data'] ?? [];
            
            if (!$userId || empty($title) || empty($message)) {
                $this->sendError('User ID, title, and message are required');
                return;
            }
            
            $success = $this->sendPushToUser($userId, $title, $message, $data);
            
            if ($success) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Push notification sent'
                ]);
            } else {
                $this->sendError('Failed to send push notification');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to send push notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user notifications from database
     */
    private function getUserNotifications($userId, $page, $limit, $unreadOnly) {
        // This would fetch from database
        // For now, return sample data
        return [
            'notifications' => [
                [
                    'id' => '1',
                    'title' => 'Welcome to BHRC',
                    'message' => 'Thank you for joining our community',
                    'type' => 'info',
                    'read' => false,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'action_url' => '/dashboard'
                ],
                [
                    'id' => '2',
                    'title' => 'Event Reminder',
                    'message' => 'Community meeting tomorrow at 10 AM',
                    'type' => 'warning',
                    'read' => true,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'action_url' => '/events/123'
                ]
            ],
            'total' => 2,
            'unread_count' => 1,
            'page' => $page,
            'limit' => $limit
        ];
    }
    
    /**
     * Mark notification as read in database
     */
    private function markNotificationAsRead($notificationId, $userId) {
        // This would update database
        return true;
    }
    
    /**
     * Mark all notifications as read in database
     */
    private function markAllNotificationsAsRead($userId) {
        // This would update database
        return 5; // Return count of updated notifications
    }
    
    /**
     * Delete notification from database
     */
    private function deleteUserNotification($notificationId, $userId) {
        // This would delete from database
        return true;
    }
    
    /**
     * Send notification to all users
     */
    private function sendNotificationToAll($title, $message, $type, $actionUrl) {
        // This would send to all users in database
        return 100; // Return count of users notified
    }
    
    /**
     * Send notification to specific users
     */
    private function sendNotificationToUsers($recipients, $title, $message, $type, $actionUrl) {
        $sentCount = 0;
        
        foreach ($recipients as $userId) {
            if ($this->createNotification($userId, $title, $message, $type, $actionUrl)) {
                $sentCount++;
            }
        }
        
        return $sentCount;
    }
    
    /**
     * Create notification in database
     */
    private function createNotification($userId, $title, $message, $type, $actionUrl) {
        // This would insert into database
        return true;
    }
    
    /**
     * Get notification statistics
     */
    private function getNotificationStats($userId) {
        // This would fetch from database
        return [
            'total' => 25,
            'unread' => 5,
            'read' => 20,
            'by_type' => [
                'info' => 10,
                'success' => 5,
                'warning' => 7,
                'error' => 2,
                'announcement' => 1
            ],
            'recent_activity' => [
                'today' => 3,
                'this_week' => 8,
                'this_month' => 15
            ]
        ];
    }
    
    /**
     * Update notification preferences
     */
    private function updateNotificationPreferences($userId, $preferences) {
        // This would update database
        return true;
    }
    
    /**
     * Get notification preferences
     */
    private function getNotificationPreferences($userId) {
        // This would fetch from database
        return [
            'email_notifications' => true,
            'push_notifications' => true,
            'sms_notifications' => false,
            'notification_types' => [
                'events' => true,
                'complaints' => true,
                'announcements' => true,
                'reminders' => true,
                'system' => false
            ],
            'frequency' => 'immediate', // immediate, daily, weekly
            'quiet_hours' => [
                'enabled' => true,
                'start' => '22:00',
                'end' => '08:00'
            ]
        ];
    }
    
    /**
     * Send push notification to user
     */
    private function sendPushToUser($userId, $title, $message, $data) {
        try {
            // Get user's push subscription from database
            $subscription = $this->getUserPushSubscription($userId);
            
            if (!$subscription) {
                return false;
            }
            
            // Here you would integrate with push notification service
            // For now, simulate success
            return true;
            
        } catch (Exception $e) {
            error_log("Push notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's push subscription
     */
    private function getUserPushSubscription($userId) {
        // This would fetch from database
        return [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/...',
            'keys' => [
                'p256dh' => 'key...',
                'auth' => 'auth...'
            ]
        ];
    }
    
    /**
     * Create system notification
     */
    public function createSystemNotification($type, $title, $message, $data = []) {
        try {
            // This method can be called from other parts of the system
            // to create automatic notifications
            
            $notification = [
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'created_at' => date('Y-m-d H:i:s'),
                'system_generated' => true
            ];
            
            // Determine recipients based on notification type
            $recipients = $this->getRecipientsForSystemNotification($type, $data);
            
            // Send to recipients
            return $this->sendNotificationToUsers($recipients, $title, $message, $type, $data['action_url'] ?? null);
            
        } catch (Exception $e) {
            error_log("System notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recipients for system notifications
     */
    private function getRecipientsForSystemNotification($type, $data) {
        switch ($type) {
            case 'new_member':
                // Send to admins
                return $this->getAdminUserIds();
            case 'new_complaint':
                // Send to complaint handlers
                return $this->getComplaintHandlerIds();
            case 'event_reminder':
                // Send to event participants
                return $data['participant_ids'] ?? [];
            default:
                return [];
        }
    }
    
    /**
     * Get admin user IDs
     */
    private function getAdminUserIds() {
        // This would fetch from database
        return ['admin1', 'admin2'];
    }
    
    /**
     * Get complaint handler IDs
     */
    private function getComplaintHandlerIds() {
        // This would fetch from database
        return ['handler1', 'handler2'];
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        $this->jsonError($message, $code);
    }
    
    /**
     * Send success response
     */
    private function sendResponse($data, $message = 'Success', $code = 200) {
        $this->jsonSuccess($message, $data, $code);
    }
}
?>