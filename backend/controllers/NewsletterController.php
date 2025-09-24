<?php
/**
 * Newsletter Controller
 * Handles newsletter subscriptions and campaigns
 */

require_once __DIR__ . '/../models/Newsletter.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/BaseController.php';

class NewsletterController extends BaseController {
    
    /**
     * @var Newsletter
     */
    private Newsletter $newsletterModel;
    
    /**
     * @var EmailService
     */
    private EmailService $emailService;
    
    public function __construct() {
        parent::__construct();
        $this->newsletterModel = new Newsletter();
        $this->emailService = new EmailService();
    }
    
    /**
     * Subscribe to newsletter (public endpoint)
     */
    public function subscribe() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['email'])) {
                $this->sendError('Email is required', 400);
                return;
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendError('Invalid email format', 400);
                return;
            }
            
            // Check if already subscribed
            if ($this->newsletterModel->isSubscribed($data['email'])) {
                $this->sendError('Email is already subscribed', 400);
                return;
            }
            
            $subscriptionData = [
                'email' => trim($data['email']),
                'name' => isset($data['name']) ? htmlspecialchars(trim($data['name'])) : null,
                'status' => 'active',
                'subscribed_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            
            $subscriptionId = $this->newsletterModel->subscribe($subscriptionData);
            
            if ($subscriptionId) {
                // Send welcome email
                $this->sendWelcomeEmail($data['email'], $data['name'] ?? '');
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Successfully subscribed to newsletter',
                    'subscription_id' => $subscriptionId
                ], 201);
            } else {
                $this->sendError('Failed to subscribe to newsletter');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to subscribe: ' . $e->getMessage());
        }
    }
    
    /**
     * Unsubscribe from newsletter (public endpoint)
     */
    public function unsubscribe() {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['email'])) {
                $this->sendError('Email is required', 400);
                return;
            }
            
            if ($this->newsletterModel->unsubscribe($data['email'])) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Successfully unsubscribed from newsletter'
                ]);
            } else {
                $this->sendError('Email not found or already unsubscribed');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to unsubscribe: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all subscribers (admin only)
     */
    public function getSubscribers() {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 50);
            $status = $_GET['status'] ?? 'active';
            $search = $_GET['search'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $subscribers = $this->newsletterModel->getSubscribers($limit, $offset, $status, $search);
            $total = $this->newsletterModel->getSubscribersCount($status, $search);
            
            $this->sendResponse([
                'success' => true,
                'data' => $subscribers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch subscribers: ' . $e->getMessage());
        }
    }
    
    /**
     * Send newsletter campaign (admin only)
     */
    public function sendNewsletter() {
        try {
            $data = $this->getJsonInput();
            
            // Validate required fields
            $required = ['subject', 'content'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->sendError("Field '$field' is required", 400);
                    return;
                }
            }
            
            $campaignData = [
                'subject' => htmlspecialchars(trim($data['subject'])),
                'content' => $data['content'], // HTML content
                'sender_id' => $_SESSION['user_id'] ?? null,
                'status' => 'draft',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Create campaign record
            $campaignId = $this->newsletterModel->createCampaign($campaignData);
            
            if (!$campaignId) {
                $this->sendError('Failed to create campaign');
                return;
            }
            
            // Get active subscribers
            $subscribers = $this->newsletterModel->getActiveSubscribers();
            
            if (empty($subscribers)) {
                $this->sendError('No active subscribers found');
                return;
            }
            
            // Update campaign status to sending
            $this->newsletterModel->updateCampaign($campaignId, [
                'status' => 'sending',
                'started_at' => date('Y-m-d H:i:s'),
                'total_recipients' => count($subscribers)
            ]);
            
            $sentCount = 0;
            $failedCount = 0;
            
            // Send emails to all subscribers
            foreach ($subscribers as $subscriber) {
                try {
                    $personalizedContent = $this->personalizeContent($data['content'], $subscriber);
                    
                    if ($this->emailService->send($subscriber['email'], $data['subject'], $personalizedContent)) {
                        $sentCount++;
                        
                        // Log successful send
                        $this->newsletterModel->logCampaignSend($campaignId, $subscriber['id'], 'sent');
                    } else {
                        $failedCount++;
                        
                        // Log failed send
                        $this->newsletterModel->logCampaignSend($campaignId, $subscriber['id'], 'failed');
                    }
                    
                } catch (Exception $e) {
                    $failedCount++;
                    error_log('Failed to send newsletter to ' . $subscriber['email'] . ': ' . $e->getMessage());
                }
            }
            
            // Update campaign with final stats
            $this->newsletterModel->updateCampaign($campaignId, [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'sent_count' => $sentCount,
                'failed_count' => $failedCount
            ]);
            
            $this->sendResponse([
                'success' => true,
                'message' => 'Newsletter campaign completed',
                'data' => [
                    'campaign_id' => $campaignId,
                    'total_recipients' => count($subscribers),
                    'sent_count' => $sentCount,
                    'failed_count' => $failedCount
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to send newsletter: ' . $e->getMessage());
        }
    }
    
    /**
     * Get newsletter campaigns (admin only)
     */
    public function getCampaigns() {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $status = $_GET['status'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $campaigns = $this->newsletterModel->getCampaigns($limit, $offset, $status);
            $total = $this->newsletterModel->getCampaignsCount($status);
            
            $this->sendResponse([
                'success' => true,
                'data' => $campaigns,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch campaigns: ' . $e->getMessage());
        }
    }
    
    /**
     * Get campaign details (admin only)
     */
    public function getCampaign($id) {
        try {
            $campaign = $this->newsletterModel->getCampaignById($id);
            
            if (!$campaign) {
                $this->sendError('Campaign not found', 404);
                return;
            }
            
            // Get campaign statistics
            $stats = $this->newsletterModel->getCampaignStats($id);
            $campaign['stats'] = $stats;
            
            $this->sendResponse([
                'success' => true,
                'data' => $campaign
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch campaign: ' . $e->getMessage());
        }
    }
    
    /**
     * Get newsletter statistics (admin only)
     */
    public function getStats() {
        try {
            $stats = $this->newsletterModel->getStats();
            
            $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch statistics: ' . $e->getMessage());
        }
    }
    
    /**
     * Send welcome email to new subscriber
     */
    private function sendWelcomeEmail($email, $name) {
        try {
            $subject = 'Welcome to BHRC Newsletter';
            $message = "
                <h3>Welcome to BHRC Newsletter!</h3>
                <p>Dear " . ($name ?: 'Subscriber') . ",</p>
                <p>Thank you for subscribing to our newsletter. You will now receive updates about our activities, events, and important announcements.</p>
                <p>If you wish to unsubscribe at any time, you can do so by clicking the unsubscribe link in any of our emails.</p>
                <p>Best regards,<br>BHRC Team</p>
            ";
            
            $this->emailService->send($email, $subject, $message);
            
        } catch (Exception $e) {
            error_log('Failed to send welcome email: ' . $e->getMessage());
        }
    }
    
    /**
     * Personalize newsletter content
     */
    private function personalizeContent($content, $subscriber) {
        $personalizedContent = $content;
        
        // Replace placeholders with subscriber data
        $personalizedContent = str_replace('{{name}}', $subscriber['name'] ?: 'Subscriber', $personalizedContent);
        $personalizedContent = str_replace('{{email}}', $subscriber['email'], $personalizedContent);
        
        // Add unsubscribe link
        $unsubscribeUrl = 'https://bhrcindia.in/unsubscribe?email=' . urlencode($subscriber['email']);
        $unsubscribeLink = '<p><a href="' . $unsubscribeUrl . '">Unsubscribe from this newsletter</a></p>';
        
        // Add unsubscribe link before closing body tag or at the end
        if (strpos($personalizedContent, '</body>') !== false) {
            $personalizedContent = str_replace('</body>', $unsubscribeLink . '</body>', $personalizedContent);
        } else {
            $personalizedContent .= $unsubscribeLink;
        }
        
        return $personalizedContent;
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        return $this->jsonError($message, $code);
    }
    
    /**
     * Send success response
     */
    private function sendResponse($data, $message = 'Success', $code = 200) {
        return $this->jsonSuccess($data, $message, $code);
    }
}
?>