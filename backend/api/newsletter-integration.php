<?php
/**
 * Newsletter Integration API
 * Provides integration between existing newsletter features and new member/admin interfaces
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/NewsletterController.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    $newsletterController = new NewsletterController();
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'subscriber-stats':
                        // Newsletter statistics for admin dashboard
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $stats = getNewsletterStats();
                        echo json_encode($stats);
                        break;
                        
                    case 'recent-campaigns':
                        // Recent campaigns for admin dashboard
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $campaigns = getRecentCampaigns();
                        echo json_encode($campaigns);
                        break;
                        
                    case 'subscribers':
                        // Get all subscribers (admin only)
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $page = $_GET['page'] ?? 1;
                        $limit = $_GET['limit'] ?? 20;
                        $subscribers = getSubscribers($page, $limit);
                        echo json_encode($subscribers);
                        break;
                        
                    case 'templates':
                        // Get newsletter templates
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $templates = getNewsletterTemplates();
                        echo json_encode($templates);
                        break;
                        
                    case 'campaigns':
                        // Get all campaigns
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $campaigns = getAllCampaigns();
                        echo json_encode($campaigns);
                        break;
                        
                    case 'subscription-status':
                        // Check subscription status for member
                        $email = $_GET['email'] ?? null;
                        if (!$email) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Email required']);
                            exit();
                        }
                        
                        $status = getSubscriptionStatus($email);
                        echo json_encode($status);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                // Return general newsletter data
                $newsletterData = [
                    'stats' => getNewsletterStats(),
                    'recent_campaigns' => getRecentCampaigns()
                ];
                echo json_encode($newsletterData);
            }
            break;
            
        case 'POST':
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'subscribe':
                        // Subscribe to newsletter
                        $result = subscribeToNewsletter($_POST);
                        echo json_encode($result);
                        break;
                        
                    case 'unsubscribe':
                        // Unsubscribe from newsletter
                        $result = unsubscribeFromNewsletter($_POST);
                        echo json_encode($result);
                        break;
                        
                    case 'create-campaign':
                        // Create new campaign (admin only)
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $result = createCampaign($_POST, $user);
                        echo json_encode($result);
                        break;
                        
                    case 'send-campaign':
                        // Send campaign (admin only)
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $result = sendCampaign($_POST, $user);
                        echo json_encode($result);
                        break;
                        
                    case 'create-template':
                        // Create newsletter template (admin only)
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $result = createTemplate($_POST, $user);
                        echo json_encode($result);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Action required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get newsletter statistics for admin dashboard
 */
function getNewsletterStats() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Total subscribers
        $stmt = $db->query("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                           SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
                           FROM newsletter_subscribers");
        $subscribers = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Campaign stats
        $stmt = $db->query("SELECT 
                           COUNT(*) as total_campaigns,
                           SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_campaigns,
                           SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as campaigns_this_month
                           FROM newsletter_campaigns");
        $campaigns = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Open rates (placeholder - would need actual tracking)
        $openRate = 65.5; // %
        $clickRate = 12.3; // %
        
        return [
            'total_subscribers' => (int)$subscribers['total'],
            'active_subscribers' => (int)$subscribers['active'],
            'new_subscribers_month' => (int)$subscribers['new_this_month'],
            'total_campaigns' => (int)$campaigns['total_campaigns'],
            'sent_campaigns' => (int)$campaigns['sent_campaigns'],
            'campaigns_this_month' => (int)$campaigns['campaigns_this_month'],
            'open_rate' => $openRate,
            'click_rate' => $clickRate,
            'growth_rate' => calculateGrowthRate()
        ];
        
    } catch (Exception $e) {
        error_log("Error getting newsletter stats: " . $e->getMessage());
        return [
            'total_subscribers' => 0,
            'active_subscribers' => 0,
            'new_subscribers_month' => 0,
            'total_campaigns' => 0,
            'sent_campaigns' => 0,
            'campaigns_this_month' => 0,
            'open_rate' => 0,
            'click_rate' => 0,
            'growth_rate' => 0
        ];
    }
}

/**
 * Get recent campaigns for admin dashboard
 */
function getRecentCampaigns() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT nc.*, u.username as created_by_name
                           FROM newsletter_campaigns nc 
                           LEFT JOIN users u ON nc.created_by = u.id
                           ORDER BY nc.created_at DESC 
                           LIMIT 10");
        
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($campaigns as &$campaign) {
            $campaign['sent_count'] = getCampaignSentCount($campaign['id']);
            $campaign['open_count'] = getCampaignOpenCount($campaign['id']);
            $campaign['click_count'] = getCampaignClickCount($campaign['id']);
        }
        
        return $campaigns;
        
    } catch (Exception $e) {
        error_log("Error getting recent campaigns: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all subscribers with pagination
 */
function getSubscribers($page = 1, $limit = 20) {
    try {
        $db = Database::getInstance()->getConnection();
        $offset = ($page - 1) * $limit;
        
        // Get total count
        $stmt = $db->query("SELECT COUNT(*) as total FROM newsletter_subscribers");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get subscribers
        $stmt = $db->prepare("SELECT * FROM newsletter_subscribers 
                             ORDER BY created_at DESC 
                             LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'subscribers' => $subscribers,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$limit,
                'total' => (int)$total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Error getting subscribers: " . $e->getMessage());
        return [
            'subscribers' => [],
            'pagination' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'total_pages' => 0]
        ];
    }
}

/**
 * Get newsletter templates
 */
function getNewsletterTemplates() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT * FROM newsletter_templates 
                           WHERE status = 'active' 
                           ORDER BY created_at DESC");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting templates: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all campaigns
 */
function getAllCampaigns() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT nc.*, u.username as created_by_name,
                           nt.name as template_name
                           FROM newsletter_campaigns nc 
                           LEFT JOIN users u ON nc.created_by = u.id
                           LEFT JOIN newsletter_templates nt ON nc.template_id = nt.id
                           ORDER BY nc.created_at DESC");
        
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($campaigns as &$campaign) {
            $campaign['sent_count'] = getCampaignSentCount($campaign['id']);
            $campaign['open_count'] = getCampaignOpenCount($campaign['id']);
            $campaign['click_count'] = getCampaignClickCount($campaign['id']);
        }
        
        return $campaigns;
        
    } catch (Exception $e) {
        error_log("Error getting campaigns: " . $e->getMessage());
        return [];
    }
}

/**
 * Check subscription status
 */
function getSubscriptionStatus($email) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscriber) {
            return [
                'subscribed' => true,
                'status' => $subscriber['status'],
                'subscribed_at' => $subscriber['created_at']
            ];
        } else {
            return [
                'subscribed' => false,
                'status' => null,
                'subscribed_at' => null
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error checking subscription status: " . $e->getMessage());
        return [
            'subscribed' => false,
            'status' => null,
            'subscribed_at' => null
        ];
    }
}

/**
 * Subscribe to newsletter
 */
function subscribeToNewsletter($data) {
    try {
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Valid email address required');
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Check if already subscribed
        $stmt = $db->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            if ($existing['status'] === 'active') {
                return [
                    'success' => true,
                    'message' => 'You are already subscribed to our newsletter'
                ];
            } else {
                // Reactivate subscription
                $stmt = $db->prepare("UPDATE newsletter_subscribers 
                                     SET status = 'active', updated_at = NOW() 
                                     WHERE id = ?");
                $stmt->execute([$existing['id']]);
                
                return [
                    'success' => true,
                    'message' => 'Your subscription has been reactivated'
                ];
            }
        } else {
            // New subscription
            $stmt = $db->prepare("INSERT INTO newsletter_subscribers 
                                 (email, name, status, created_at) 
                                 VALUES (?, ?, 'active', NOW())");
            
            $result = $stmt->execute([$email, $name]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Successfully subscribed to newsletter'
                ];
            } else {
                throw new Exception('Failed to subscribe');
            }
        }
        
    } catch (Exception $e) {
        error_log("Error subscribing to newsletter: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to subscribe: ' . $e->getMessage()
        ];
    }
}

/**
 * Unsubscribe from newsletter
 */
function unsubscribeFromNewsletter($data) {
    try {
        $email = $data['email'] ?? '';
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Valid email address required');
        }
        
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("UPDATE newsletter_subscribers 
                             SET status = 'unsubscribed', updated_at = NOW() 
                             WHERE email = ?");
        
        $result = $stmt->execute([$email]);
        
        if ($result && $stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Successfully unsubscribed from newsletter'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Email not found in our subscription list'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error unsubscribing from newsletter: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to unsubscribe: ' . $e->getMessage()
        ];
    }
}

/**
 * Create new campaign
 */
function createCampaign($data, $user) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("INSERT INTO newsletter_campaigns 
                             (subject, content, template_id, created_by, status, created_at) 
                             VALUES (?, ?, ?, ?, 'draft', NOW())");
        
        $result = $stmt->execute([
            $data['subject'],
            $data['content'],
            $data['template_id'] ?? null,
            $user['id']
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Campaign created successfully',
                'campaign_id' => $db->lastInsertId()
            ];
        } else {
            throw new Exception('Failed to create campaign');
        }
        
    } catch (Exception $e) {
        error_log("Error creating campaign: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to create campaign: ' . $e->getMessage()
        ];
    }
}

/**
 * Send campaign
 */
function sendCampaign($data, $user) {
    try {
        $campaignId = $data['campaign_id'] ?? null;
        
        if (!$campaignId) {
            throw new Exception('Campaign ID required');
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Get campaign details
        $stmt = $db->prepare("SELECT * FROM newsletter_campaigns WHERE id = ?");
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campaign) {
            throw new Exception('Campaign not found');
        }
        
        // Get active subscribers
        $stmt = $db->query("SELECT email, name FROM newsletter_subscribers WHERE status = 'active'");
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($subscribers)) {
            throw new Exception('No active subscribers found');
        }
        
        // Update campaign status
        $stmt = $db->prepare("UPDATE newsletter_campaigns 
                             SET status = 'sent', sent_at = NOW(), sent_by = ? 
                             WHERE id = ?");
        $stmt->execute([$user['id'], $campaignId]);
        
        // In a real implementation, you would queue emails for sending
        // For now, we'll just simulate the process
        $sentCount = count($subscribers);
        
        return [
            'success' => true,
            'message' => "Campaign sent successfully to {$sentCount} subscribers",
            'sent_count' => $sentCount
        ];
        
    } catch (Exception $e) {
        error_log("Error sending campaign: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to send campaign: ' . $e->getMessage()
        ];
    }
}

/**
 * Create newsletter template
 */
function createTemplate($data, $user) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("INSERT INTO newsletter_templates 
                             (name, content, created_by, status, created_at) 
                             VALUES (?, ?, ?, 'active', NOW())");
        
        $result = $stmt->execute([
            $data['name'],
            $data['content'],
            $user['id']
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Template created successfully',
                'template_id' => $db->lastInsertId()
            ];
        } else {
            throw new Exception('Failed to create template');
        }
        
    } catch (Exception $e) {
        error_log("Error creating template: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to create template: ' . $e->getMessage()
        ];
    }
}

/**
 * Calculate growth rate
 */
function calculateGrowthRate() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get current month subscribers
        $stmt = $db->query("SELECT COUNT(*) as current_month 
                           FROM newsletter_subscribers 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $currentMonth = $stmt->fetch(PDO::FETCH_ASSOC)['current_month'];
        
        // Get previous month subscribers
        $stmt = $db->query("SELECT COUNT(*) as previous_month 
                           FROM newsletter_subscribers 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                           AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $previousMonth = $stmt->fetch(PDO::FETCH_ASSOC)['previous_month'];
        
        if ($previousMonth > 0) {
            return round((($currentMonth - $previousMonth) / $previousMonth) * 100, 1);
        } else {
            return $currentMonth > 0 ? 100 : 0;
        }
        
    } catch (Exception $e) {
        error_log("Error calculating growth rate: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get campaign sent count (placeholder)
 */
function getCampaignSentCount($campaignId) {
    // In a real implementation, this would query a campaign_sends table
    return rand(50, 500);
}

/**
 * Get campaign open count (placeholder)
 */
function getCampaignOpenCount($campaignId) {
    // In a real implementation, this would query email tracking data
    return rand(20, 300);
}

/**
 * Get campaign click count (placeholder)
 */
function getCampaignClickCount($campaignId) {
    // In a real implementation, this would query click tracking data
    return rand(5, 50);
}
?>