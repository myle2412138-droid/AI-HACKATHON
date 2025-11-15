<?php
/**
 * Apply to Research Project
 * Student gửi đơn apply vào đề tài
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

if (!$input || !isset($input['project_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing project_id']);
    exit;
}

// Get user from token (simplified - trong thực tế verify token)
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

// Extract user_id from token or session
// For now, assume user_id in request
$studentId = $input['student_id'] ?? null;

if (!$studentId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Check if already applied
    $stmt = $db->prepare("
        SELECT id FROM applications 
        WHERE project_id = ? AND student_id = ?
    ");
    $stmt->execute([$input['project_id'], $studentId]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false, 
            'message' => 'Bạn đã apply vào đề tài này rồi!'
        ]);
        exit;
    }
    
    // Check if project still open
    $stmt = $db->prepare("
        SELECT status, current_students, max_students 
        FROM research_projects 
        WHERE id = ?
    ");
    $stmt->execute([$input['project_id']]);
    $project = $stmt->fetch();
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }
    
    if ($project['status'] !== 'open') {
        echo json_encode(['success' => false, 'message' => 'Đề tài đã đóng']);
        exit;
    }
    
    if ($project['current_students'] >= $project['max_students']) {
        echo json_encode(['success' => false, 'message' => 'Đề tài đã đủ thành viên']);
        exit;
    }
    
    // Insert application
    $stmt = $db->prepare("
        INSERT INTO applications 
        (project_id, student_id, cover_letter, motivation, relevant_experience)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $input['project_id'],
        $studentId,
        $input['cover_letter'] ?? '',
        $input['motivation'] ?? '',
        $input['relevant_experience'] ?? ''
    ]);
    
    // Update application count
    $db->exec("UPDATE research_projects SET application_count = application_count + 1 WHERE id = " . $input['project_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã gửi đơn apply thành công!',
        'data' => [
            'application_id' => $db->lastInsertId()
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Apply error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

