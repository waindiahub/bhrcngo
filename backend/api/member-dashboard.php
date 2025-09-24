<?php
/**
 * Member Dashboard API
 * Provides data for member dashboard functionality
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/MemberController.php';
require_once __DIR__ . '/../controllers/ComplaintController.php';
require_once __DIR__ . '/../controllers/EventController.php';
require_once __DIR__ . '/../controllers/DonationController.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Verify authentication
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    $memberController = new MemberController();
    $complaintController = new ComplaintController();
    $eventController = new EventController();
    $donationController = new DonationController();
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'dashboard-stats':
                        $stats = getDashboardStats($user['id']);
                        echo json_encode($stats);
                        break;
                        
                    case 'profile-completion':
                        $completion = getProfileCompletion($user['id']);
                        echo json_encode($completion);
                        break;
                        
                    case 'recent-activity':
                        $activity = getRecentActivity($user['id']);
                        echo json_encode($activity);
                        break;
                        
                    case 'quick-stats':
                        $quickStats = getQuickStats($user['id']);
                        echo json_encode($quickStats);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                // Return full dashboard data
                $dashboardData = [
                    'stats' => getDashboardStats($user['id']),
                    'profile_completion' => getProfileCompletion($user['id']),
                    'recent_activity' => getRecentActivity($user['id']),
                    'quick_stats' => getQuickStats($user['id'])
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
 * Get dashboard statistics for member
 */
function getDashboardStats($memberId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Get complaints count
        $stmt = $db->prepare("SELECT COUNT(*) as total, 
                             SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                             SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                             FROM complaints WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $complaints = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get events count
        $stmt = $db->prepare("SELECT COUNT(*) as registered FROM event_registrations WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $events = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get donations count and total
        $stmt = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as total_amount 
                             FROM donations WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $donations = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get certificates count
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM member_certificates WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $certificates = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'complaints' => [
                'total' => (int)$complaints['total'],
                'pending' => (int)$complaints['pending'],
                'resolved' => (int)$complaints['resolved']
            ],
            'events' => [
                'registered' => (int)$events['registered']
            ],
            'donations' => [
                'count' => (int)$donations['total'],
                'total_amount' => (float)$donations['total_amount']
            ],
            'certificates' => [
                'total' => (int)$certificates['total']
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
        return [
            'complaints' => ['total' => 0, 'pending' => 0, 'resolved' => 0],
            'events' => ['registered' => 0],
            'donations' => ['count' => 0, 'total_amount' => 0],
            'certificates' => ['total' => 0]
        ];
    }
}

/**
 * Get profile completion percentage
 */
function getProfileCompletion($memberId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$member) {
            return ['percentage' => 0, 'missing_fields' => []];
        }
        
        $requiredFields = [
            'first_name', 'last_name', 'email', 'phone', 'address',
            'city', 'state', 'pincode', 'date_of_birth', 'gender'
        ];
        
        $completedFields = 0;
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!empty($member[$field])) {
                $completedFields++;
            } else {
                $missingFields[] = $field;
            }
        }
        
        $percentage = round(($completedFields / count($requiredFields)) * 100);
        
        return [
            'percentage' => $percentage,
            'missing_fields' => $missingFields,
            'completed_fields' => $completedFields,
            'total_fields' => count($requiredFields)
        ];
        
    } catch (Exception $e) {
        error_log("Error getting profile completion: " . $e->getMessage());
        return ['percentage' => 0, 'missing_fields' => []];
    }
}

/**
 * Get recent activity for member
 */
function getRecentActivity($memberId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $activities = [];
        
        // Get recent activity
        $recent_activity = [
            [
                'type' => 'complaint',
                'title' => 'New complaint submitted',
                'description' => 'Complaint #' . rand(1000, 9999) . ' has been submitted',
                'date' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'status' => 'pending'
            ],
            [
                'type' => 'event',
                'title' => 'Event registration',
                'description' => 'Registered for upcoming community meeting',
                'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'status' => 'confirmed'
            ],
            [
                'type' => 'donation',
                'title' => 'Donation made',
                'description' => 'Donation of ₹500 processed successfully',
                'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'status' => 'completed'
            ]
        ];
        
        // Get gallery data
        $gallery_data = getGalleryData();
        
        // Get newsletter subscription status
        $newsletter_status = getNewsletterStatus($user['email'] ?? '');
        
        // Add gallery and newsletter data to activities response
        $response_data = [
            'activities' => $activities,
            'gallery' => $gallery_data,
            'newsletter' => $newsletter_status
        ];
        
        return $response_data;
        
        // Recent complaints
        $stmt = $db->prepare("SELECT 'complaint' as type, id, title as description, 
                             created_at, status FROM complaints 
                             WHERE member_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$memberId]);
        $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($complaints as $complaint) {
            $activities[] = [
                'type' => 'complaint',
                'id' => $complaint['id'],
                'description' => 'Filed complaint: ' . $complaint['description'],
                'date' => $complaint['created_at'],
                'status' => $complaint['status'],
                'icon' => 'fas fa-exclamation-triangle'
            ];
        }
        
        // Recent event registrations
        $stmt = $db->prepare("SELECT 'event' as type, e.id, e.title as description, 
                             er.created_at, 'registered' as status 
                             FROM event_registrations er 
                             JOIN events e ON er.event_id = e.id 
                             WHERE er.member_id = ? ORDER BY er.created_at DESC LIMIT 5");
        $stmt->execute([$memberId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($events as $event) {
            $activities[] = [
                'type' => 'event',
                'id' => $event['id'],
                'description' => 'Registered for: ' . $event['description'],
                'date' => $event['created_at'],
                'status' => $event['status'],
                'icon' => 'fas fa-calendar-check'
            ];
        }
        
        // Recent donations
        $stmt = $db->prepare("SELECT 'donation' as type, id, amount, purpose, 
                             created_at, status FROM donations 
                             WHERE member_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$memberId]);
        $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($donations as $donation) {
            $activities[] = [
                'type' => 'donation',
                'id' => $donation['id'],
                'description' => 'Donated ₹' . number_format($donation['amount']) . ' for ' . $donation['purpose'],
                'date' => $donation['created_at'],
                'status' => $donation['status'],
                'icon' => 'fas fa-heart'
            ];
        }
        
        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return array_slice($activities, 0, 10);
        
    } catch (Exception $e) {
        error_log("Error getting recent activity: " . $e->getMessage());
        return [];
    }
}

/**
 * Get quick statistics for dashboard cards
 */
function getQuickStats($memberId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Membership duration
        $stmt = $db->prepare("SELECT created_at FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $memberSince = '';
        if ($member) {
            $memberSince = date('M Y', strtotime($member['created_at']));
        }
        
        // Upcoming events
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM event_registrations er 
                             JOIN events e ON er.event_id = e.id 
                             WHERE er.member_id = ? AND e.event_date > NOW()");
        $stmt->execute([$memberId]);
        $upcomingEvents = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Active complaints
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM complaints 
                             WHERE member_id = ? AND status IN ('pending', 'in_progress')");
        $stmt->execute([$memberId]);
        $activeComplaints = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Total impact (donations)
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations 
                             WHERE member_id = ? AND status = 'completed'");
        $stmt->execute([$memberId]);
        $totalImpact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'member_since' => $memberSince,
            'upcoming_events' => (int)$upcomingEvents['count'],
            'active_complaints' => (int)$activeComplaints['count'],
            'total_impact' => (float)$totalImpact['total']
        ];
        
    } catch (Exception $e) {
        error_log("Error getting quick stats: " . $e->getMessage());
        return [
            'member_since' => '',
            'upcoming_events' => 0,
            'active_complaints' => 0,
            'total_impact' => 0
        ];
    }
}

/**
 * Get gallery data for member dashboard
 */
function getGalleryData() {
    try {
        // Make API call to gallery integration
        $galleryUrl = '/backend/api/gallery-integration.php?action=public-gallery';
        
        // In a real implementation, you would make an internal API call
        // For now, we'll return mock data
        return [
            'recent_photos' => [
                [
                    'id' => 1,
                    'title' => 'Community Event',
                    'thumbnail_url' => '/uploads/gallery/thumbnails/event1.jpg',
                    'album_name' => 'Events 2024'
                ],
                [
                    'id' => 2,
                    'title' => 'Awareness Campaign',
                    'thumbnail_url' => '/uploads/gallery/thumbnails/campaign1.jpg',
                    'album_name' => 'Campaigns'
                ],
                [
                    'id' => 3,
                    'title' => 'Training Session',
                    'thumbnail_url' => '/uploads/gallery/thumbnails/training1.jpg',
                    'album_name' => 'Training'
                ]
            ],
            'total_photos' => 156,
            'total_albums' => 12
        ];
        
    } catch (Exception $e) {
        error_log("Error getting gallery data: " . $e->getMessage());
        return [
            'recent_photos' => [],
            'total_photos' => 0,
            'total_albums' => 0
        ];
    }
}

/**
 * Get newsletter subscription status
 */
function getNewsletterStatus($email) {
    try {
        if (!$email) {
            return [
                'subscribed' => false,
                'status' => 'unknown'
            ];
        }
        
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT status FROM newsletter_subscribers WHERE email = ?");
        $stmt->execute([$email]);
        $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscriber) {
            return [
                'subscribed' => $subscriber['status'] === 'active',
                'status' => $subscriber['status']
            ];
        } else {
            return [
                'subscribed' => false,
                'status' => 'not_subscribed'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error getting newsletter status: " . $e->getMessage());
        return [
            'subscribed' => false,
            'status' => 'error'
        ];
    }
}
?>