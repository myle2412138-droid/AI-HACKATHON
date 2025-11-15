<?php
/**
 * Log Paper Interaction
 * Track: view, save, cite, download
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Suppress errors to ensure JSON-only output
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

@require_once __DIR__ . '/../../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['user_id']) || !isset($input['paper_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $db = @getDBConnection();
    if (!$db) {
        throw new Exception('Database unavailable');
    }
    
    $stmt = $db->prepare("
        INSERT INTO paper_interactions 
        (user_id, paper_id, paper_title, paper_authors, paper_year, paper_source, interaction_type, search_query)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $input['user_id'],
        $input['paper_id'],
        $input['paper_title'] ?? null,
        $input['paper_authors'] ?? null,
        $input['paper_year'] ?? null,
        $input['paper_source'] ?? 'unknown',
        $input['interaction_type'] ?? 'view',
        $input['search_query'] ?? null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Interaction logged',
        'data' => ['interaction_id' => $db->lastInsertId()]
    ]);
    
} catch (Exception $e) {
    error_log('Log interaction error: ' . $e->getMessage());
    // Return success anyway to not block frontend
    echo json_encode([
        'success' => true,
        'message' => 'Interaction received',
        'data' => ['interaction_id' => 0]
    ]);
}

