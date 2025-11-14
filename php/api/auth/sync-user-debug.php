<?php
/**
 * Sync User API - WITH ERROR DISPLAY
 * Temporary version for debugging
 */

// ENABLE ALL ERRORS FOR DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Force JSON header first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug info
$debug = [
    'step' => 1,
    'message' => 'Starting script',
    '__DIR__' => __DIR__,
    '__FILE__' => __FILE__,
];

try {
    $debug['step'] = 2;
    $debug['message'] = 'Loading config files';
    
    $configPath = dirname(__DIR__, 2) . '/config/database.php';
    $responsePath = dirname(__DIR__, 2) . '/helpers/response.php';
    $validatorPath = dirname(__DIR__, 2) . '/helpers/validator.php';
    
    $debug['paths'] = [
        'config' => $configPath,
        'response' => $responsePath,
        'validator' => $validatorPath
    ];
    
    $debug['paths_exist'] = [
        'config' => file_exists($configPath),
        'response' => file_exists($responsePath),
        'validator' => file_exists($validatorPath)
    ];
    
    if (!file_exists($configPath)) {
        throw new Exception('Config file not found: ' . $configPath);
    }
    
    if (!file_exists($responsePath)) {
        throw new Exception('Response helper not found: ' . $responsePath);
    }
    
    if (!file_exists($validatorPath)) {
        throw new Exception('Validator helper not found: ' . $validatorPath);
    }
    
    $debug['step'] = 3;
    $debug['message'] = 'Including files';
    
    require_once $configPath;
    require_once $responsePath;
    require_once $validatorPath;
    
    $debug['step'] = 4;
    $debug['message'] = 'Files loaded successfully';
    
    // Check method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed. Use POST.',
            'debug' => $debug
        ]);
        exit;
    }
    
    $debug['step'] = 5;
    $debug['message'] = 'Reading request body';
    
    // Get request body
    $input = file_get_contents('php://input');
    $debug['raw_input'] = $input;
    
    $data = json_decode($input, true);
    $debug['parsed_data'] = $data;
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON',
            'debug' => $debug
        ]);
        exit;
    }
    
    $debug['step'] = 6;
    $debug['message'] = 'Connecting to database';
    
    // Test DB connection
    $pdo = getDBConnection();
    $debug['db_connected'] = true;
    
    $debug['step'] = 7;
    $debug['message'] = 'Processing user data';
    
    // Validate required fields
    if (!isset($data['user']) || !isset($data['user']['uid'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing user.uid',
            'debug' => $debug
        ]);
        exit;
    }
    
    $user = $data['user'];
    $firebaseUid = $user['uid'];
    $email = $user['email'] ?? null;
    $displayName = $user['displayName'] ?? null;
    $photoURL = $user['photoURL'] ?? null;
    $emailVerified = isset($user['emailVerified']) ? ($user['emailVerified'] ? 1 : 0) : 0;
    $provider = $user['provider'] ?? 'password';
    
    $debug['step'] = 8;
    $debug['message'] = 'Checking if user exists';
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $existingUser = $stmt->fetch();
    
    $debug['user_exists'] = $existingUser !== false;
    
    if ($existingUser) {
        // Update existing user
        $debug['step'] = 9;
        $debug['message'] = 'Updating existing user';
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET email = ?, display_name = ?, photo_url = ?, 
                email_verified = ?, auth_provider = ?, last_login = NOW()
            WHERE firebase_uid = ?
        ");
        $stmt->execute([$email, $displayName, $photoURL, $emailVerified, $provider, $firebaseUid]);
        
        $action = 'login';
    } else {
        // Insert new user
        $debug['step'] = 9;
        $debug['message'] = 'Creating new user';
        
        $stmt = $pdo->prepare("
            INSERT INTO users (firebase_uid, email, display_name, photo_url, email_verified, auth_provider)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$firebaseUid, $email, $displayName, $photoURL, $emailVerified, $provider]);
        
        $action = 'register';
    }
    
    $debug['step'] = 10;
    $debug['message'] = 'Success!';
    
    echo json_encode([
        'success' => true,
        'message' => 'User synced successfully',
        'data' => [
            'action' => $action,
            'firebase_uid' => $firebaseUid,
            'email' => $email
        ],
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'debug' => $debug
    ]);
}
