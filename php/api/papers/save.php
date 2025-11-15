<?php
/**
 * Save Paper API
 * Save paper to user's library
 */

// Suppress errors
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDBConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = $input['user_id'] ?? null;
    $paperId = $input['paper_id'] ?? null;
    $paperData = $input['paper_data'] ?? null;
    
    if (!$userId || !$paperId) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // Check if already saved
    $stmt = $db->prepare("
        SELECT id FROM saved_papers 
        WHERE user_id = ? AND paper_id = ?
    ");
    $stmt->execute([$userId, $paperId]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => true,
            'message' => 'Paper already saved',
            'already_saved' => true
        ]);
        exit;
    }
    
    // Save paper
    $stmt = $db->prepare("
        INSERT INTO saved_papers (
            user_id, 
            paper_id, 
            paper_title, 
            paper_authors, 
            paper_year, 
            paper_abstract,
            paper_url,
            paper_source,
            saved_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $userId,
        $paperId,
        $paperData['title'] ?? null,
        $paperData['authors'] ?? null,
        $paperData['year'] ?? null,
        $paperData['abstract'] ?? null,
        $paperData['url'] ?? null,
        $paperData['source'] ?? 'semantic_scholar'
    ]);
    
    $savedId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Paper saved successfully',
        'saved_id' => $savedId
    ]);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}
