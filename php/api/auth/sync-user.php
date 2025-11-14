<?php
/**
 * Victoria AI - Sync User API
 * Syncs user data from Firebase to MySQL
 * 
 * Endpoint: POST /php/api/auth/sync-user.php
 * Body: { idToken, user: { uid, email, displayName, photoURL, emailVerified, provider } }
 */

// Include files
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/helpers/response.php';

// Set headers
setJSONHeaders();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Get request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    sendError('Invalid JSON', 400);
}

// Validate required fields
if (!isset($data['user']) || !isset($data['user']['uid'])) {
    sendError('Missing user.uid', 400);
}

try {
    $user = $data['user'];
    $firebaseUid = $data['user']['uid'];
    $email = $user['email'] ?? null;
    $displayName = $user['displayName'] ?? null;
    $photoURL = $user['photoURL'] ?? null;
    $emailVerified = isset($user['emailVerified']) && $user['emailVerified'] ? 1 : 0;
    $provider = $user['provider'] ?? 'password';
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, firebase_uid, email FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Update existing user
        $stmt = $pdo->prepare("
            UPDATE users 
            SET email = ?, display_name = ?, photo_url = ?, 
                email_verified = ?, auth_provider = ?, last_login = NOW()
            WHERE firebase_uid = ?
        ");
        $stmt->execute([$email, $displayName, $photoURL, $emailVerified, $provider, $firebaseUid]);
        
        $action = 'login';
        $userId = $existingUser['id'];
    } else {
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (firebase_uid, email, display_name, photo_url, email_verified, auth_provider)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$firebaseUid, $email, $displayName, $photoURL, $emailVerified, $provider]);
        
        $userId = $pdo->lastInsertId();
        $action = 'register';
        
        // Create default preferences for new user
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences (user_id, theme, language, notifications_enabled)
            VALUES (?, 'dark', 'vi', 1)
        ");
        $stmt->execute([$userId]);
    }
    
    // Get updated user data
    $stmt = $pdo->prepare("SELECT id, firebase_uid, email, display_name, photo_url, auth_provider, created_at FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $userData = $stmt->fetch();
    
    // Log activity
    try {
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$userId, $action, $clientIP, $userAgent]);
    } catch (Exception $e) {
        // Ignore activity log errors
    }
    
    // Success response
    sendSuccess([
        'action' => $action,
        'user' => $userData
    ], 'User synced successfully');
    
} catch (PDOException $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    sendError('Server error: ' . $e->getMessage(), 500);
}
