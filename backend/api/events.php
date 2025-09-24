<?php
/**
 * Events API Endpoint
 * Provides events data for the BHRC website
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get query parameters
    $action = $_GET['action'] ?? 'list';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // Handle different actions
    switch ($action) {
        case 'recent':
            $events = getRecentEvents($limit);
            break;
        case 'list':
        default:
            $events = getAllEvents($limit);
            break;
    }

    $response = [
        'success' => true,
        'data' => $events,
        'count' => count($events),
        'timestamp' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}

/**
 * Get recent events
 */
function getRecentEvents($limit = 3) {
    // Mock recent events data - replace with actual database queries
    $events = [
        [
            'id' => 1,
            'title' => 'Human Rights Awareness Workshop',
            'description' => 'Join us for an interactive workshop on understanding and protecting human rights in our community.',
            'date' => date('Y-m-d', strtotime('+5 days')),
            'time' => '10:00 AM',
            'location' => 'BHRC Main Office, Delhi',
            'image' => './assets/images/event-placeholder.svg',
            'category' => 'Workshop',
            'status' => 'upcoming'
        ],
        [
            'id' => 2,
            'title' => 'Legal Aid Camp',
            'description' => 'Free legal consultation and assistance for underprivileged communities.',
            'date' => date('Y-m-d', strtotime('+12 days')),
            'time' => '9:00 AM',
            'location' => 'Community Center, Mumbai',
            'image' => './assets/images/event-placeholder.svg',
            'category' => 'Legal Aid',
            'status' => 'upcoming'
        ],
        [
            'id' => 3,
            'title' => 'Women Rights Seminar',
            'description' => 'Empowering women through awareness of their legal rights and available support systems.',
            'date' => date('Y-m-d', strtotime('+18 days')),
            'time' => '2:00 PM',
            'location' => 'Women\'s College, Bangalore',
            'image' => './assets/images/event-placeholder.svg',
            'category' => 'Seminar',
            'status' => 'upcoming'
        ],
        [
            'id' => 4,
            'title' => 'Child Protection Drive',
            'description' => 'Community outreach program focusing on child safety and protection measures.',
            'date' => date('Y-m-d', strtotime('+25 days')),
            'time' => '11:00 AM',
            'location' => 'School Auditorium, Chennai',
            'image' => './assets/images/event-placeholder.svg',
            'category' => 'Community Outreach',
            'status' => 'upcoming'
        ]
    ];

    return array_slice($events, 0, $limit);
}

/**
 * Get all events
 */
function getAllEvents($limit = 10) {
    return getRecentEvents($limit);
}
?>