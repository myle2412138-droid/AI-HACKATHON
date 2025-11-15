<?php
/**
 * Project Detail API
 * Get full details of a research project
 */

// Suppress errors
error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../../config/database.php';
    $db = getDBConnection();
    
    $projectId = $_GET['id'] ?? null;
    
    if (!$projectId) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing project ID'
        ]);
        exit;
    }
    
    // Get project with lecturer info
    $stmt = $db->prepare("
        SELECT 
            p.*,
            l.full_name as lecturer_name,
            l.title as lecturer_title,
            l.university,
            l.department,
            l.email as lecturer_email,
            l.avatar_url as lecturer_avatar,
            COUNT(DISTINCT tm.student_id) as current_students
        FROM research_projects p
        LEFT JOIN lecturer_profiles l ON p.lecturer_id = l.user_id
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
    
    // Get required skills
    if (!empty($project['required_skills'])) {
        $project['skills_array'] = json_decode($project['required_skills'], true) ?? [];
    } else {
        $project['skills_array'] = [];
    }
    
    // Get current team members (if user is authorized)
    $stmt = $db->prepare("
        SELECT 
            sp.full_name,
            sp.avatar_url,
            tm.role,
            tm.joined_date
        FROM team_members tm
        JOIN student_profiles sp ON tm.student_id = sp.user_id
        WHERE tm.project_id = ? AND tm.status = 'active'
        ORDER BY tm.joined_date ASC
    ");
    
    $stmt->execute([$projectId]);
    $project['team_members'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate spots left
    $project['spots_left'] = max(0, ($project['max_students'] ?? 3) - ($project['current_students'] ?? 0));
    $project['is_full'] = $project['spots_left'] <= 0;
    
    // Format dates
    $project['start_date_formatted'] = date('d/m/Y', strtotime($project['start_date'] ?? 'now'));
    $project['end_date_formatted'] = date('d/m/Y', strtotime($project['end_date'] ?? '+6 months'));
    
    echo json_encode([
        'success' => true,
        'project' => $project
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
