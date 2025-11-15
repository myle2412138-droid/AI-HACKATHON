<?php
/**
 * Log Search Query
 * Track student searches for monitoring
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id']) || !isset($input['query'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO search_logs (user_id, query, search_type, results_count, session_id, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $input['user_id'],
        $input['query'],
        $input['search_type'] ?? 'papers',
        $input['results_count'] ?? 0,
        $input['session_id'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    $searchId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Search logged',
        'data' => ['search_id' => $searchId]
    ]);
    
} catch (Exception $e) {
    error_log('Log search error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

