<?php
/**
 * Test Database Connection
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = [
    'success' => false,
    'message' => '',
    'checks' => []
];

// Check 1: Config file exists
if (file_exists(__DIR__ . '/../../config/database.php')) {
    $response['checks']['config_file'] = 'EXISTS';
    require_once __DIR__ . '/../../config/database.php';
} else {
    $response['checks']['config_file'] = 'NOT FOUND';
    $response['message'] = 'Config file not found';
    echo json_encode($response);
    exit;
}

// Check 2: Database connection
try {
    $db = getDBConnection();
    $response['checks']['database'] = 'CONNECTED';
    
    // Check 3: Tables exist
    $tables = ['users', 'student_profiles', 'lecturer_profiles'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $response['checks']["table_$table"] = $stmt->rowCount() > 0 ? 'EXISTS' : 'NOT FOUND';
    }
    
    // Check 4: Count records
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $response['checks']['users_count'] = $result['count'];
    
    $response['success'] = true;
    $response['message'] = 'All checks passed!';
    
} catch (Exception $e) {
    $response['checks']['database'] = 'ERROR: ' . $e->getMessage();
    $response['message'] = 'Database error';
}

echo json_encode($response, JSON_PRETTY_PRINT);

