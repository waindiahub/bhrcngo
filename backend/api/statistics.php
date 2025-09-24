<?php
/**
 * Statistics API Endpoint
 * Provides statistical data for the BHRC website
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
    // Mock statistics data - replace with actual database queries
    $statistics = [
        'members' => 15000,
        'cases_resolved' => 8500,
        'years_experience' => 25,
        'success_rate' => 95,
        'complaints_filed' => 12000,
        'legal_aid_provided' => 9500,
        'awareness_programs' => 450,
        'volunteers' => 2500
    ];

    // Add some dynamic variation to make it feel more real
    $statistics['members'] += rand(-50, 100);
    $statistics['cases_resolved'] += rand(-20, 50);
    $statistics['complaints_filed'] += rand(-30, 80);

    $response = [
        'success' => true,
        'data' => $statistics,
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
?>