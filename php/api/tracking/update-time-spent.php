<?php
/**
 * Update Time Spent on Paper
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

try {
    $db = getDBConnection();
    
    // Update latest view interaction
    $stmt = $db->prepare("
        UPDATE paper_interactions 
        SET time_spent = time_spent + ?
        WHERE user_id = ? 
          AND paper_id = ? 
          AND interaction_type = 'view'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([
        $input['time_spent'] ?? 0,
        $input['user_id'],
        $input['paper_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Time updated']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error']);
}

