<?php
/**
 * Newsletter Model
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/BaseModel.php';

class Newsletter extends BaseModel {
    protected $table = 'newsletter_subscriptions';
    protected $fillable = [
        'email', 'name', 'status', 'subscription_date', 'unsubscribe_token',
        'preferences', 'source', 'ip_address'
    ];
    
    /**
     * Subscribe to newsletter
     */
    public function subscribe($email, $name = null, $preferences = []) {
        // Check if already subscribed
        $existing = $this->findByEmail($email);
        
        if ($existing) {
            if ($existing['status'] === 'active') {
                return ['success' => false, 'message' => 'Email already subscribed'];
            } else {
                // Reactivate subscription
                return $this->reactivateSubscription($existing['id']);
            }
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        $data = [
            'email' => $email,
            'name' => $name,
            'status' => 'active',
            'subscription_date' => date('Y-m-d H:i:s'),
            'unsubscribe_token' => $this->generateUnsubscribeToken(),
            'preferences' => json_encode($preferences ?: $this->getDefaultPreferences()),
            'source' => $_POST['source'] ?? 'website',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        
        $subscription = $this->create($data);
        
        if ($subscription) {
            return ['success' => true, 'subscription' => $subscription];
        }
        
        return ['success' => false, 'message' => 'Failed to subscribe'];
    }
    
    /**
     * Unsubscribe from newsletter
     */
    public function unsubscribe($token) {
        $subscription = $this->findByToken($token);
        
        if (!$subscription) {
            return ['success' => false, 'message' => 'Invalid unsubscribe token'];
        }
        
        $updated = $this->update($subscription['id'], [
            'status' => 'unsubscribed',
            'unsubscribed_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($updated) {
            return ['success' => true, 'message' => 'Successfully unsubscribed'];
        }
        
        return ['success' => false, 'message' => 'Failed to unsubscribe'];
    }
    
    /**
     * Get active subscribers
     */
    public function getActiveSubscribers($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY subscription_date DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return dbFetchAll($sql);
    }
    
    /**
     * Get subscription statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total subscribers
        $stats['total'] = $this->count();
        
        // Active subscribers
        $stats['active'] = $this->count(['status' => 'active']);
        
        // Unsubscribed
        $stats['unsubscribed'] = $this->count(['status' => 'unsubscribed']);
        
        // New subscribers this month
        $thisMonth = date('Y-m-01');
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE subscription_date >= ?";
        $result = dbFetch($sql, [$thisMonth]);
        $stats['new_this_month'] = $result['count'] ?? 0;
        
        // Growth rate
        $lastMonth = date('Y-m-01', strtotime('-1 month'));
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE subscription_date >= ? AND subscription_date < ?";
        $result = dbFetch($sql, [$lastMonth, $thisMonth]);
        $lastMonthCount = $result['count'] ?? 0;
        
        if ($lastMonthCount > 0) {
            $stats['growth_rate'] = round((($stats['new_this_month'] - $lastMonthCount) / $lastMonthCount) * 100, 2);
        } else {
            $stats['growth_rate'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Find subscription by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return dbFetch($sql, [$email]);
    }
    
    /**
     * Find subscription by unsubscribe token
     */
    public function findByToken($token) {
        $sql = "SELECT * FROM {$this->table} WHERE unsubscribe_token = ?";
        return dbFetch($sql, [$token]);
    }
    
    /**
     * Reactivate subscription
     */
    private function reactivateSubscription($id) {
        $updated = $this->update($id, [
            'status' => 'active',
            'subscription_date' => date('Y-m-d H:i:s'),
            'unsubscribed_at' => null
        ]);
        
        if ($updated) {
            return ['success' => true, 'message' => 'Subscription reactivated'];
        }
        
        return ['success' => false, 'message' => 'Failed to reactivate subscription'];
    }
    
    /**
     * Generate unique unsubscribe token
     */
    private function generateUnsubscribeToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Get default subscription preferences
     */
    private function getDefaultPreferences() {
        return [
            'events' => true,
            'announcements' => true,
            'newsletters' => true,
            'updates' => true
        ];
    }
    
    /**
     * Update subscription preferences
     */
    public function updatePreferences($email, $preferences) {
        $subscription = $this->findByEmail($email);
        
        if (!$subscription) {
            return ['success' => false, 'message' => 'Subscription not found'];
        }
        
        $updated = $this->update($subscription['id'], [
            'preferences' => json_encode($preferences)
        ]);
        
        if ($updated) {
            return ['success' => true, 'message' => 'Preferences updated'];
        }
        
        return ['success' => false, 'message' => 'Failed to update preferences'];
    }
    
    /**
     * Search subscribers
     */
    public function search($query, $limit = 20, $page = 1) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE (email LIKE ? OR name LIKE ?) 
                ORDER BY subscription_date DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        $searchTerm = "%{$query}%";
        return dbFetchAll($sql, [$searchTerm, $searchTerm]);
    }
    
    /**
     * Get subscribers by preferences
     */
    public function getSubscribersByPreference($preference) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'active' 
                AND JSON_EXTRACT(preferences, '$.{$preference}') = true";
        
        return dbFetchAll($sql);
    }
    
    /**
     * Bulk import subscribers
     */
    public function bulkImport($subscribers) {
        $imported = 0;
        $errors = [];
        
        foreach ($subscribers as $subscriber) {
            $result = $this->subscribe(
                $subscriber['email'],
                $subscriber['name'] ?? null,
                $subscriber['preferences'] ?? []
            );
            
            if ($result['success']) {
                $imported++;
            } else {
                $errors[] = [
                    'email' => $subscriber['email'],
                    'error' => $result['message']
                ];
            }
        }
        
        return [
            'imported' => $imported,
            'errors' => $errors,
            'total' => count($subscribers)
        ];
    }
    
    /**
     * Export subscribers
     */
    public function exportSubscribers($format = 'csv', $status = 'active') {
        $conditions = [];
        if ($status !== 'all') {
            $conditions['status'] = $status;
        }
        
        $subscribers = $this->findAll($conditions, 'subscription_date DESC');
        
        switch ($format) {
            case 'json':
                return json_encode($subscribers);
            case 'csv':
            default:
                return $this->convertToCSV($subscribers);
        }
    }
    
    /**
     * Convert subscribers array to CSV
     */
    private function convertToCSV($subscribers) {
        if (empty($subscribers)) {
            return '';
        }
        
        $csv = "Email,Name,Status,Subscription Date,Preferences\n";
        
        foreach ($subscribers as $subscriber) {
            $preferences = json_decode($subscriber['preferences'] ?? '{}', true);
            $preferencesStr = implode(';', array_keys(array_filter($preferences)));
            
            $csv .= sprintf(
                "%s,%s,%s,%s,%s\n",
                $subscriber['email'],
                $subscriber['name'] ?? '',
                $subscriber['status'],
                $subscriber['subscription_date'],
                $preferencesStr
            );
        }
        
        return $csv;
    }
}

/**
 * Newsletter Campaign Model
 */
class NewsletterCampaign extends BaseModel {
    protected $table = 'newsletter_campaigns';
    protected $fillable = [
        'title', 'subject', 'content', 'status', 'scheduled_at',
        'sent_at', 'created_by', 'recipient_count', 'open_count', 'click_count'
    ];
    
    /**
     * Create new campaign
     */
    public function createCampaign($data) {
        $errors = $this->validate($data, [
            'title' => 'required|max:255',
            'subject' => 'required|max:255',
            'content' => 'required'
        ]);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $data['status'] = 'draft';
        $data['created_by'] = $_SESSION['user_id'] ?? null;
        
        $campaign = $this->create($data);
        
        if ($campaign) {
            return ['success' => true, 'campaign' => $campaign];
        }
        
        return ['success' => false, 'message' => 'Failed to create campaign'];
    }
    
    /**
     * Send campaign
     */
    public function sendCampaign($campaignId, $recipientIds = null) {
        $campaign = $this->find($campaignId);
        
        if (!$campaign) {
            return ['success' => false, 'message' => 'Campaign not found'];
        }
        
        if ($campaign['status'] !== 'draft') {
            return ['success' => false, 'message' => 'Campaign already sent or scheduled'];
        }
        
        // Get recipients
        $newsletterModel = new Newsletter();
        if ($recipientIds) {
            $recipients = $newsletterModel->findAll(['id' => $recipientIds, 'status' => 'active']);
        } else {
            $recipients = $newsletterModel->getActiveSubscribers();
        }
        
        if (empty($recipients)) {
            return ['success' => false, 'message' => 'No active recipients found'];
        }
        
        // Update campaign status
        $this->update($campaignId, [
            'status' => 'sending',
            'recipient_count' => count($recipients)
        ]);
        
        // Send emails (this would be done in background job in production)
        $sentCount = $this->sendEmails($campaign, $recipients);
        
        // Update campaign status
        $this->update($campaignId, [
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'recipient_count' => $sentCount
        ]);
        
        return ['success' => true, 'sent_count' => $sentCount];
    }
    
    /**
     * Send emails to recipients
     */
    private function sendEmails($campaign, $recipients) {
        $sentCount = 0;
        
        foreach ($recipients as $recipient) {
            $personalizedContent = $this->personalizeContent($campaign['content'], $recipient);
            
            $headers = [
                'From: BHRC <newsletter@bhrcindia.in>',
                'Reply-To: noreply@bhrcindia.in',
                'Content-Type: text/html; charset=UTF-8'
            ];
            
            if (mail($recipient['email'], $campaign['subject'], $personalizedContent, implode("\r\n", $headers))) {
                $sentCount++;
            }
        }
        
        return $sentCount;
    }
    
    /**
     * Personalize email content
     */
    private function personalizeContent($content, $recipient) {
        $replacements = [
            '{{name}}' => $recipient['name'] ?: 'Subscriber',
            '{{email}}' => $recipient['email'],
            '{{unsubscribe_url}}' => "https://bhrcindia.in/unsubscribe?token=" . $recipient['unsubscribe_token']
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Get campaign statistics
     */
    public function getCampaignStats($campaignId) {
        $campaign = $this->find($campaignId);
        
        if (!$campaign) {
            return null;
        }
        
        return [
            'recipient_count' => $campaign['recipient_count'] ?? 0,
            'open_count' => $campaign['open_count'] ?? 0,
            'click_count' => $campaign['click_count'] ?? 0,
            'open_rate' => $campaign['recipient_count'] > 0 ? 
                round(($campaign['open_count'] / $campaign['recipient_count']) * 100, 2) : 0,
            'click_rate' => $campaign['recipient_count'] > 0 ? 
                round(($campaign['click_count'] / $campaign['recipient_count']) * 100, 2) : 0
        ];
    }
    
    /**
     * Check if email is subscribed
     */
    public function isSubscribed($email) {
        try {
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("SELECT COUNT(*) FROM {$this->table} WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Check subscription error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all active subscribers
     */
    public function getSubscribers($limit = null, $offset = 0) {
        try {
            $connection = $this->db->getConnection();
            $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY subscription_date DESC";
            
            if ($limit) {
                $sql .= " LIMIT ? OFFSET ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$limit, $offset]);
            } else {
                $stmt = $connection->prepare($sql);
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get subscribers error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get count of active subscribers
     */
    public function getSubscribersCount() {
        try {
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Get subscribers count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update a campaign
     */
    public function updateCampaign($id, $data) {
        try {
            $connection = $this->db->getConnection();
            
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            $setClause = [];
            foreach ($data as $key => $value) {
                $setClause[] = "{$key} = :{$key}";
            }
            
            $stmt = $connection->prepare("
                UPDATE newsletter_campaigns 
                SET " . implode(', ', $setClause) . " 
                WHERE id = :id
            ");
            
            $data['id'] = $id;
            return $stmt->execute($data);
        } catch (Exception $e) {
            error_log("Update campaign error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log campaign send
     */
    public function logCampaignSend($campaignId, $email, $status = 'sent') {
        try {
            $connection = $this->db->getConnection();
            
            $data = [
                'campaign_id' => $campaignId,
                'email' => $email,
                'status' => $status,
                'sent_at' => date('Y-m-d H:i:s')
            ];
            
            $stmt = $connection->prepare("
                INSERT INTO newsletter_campaign_logs (campaign_id, email, status, sent_at) 
                VALUES (:campaign_id, :email, :status, :sent_at)
            ");
            
            return $stmt->execute($data);
        } catch (Exception $e) {
            error_log("Log campaign send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get campaigns with pagination
     */
    public function getCampaigns($limit = 20, $offset = 0, $status = '') {
        try {
            $connection = $this->db->getConnection();
            
            $whereClause = '';
            $params = [$limit, $offset];
            
            if (!empty($status)) {
                $whereClause = 'WHERE status = ?';
                array_unshift($params, $status);
            }
            
            $stmt = $connection->prepare("
                SELECT * FROM newsletter_campaigns 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get campaigns error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get campaigns count
     */
    public function getCampaignsCount($status = '') {
        try {
            $connection = $this->db->getConnection();
            
            $whereClause = '';
            $params = [];
            
            if (!empty($status)) {
                $whereClause = 'WHERE status = ?';
                $params[] = $status;
            }
            
            $stmt = $connection->prepare("SELECT COUNT(*) FROM newsletter_campaigns {$whereClause}");
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Get campaigns count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get campaign by ID
     */
    public function getCampaignById($id) {
        try {
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("SELECT * FROM newsletter_campaigns WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Get campaign by ID error: " . $e->getMessage());
            return null;
        }
    }
    
}
?>