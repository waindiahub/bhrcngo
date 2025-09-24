<?php
/**
 * Admin Dashboard API
 * Provides comprehensive data for admin dashboard functionality
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/MemberController.php';
require_once __DIR__ . '/../controllers/ComplaintController.php';
require_once __DIR__ . '/../controllers/EventController.php';
require_once __DIR__ . '/../controllers/DonationController.php';
require_once __DIR__ . '/../controllers/GalleryController.php';
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
    // Verify authentication and admin privileges
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'overview-stats':
                        $stats = getOverviewStats();
                        echo json_encode($stats);
                        break;
                        
                    case 'chart-data':
                        $chartData = getChartData();
                        echo json_encode($chartData);
                        break;
                        
                    case 'recent-activities':
                        $activities = getRecentActivities();
                        echo json_encode($activities);
                        break;
                        
                    case 'system-health':
                        $health = getSystemHealth();
                        echo json_encode($health);
                        break;
                        
                    case 'analytics-data':
                        $analytics = getAnalyticsData();
                        echo json_encode($analytics);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                // Get gallery statistics
                $gallery_stats = getGalleryStats();
                
                // Get newsletter statistics  
                $newsletter_stats = getNewsletterStats();
                
                // Return full dashboard data
                $dashboardData = [
                    'overview_stats' => getOverviewStats(),
                    'chart_data' => getChartData(),
                    'recent_activities' => getRecentActivities(),
                    'system_health' => getSystemHealth(),
                    'gallery_stats' => $gallery_stats,
                    'newsletter_stats' => $newsletter_stats
                ];
                echo json_encode($dashboardData);
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
 * Get overview statistics for admin dashboard
 */
function getOverviewStats() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Complaints statistics
        $stmt = $db->query("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                           SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent
                           FROM complaints");
        $complaints = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Events statistics
        $stmt = $db->query("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN event_date > NOW() THEN 1 ELSE 0 END) as upcoming,
                           SUM(CASE WHEN event_date < NOW() THEN 1 ELSE 0 END) as completed
                           FROM events");
        $events = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Members statistics
        $stmt = $db->query("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                           SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
                           FROM members");
        $members = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Donations statistics
        $stmt = $db->query("SELECT 
                           COUNT(*) as total,
                           COALESCE(SUM(amount), 0) as total_amount,
                           COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END), 0) as this_month
                           FROM donations WHERE status = 'completed'");
        $donations = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Activities statistics
        $stmt = $db->query("SELECT COUNT(*) as total FROM activities");
        $activities = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Gallery statistics
        $stmt = $db->query("SELECT 
                           COUNT(*) as photos FROM gallery_items WHERE type = 'photo'");
        $photos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->query("SELECT 
                           COUNT(*) as videos FROM gallery_items WHERE type = 'video'");
        $videos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Newsletter statistics
        $stmt = $db->query("SELECT COUNT(*) as subscribers FROM newsletter_subscribers WHERE status = 'active'");
        $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'complaints' => [
                'total' => (int)$complaints['total'],
                'pending' => (int)$complaints['pending'],
                'resolved' => (int)$complaints['resolved'],
                'recent' => (int)$complaints['recent']
            ],
            'events' => [
                'total' => (int)$events['total'],
                'upcoming' => (int)$events['upcoming'],
                'completed' => (int)$events['completed']
            ],
            'members' => [
                'total' => (int)$members['total'],
                'active' => (int)$members['active'],
                'pending' => (int)$members['pending'],
                'new_this_month' => (int)$members['new_this_month']
            ],
            'donations' => [
                'total' => (int)$donations['total'],
                'total_amount' => (float)$donations['total_amount'],
                'this_month' => (float)$donations['this_month']
            ],
            'activities' => [
                'total' => (int)$activities['total']
            ],
            'gallery' => [
                'photos' => (int)$photos['photos'],
                'videos' => (int)$videos['videos'],
                'total' => (int)$photos['photos'] + (int)$videos['videos']
            ],
            'newsletter' => [
                'subscribers' => (int)$newsletter['subscribers']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Error getting overview stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get chart data for dashboard visualizations
 */
function getChartData() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Complaints trend (last 12 months)
        $stmt = $db->query("SELECT 
                           DATE_FORMAT(created_at, '%Y-%m') as month,
                           COUNT(*) as count
                           FROM complaints 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                           GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                           ORDER BY month");
        $complaintsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Donations trend (last 12 months)
        $stmt = $db->query("SELECT 
                           DATE_FORMAT(created_at, '%Y-%m') as month,
                           COUNT(*) as count,
                           COALESCE(SUM(amount), 0) as amount
                           FROM donations 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND status = 'completed'
                           GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                           ORDER BY month");
        $donationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Member growth (last 12 months)
        $stmt = $db->query("SELECT 
                           DATE_FORMAT(created_at, '%Y-%m') as month,
                           COUNT(*) as count
                           FROM members 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                           GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                           ORDER BY month");
        $membersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Complaint status distribution
        $stmt = $db->query("SELECT status, COUNT(*) as count FROM complaints GROUP BY status");
        $complaintStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'complaints_trend' => $complaintsData,
            'donations_trend' => $donationsData,
            'members_growth' => $membersData,
            'complaint_status' => $complaintStatus
        ];
        
    } catch (Exception $e) {
        error_log("Error getting chart data: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent activities across the system
 */
function getRecentActivities() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $activities = [];
        
        // Recent complaints
        $stmt = $db->query("SELECT 'complaint' as type, id, title as description, 
                           created_at, status, 'fas fa-exclamation-triangle' as icon
                           FROM complaints 
                           ORDER BY created_at DESC LIMIT 5");
        $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $activities = array_merge($activities, $complaints);
        
        // Recent member registrations
        $stmt = $db->query("SELECT 'member' as type, id, 
                           CONCAT(first_name, ' ', last_name) as description,
                           created_at, status, 'fas fa-user-plus' as icon
                           FROM members 
                           ORDER BY created_at DESC LIMIT 5");
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($members as &$member) {
            $member['description'] = 'New member: ' . $member['description'];
        }
        $activities = array_merge($activities, $members);
        
        // Recent donations
        $stmt = $db->query("SELECT 'donation' as type, id, 
                           CONCAT('₹', FORMAT(amount, 0), ' - ', purpose) as description,
                           created_at, status, 'fas fa-heart' as icon
                           FROM donations 
                           ORDER BY created_at DESC LIMIT 5");
        $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($donations as &$donation) {
            $donation['description'] = 'Donation: ' . $donation['description'];
        }
        $activities = array_merge($activities, $donations);
        
        // Recent events
        $stmt = $db->query("SELECT 'event' as type, id, title as description,
                           created_at, 'active' as status, 'fas fa-calendar' as icon
                           FROM events 
                           ORDER BY created_at DESC LIMIT 5");
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($events as &$event) {
            $event['description'] = 'Event created: ' . $event['description'];
        }
        $activities = array_merge($activities, $events);
        
        // Sort by date and limit
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return array_slice($activities, 0, 15);
        
    } catch (Exception $e) {
        error_log("Error getting recent activities: " . $e->getMessage());
        return [];
    }
}

/**
 * Get system health metrics
 */
function getSystemHealth() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Database connection status
        $dbStatus = $db ? 'healthy' : 'error';
        
        // Check recent error logs (if logging is implemented)
        $errorCount = 0; // Placeholder
        
        // Check disk usage (placeholder)
        $diskUsage = 45; // Percentage
        
        // Check response time (placeholder)
        $responseTime = 120; // milliseconds
        
        // Check active sessions
        $stmt = $db->query("SELECT COUNT(*) as count FROM user_sessions WHERE expires_at > NOW()");
        $activeSessions = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'database' => [
                'status' => $dbStatus,
                'response_time' => $responseTime
            ],
            'system' => [
                'disk_usage' => $diskUsage,
                'active_sessions' => (int)$activeSessions['count'],
                'error_count' => $errorCount
            ],
            'overall_status' => $dbStatus === 'healthy' && $diskUsage < 80 ? 'healthy' : 'warning'
        ];
        
    } catch (Exception $e) {
        error_log("Error getting system health: " . $e->getMessage());
        return [
            'database' => ['status' => 'error', 'response_time' => 0],
            'system' => ['disk_usage' => 0, 'active_sessions' => 0, 'error_count' => 1],
            'overall_status' => 'error'
        ];
    }
}

/**
 * Get analytics data for reports
 */
function getAnalyticsData() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Regional distribution of members
        $stmt = $db->query("SELECT state, COUNT(*) as count FROM members GROUP BY state ORDER BY count DESC LIMIT 10");
        $regionalData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Monthly performance metrics
        $stmt = $db->query("SELECT 
                           DATE_FORMAT(created_at, '%Y-%m') as month,
                           COUNT(*) as complaints,
                           (SELECT COUNT(*) FROM members WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(c.created_at, '%Y-%m')) as members,
                           (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(c.created_at, '%Y-%m') AND status = 'completed') as donations
                           FROM complaints c
                           WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                           GROUP BY DATE_FORMAT(c.created_at, '%Y-%m')
                           ORDER BY month");
        $monthlyMetrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'regional_distribution' => $regionalData,
            'monthly_metrics' => $monthlyMetrics
        ];
        
    } catch (Exception $e) {
        error_log("Error getting analytics data: " . $e->getMessage());
        return [];
    }
}

/**
 * Get gallery statistics for admin dashboard
 */
function getGalleryStats() {
    try {
        // In a real implementation, you would make an internal API call to gallery-integration.php
        // For now, we'll return mock data similar to what the gallery API would provide
        return [
            'total_items' => 156,
            'photos' => 134,
            'videos' => 22,
            'albums' => 12,
            'recent_uploads' => 8,
            'storage' => [
                'used' => 2.4,
                'limit' => 10,
                'percentage' => 24.0
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Error getting gallery stats: " . $e->getMessage());
        return [
            'total_items' => 0,
            'photos' => 0,
            'videos' => 0,
            'albums' => 0,
            'recent_uploads' => 0,
            'storage' => ['used' => 0, 'limit' => 10, 'percentage' => 0]
        ];
    }
}

/**
 * Get newsletter statistics for admin dashboard
 */
function getNewsletterStats() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get subscriber stats
        $stmt = $db->query("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                           SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
                           FROM newsletter_subscribers");
        $subscribers = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get campaign stats
        $stmt = $db->query("SELECT 
                           COUNT(*) as total_campaigns,
                           SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_campaigns,
                           SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as campaigns_this_month
                           FROM newsletter_campaigns");
        $campaigns = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_subscribers' => (int)($subscribers['total'] ?? 0),
            'active_subscribers' => (int)($subscribers['active'] ?? 0),
            'new_subscribers_month' => (int)($subscribers['new_this_month'] ?? 0),
            'total_campaigns' => (int)($campaigns['total_campaigns'] ?? 0),
            'sent_campaigns' => (int)($campaigns['sent_campaigns'] ?? 0),
            'campaigns_this_month' => (int)($campaigns['campaigns_this_month'] ?? 0),
            'open_rate' => 65.5,
            'click_rate' => 12.3,
            'growth_rate' => calculateNewsletterGrowthRate()
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
 * Calculate newsletter growth rate
 */
function calculateNewsletterGrowthRate() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get current month subscribers
        $stmt = $db->query("SELECT COUNT(*) as current_month 
                           FROM newsletter_subscribers 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $currentMonth = $stmt->fetch(PDO::FETCH_ASSOC)['current_month'] ?? 0;
        
        // Get previous month subscribers
        $stmt = $db->query("SELECT COUNT(*) as previous_month 
                           FROM newsletter_subscribers 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
                           AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $previousMonth = $stmt->fetch(PDO::FETCH_ASSOC)['previous_month'] ?? 0;
        
        if ($previousMonth > 0) {
            return round((($currentMonth - $previousMonth) / $previousMonth) * 100, 1);
        } else {
            return $currentMonth > 0 ? 100 : 0;
        }
        
    } catch (Exception $e) {
        error_log("Error calculating newsletter growth rate: " . $e->getMessage());
        return 0;
    }
}
?>