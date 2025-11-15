<?php
/**
 * Update Profile - ABSOLUTE MINIMAL VERSION
 * Chỉ INSERT/UPDATE thuần túy, không có gì thêm
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

// Function đơn giản để response
function respond($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Only POST allowed');
}

// Get input
$input = @file_get_contents('php://input');
if (!$input) {
    respond(false, 'No input data');
}

$data = @json_decode($input, true);
if (!$data) {
    respond(false, 'Invalid JSON');
}

// Get auth header
$headers = @getallheaders();
$auth = $headers['Authorization'] ?? '';

if (empty($auth)) {
    respond(false, 'No authorization');
}

// Extract token
if (!preg_match('/Bearer\s+(.+)$/', $auth, $m)) {
    respond(false, 'Invalid auth format');
}

$token = $m[1];

// Decode token (simplified - no verify)
$parts = explode('.', $token);
if (count($parts) !== 3) {
    respond(false, 'Invalid token');
}

$payload = @json_decode(@base64_decode($parts[1]), true);
$firebaseUid = $payload['user_id'] ?? null;

if (!$firebaseUid) {
    respond(false, 'Invalid token payload');
}

// Connect DB
try {
    $db = new PDO(
        "mysql:host=localhost;dbname=victoria_ai;charset=utf8mb4", 
        "root", 
        "123456",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    respond(false, 'DB connection failed');
}

// Get user
try {
    $stmt = $db->prepare("SELECT id, role FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        respond(false, 'User not found');
    }
    
    $userId = $user['id'];
    $role = $data['role'] ?? $user['role'];
    
    // Update role nếu chưa có
    if (!$user['role'] && $role) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $userId]);
    }
    
    // Update phone
    if (isset($data['phone'])) {
        $stmt = $db->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$data['phone'], $userId]);
    }
    
    // UPDATE STUDENT
    if ($role === 'student') {
        // Check if profile exists
        $stmt = $db->prepare("SELECT id, student_id FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // UPDATE - CHỈ update student_id nếu KHÁC với hiện tại
            $updateStudentId = false;
            $newStudentId = $data['student_id'] ?? '';
            
            // Nếu student_id mới khác với cũ, check xem có bị duplicate không
            if ($newStudentId && $newStudentId !== $existing['student_id']) {
                $stmt = $db->prepare("SELECT id FROM student_profiles WHERE student_id = ? AND user_id != ?");
                $stmt->execute([$newStudentId, $userId]);
                
                if ($stmt->fetch()) {
                    respond(false, "MSSV '$newStudentId' đã được sử dụng bởi sinh viên khác!");
                }
                $updateStudentId = true;
            }
            
            // Build UPDATE query
            if ($updateStudentId) {
                $sql = "UPDATE student_profiles SET 
                        student_id = ?, university = ?, major = ?, 
                        phone = ?, bio = ?, research_interests = ?
                        WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $newStudentId,
                    $data['university'] ?? '',
                    $data['major'] ?? '',
                    $data['phone'] ?? '',
                    $data['bio'] ?? '',
                    $data['research_interests'] ?? '',
                    $userId
                ]);
            } else {
                // Không update student_id, giữ nguyên
                $sql = "UPDATE student_profiles SET 
                        university = ?, major = ?, 
                        phone = ?, bio = ?, research_interests = ?
                        WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $data['university'] ?? '',
                    $data['major'] ?? '',
                    $data['phone'] ?? '',
                    $data['bio'] ?? '',
                    $data['research_interests'] ?? '',
                    $userId
                ]);
            }
        } else {
            // INSERT - Check student_id không trùng
            $newStudentId = $data['student_id'] ?? '';
            
            if ($newStudentId) {
                $stmt = $db->prepare("SELECT id FROM student_profiles WHERE student_id = ?");
                $stmt->execute([$newStudentId]);
                
                if ($stmt->fetch()) {
                    respond(false, "MSSV '$newStudentId' đã được sử dụng bởi sinh viên khác!");
                }
            }
            
            $sql = "INSERT INTO student_profiles 
                    (user_id, student_id, university, major, phone, bio, research_interests) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $newStudentId,
                $data['university'] ?? '',
                $data['major'] ?? '',
                $data['phone'] ?? '',
                $data['bio'] ?? '',
                $data['research_interests'] ?? ''
            ]);
        }
    }
    // UPDATE LECTURER
    elseif ($role === 'lecturer') {
        // Check if profile exists
        $stmt = $db->prepare("SELECT id, lecturer_id FROM lecturer_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // UPDATE - Check lecturer_id duplicate
            $updateLecturerId = false;
            $newLecturerId = $data['lecturer_id'] ?? '';
            
            if ($newLecturerId && $newLecturerId !== $existing['lecturer_id']) {
                $stmt = $db->prepare("SELECT id FROM lecturer_profiles WHERE lecturer_id = ? AND user_id != ?");
                $stmt->execute([$newLecturerId, $userId]);
                
                if ($stmt->fetch()) {
                    respond(false, "Mã giảng viên '$newLecturerId' đã được sử dụng!");
                }
                $updateLecturerId = true;
            }
            
            // Build UPDATE
            if ($updateLecturerId) {
                $sql = "UPDATE lecturer_profiles SET 
                        lecturer_id = ?, university = ?, department = ?, 
                        degree = ?, research_interests = ?, phone = ?
                        WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $newLecturerId,
                    $data['university'] ?? '',
                    $data['department'] ?? '',
                    $data['degree'] ?? 'bachelor',
                    $data['research_interests'] ?? '',
                    $data['phone'] ?? '',
                    $userId
                ]);
            } else {
                // Không update lecturer_id
                $sql = "UPDATE lecturer_profiles SET 
                        university = ?, department = ?, 
                        degree = ?, research_interests = ?, phone = ?
                        WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $data['university'] ?? '',
                    $data['department'] ?? '',
                    $data['degree'] ?? 'bachelor',
                    $data['research_interests'] ?? '',
                    $data['phone'] ?? '',
                    $userId
                ]);
            }
        } else {
            // INSERT - Check lecturer_id không trùng
            $newLecturerId = $data['lecturer_id'] ?? '';
            
            if ($newLecturerId) {
                $stmt = $db->prepare("SELECT id FROM lecturer_profiles WHERE lecturer_id = ?");
                $stmt->execute([$newLecturerId]);
                
                if ($stmt->fetch()) {
                    respond(false, "Mã giảng viên '$newLecturerId' đã được sử dụng!");
                }
            }
            
            $sql = "INSERT INTO lecturer_profiles 
                    (user_id, lecturer_id, university, department, degree, research_interests, phone) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $newLecturerId,
                $data['university'] ?? '',
                $data['department'] ?? '',
                $data['degree'] ?? 'bachelor',
                $data['research_interests'] ?? '',
                $data['phone'] ?? ''
            ]);
        }
    }
    
    // Update completed flag
    $stmt = $db->prepare("UPDATE users SET profile_completed = 1 WHERE id = ?");
    $stmt->execute([$userId]);
    
    respond(true, 'Cập nhật thành công', [
        'profile_completed' => true,
        'message' => 'Hồ sơ đã hoàn thiện!'
    ]);
    
} catch (Exception $e) {
    respond(false, 'Error: ' . $e->getMessage());
}

