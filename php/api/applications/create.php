<?php
/**
 * Create Application API
 * Submit application to project
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
    
    $projectId = $input['project_id'] ?? null;
    $studentId = $input['student_id'] ?? null;
    $coverLetter = $input['cover_letter'] ?? null;
    $skills = $input['skills'] ?? [];
    
    if (!$projectId || !$studentId) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // Check if already applied
    $stmt = $db->prepare("
        SELECT id FROM applications 
        WHERE project_id = ? AND student_id = ?
    ");
    $stmt->execute([$projectId, $studentId]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'You have already applied to this project'
        ]);
        exit;
    }
    
    // Check if project is full
    $stmt = $db->prepare("
        SELECT 
            p.max_students,
            COUNT(DISTINCT tm.student_id) as current_students
        FROM research_projects p
        LEFT JOIN team_members tm ON p.id = tm.project_id AND tm.status = 'active'
        WHERE p.id = ?
        GROUP BY p.id
    ");
    
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found'
        ]);
        exit;
    }
    
    if ($project['current_students'] >= $project['max_students']) {
        echo json_encode([
            'success' => false,
            'message' => 'Project is full. No more spots available.'
        ]);
        exit;
    }
    
    // Create application
    $stmt = $db->prepare("
        INSERT INTO applications (
            project_id,
            student_id,
            cover_letter,
            skills,
            status,
            applied_date
        ) VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $projectId,
        $studentId,
        $coverLetter,
        json_encode($skills)
    ]);
    
    $applicationId = $db->lastInsertId();
    
    // Get student info for notification
    $stmt = $db->prepare("
        SELECT full_name, email FROM student_profiles WHERE user_id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Send notification to lecturer
    
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully',
        'application_id' => $applicationId,
        'student_name' => $student['full_name'] ?? 'Unknown'
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
