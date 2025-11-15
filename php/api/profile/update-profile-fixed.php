<?php
/**
 * Update User Profile API - FIXED VERSION
 * Added error handling and debugging
 */

// Enable error logging
ini_set('log_errors', 1);
ini_set('display_errors', 0); // Don't display, only log
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Try to include required files with error handling
try {
    require_once __DIR__ . '/../../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Config error: ' . $e->getMessage()]);
    exit;
}

try {
    require_once __DIR__ . '/../../helpers/response.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Response helper error: ' . $e->getMessage()]);
    exit;
}

try {
    require_once __DIR__ . '/../../helpers/validator.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Validator error: ' . $e->getMessage()]);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Get Authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    sendError('Missing or invalid authorization token', 401);
}

$firebaseToken = $matches[1];

// Verify token (simplified)
try {
    $tokenParts = explode('.', $firebaseToken);
    if (count($tokenParts) !== 3) {
        sendError('Invalid token format', 401);
    }
    
    $payload = json_decode(base64_decode($tokenParts[1]), true);
    $firebase_uid = $payload['user_id'] ?? null;
    
    if (!$firebase_uid) {
        sendError('Invalid token payload', 401);
    }
    
} catch (Exception $e) {
    sendError('Token verification failed: ' . $e->getMessage(), 401);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendError('Invalid JSON input', 400);
}

// Connect to database
try {
    $db = getDBConnection();
} catch (Exception $e) {
    sendError('Database connection failed: ' . $e->getMessage(), 500);
}

try {
    // Get current user
    $stmt = $db->prepare("SELECT id, role FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebase_uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('User not found', 404);
    }
    
    $userId = $user['id'];
    $role = $input['role'] ?? $user['role'];
    
    // Begin transaction
    $db->beginTransaction();
    
    // Update role if not set
    if (!$user['role'] && $role) {
        if (!in_array($role, ['student', 'lecturer'])) {
            $db->rollBack();
            sendError('Invalid role. Must be "student" or "lecturer"', 400);
        }
        
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $userId]);
    }
    
    // Update phone if provided
    if (isset($input['phone'])) {
        $stmt = $db->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$input['phone'], $userId]);
    }
    
    // Update profile based on role
    if ($role === 'student') {
        // Validate student data
        $errors = [];
        
        if (isset($input['student_id']) && function_exists('validateStudentId')) {
            if (!validateStudentId($input['student_id'])) {
                $errors[] = 'Invalid student ID format (8-10 characters)';
            }
        }
        
        if (isset($input['phone']) && function_exists('validatePhone')) {
            if (!validatePhone($input['phone'])) {
                $errors[] = 'Invalid phone number format (10 digits)';
            }
        }
        
        if (!empty($errors)) {
            $db->rollBack();
            sendError('Validation failed: ' . implode(', ', $errors), 400);
        }
        
        // Check if student profile exists
        $stmt = $db->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $fields = [];
            $values = [];
            
            $allowedFields = ['student_id', 'university', 'major', 'academic_year', 'gpa', 
                              'phone', 'bio', 'research_interests', 'skills', 'avatar_url'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
            
            if (!empty($fields)) {
                $values[] = $userId;
                $sql = "UPDATE student_profiles SET " . implode(', ', $fields) . " WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
            }
        } else {
            // Insert
            $sql = "INSERT INTO student_profiles (user_id, student_id, university, major, academic_year, phone, bio, research_interests) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $input['student_id'] ?? '',
                $input['university'] ?? '',
                $input['major'] ?? '',
                $input['academic_year'] ?? '',
                $input['phone'] ?? '',
                $input['bio'] ?? '',
                $input['research_interests'] ?? ''
            ]);
        }
        
    } elseif ($role === 'lecturer') {
        // Similar for lecturer
        $errors = [];
        
        if (isset($input['phone']) && function_exists('validatePhone')) {
            if (!validatePhone($input['phone'])) {
                $errors[] = 'Invalid phone number format';
            }
        }
        
        if (!empty($errors)) {
            $db->rollBack();
            sendError('Validation failed: ' . implode(', ', $errors), 400);
        }
        
        // Check if lecturer profile exists
        $stmt = $db->prepare("SELECT id FROM lecturer_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $fields = [];
            $values = [];
            
            $allowedFields = ['lecturer_id', 'university', 'department', 'degree', 'title',
                              'research_interests', 'publications_count', 'phone', 'office_location',
                              'bio', 'website_url', 'google_scholar_url', 'orcid', 'avatar_url',
                              'available_for_mentoring', 'max_students'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
            
            if (!empty($fields)) {
                $values[] = $userId;
                $sql = "UPDATE lecturer_profiles SET " . implode(', ', $fields) . " WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($values);
            }
        } else {
            // Insert
            $sql = "INSERT INTO lecturer_profiles (user_id, lecturer_id, university, department, degree, research_interests, phone) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $input['lecturer_id'] ?? '',
                $input['university'] ?? '',
                $input['department'] ?? '',
                $input['degree'] ?? 'bachelor',
                $input['research_interests'] ?? '',
                $input['phone'] ?? ''
            ]);
        }
    } else {
        $db->rollBack();
        sendError('Role not set. Please set role first.', 400);
    }
    
    // Check completeness
    $isComplete = false;
    if ($role === 'student') {
        $stmt = $db->prepare("
            SELECT COUNT(*) as complete FROM student_profiles 
            WHERE user_id = ? 
              AND student_id IS NOT NULL AND student_id != ''
              AND university IS NOT NULL AND university != ''
              AND major IS NOT NULL AND major != ''
              AND phone IS NOT NULL AND phone != ''
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $isComplete = $result['complete'] > 0;
    } elseif ($role === 'lecturer') {
        $stmt = $db->prepare("
            SELECT COUNT(*) as complete FROM lecturer_profiles 
            WHERE user_id = ? 
              AND lecturer_id IS NOT NULL AND lecturer_id != ''
              AND university IS NOT NULL AND university != ''
              AND department IS NOT NULL AND department != ''
              AND degree IS NOT NULL
              AND research_interests IS NOT NULL AND research_interests != ''
              AND phone IS NOT NULL AND phone != ''
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $isComplete = $result['complete'] > 0;
    }
    
    // Update profile_completed flag
    $stmt = $db->prepare("UPDATE users SET profile_completed = ? WHERE id = ?");
    $stmt->execute([$isComplete, $userId]);
    
    // Commit transaction
    $db->commit();
    
    sendSuccess([
        'profile_completed' => $isComplete,
        'message' => $isComplete ? 'Profile updated and completed!' : 'Profile updated. Please complete remaining fields.'
    ], 'Profile updated successfully');
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Database error in update-profile: ' . $e->getMessage());
    sendError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in update-profile: ' . $e->getMessage());
    sendError('Server error: ' . $e->getMessage(), 500);
}

