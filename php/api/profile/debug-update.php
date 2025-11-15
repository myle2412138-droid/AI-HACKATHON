<?php
/**
 * Debug Update Profile API
 * File này để debug lỗi 500
 */

// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== DEBUG UPDATE PROFILE API ===\n\n";

// Test 1: Check if files exist
echo "1. Checking required files...\n";
$files = [
    'database.php' => __DIR__ . '/../../config/database.php',
    'response.php' => __DIR__ . '/../../helpers/response.php',
    'validator.php' => __DIR__ . '/../../helpers/validator.php'
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "   ✓ $name exists\n";
    } else {
        echo "   ✗ $name NOT FOUND at $path\n";
    }
}

// Test 2: Try to include files
echo "\n2. Including files...\n";
try {
    require_once __DIR__ . '/../../config/database.php';
    echo "   ✓ database.php included\n";
} catch (Exception $e) {
    echo "   ✗ database.php error: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once __DIR__ . '/../../helpers/response.php';
    echo "   ✓ response.php included\n";
} catch (Exception $e) {
    echo "   ✗ response.php error: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once __DIR__ . '/../../helpers/validator.php';
    echo "   ✓ validator.php included\n";
} catch (Exception $e) {
    echo "   ✗ validator.php error: " . $e->getMessage() . "\n";
    exit;
}

// Test 3: Check if functions exist
echo "\n3. Checking functions...\n";
$functions = ['getDBConnection', 'sendError', 'sendSuccess', 'validatePhone', 'validateStudentId'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "   ✓ $func() exists\n";
    } else {
        echo "   ✗ $func() NOT FOUND\n";
    }
}

// Test 4: Test database connection
echo "\n4. Testing database connection...\n";
try {
    $db = getDBConnection();
    echo "   ✓ Database connected\n";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Table 'users' exists\n";
    } else {
        echo "   ✗ Table 'users' NOT FOUND\n";
    }
    
    // Check if student_profiles table exists
    $stmt = $db->query("SHOW TABLES LIKE 'student_profiles'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Table 'student_profiles' exists\n";
    } else {
        echo "   ✗ Table 'student_profiles' NOT FOUND\n";
    }
    
    // Check if lecturer_profiles table exists
    $stmt = $db->query("SHOW TABLES LIKE 'lecturer_profiles'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Table 'lecturer_profiles' exists\n";
    } else {
        echo "   ✗ Table 'lecturer_profiles' NOT FOUND\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

// Test 5: Test validation functions
echo "\n5. Testing validation functions...\n";
if (function_exists('validatePhone')) {
    $testPhone = '0123456789';
    $result = validatePhone($testPhone);
    echo "   validatePhone('$testPhone') = " . ($result ? 'true' : 'false') . "\n";
}

if (function_exists('validateStudentId')) {
    $testId = '20520001';
    $result = validateStudentId($testId);
    echo "   validateStudentId('$testId') = " . ($result ? 'true' : 'false') . "\n";
}

// Test 6: Simulate update request
echo "\n6. Testing update flow...\n";
echo "   Simulating POST request with sample data...\n";

$sampleData = [
    'role' => 'student',
    'student_id' => '20520001',
    'university' => 'Test University',
    'major' => 'Test Major',
    'phone' => '0123456789'
];

try {
    // Giả lập có user
    $testFirebaseUid = 'test_uid_123';
    
    // Check if test user exists
    $stmt = $db->prepare("SELECT id, role FROM users WHERE firebase_uid = ?");
    $stmt->execute([$testFirebaseUid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "   ✓ Test user found (ID: {$user['id']})\n";
    } else {
        echo "   ⚠ Test user not found (this is OK for testing)\n";
        echo "   Creating test user...\n";
        
        try {
            $stmt = $db->prepare("INSERT INTO users (firebase_uid, email, display_name) VALUES (?, ?, ?)");
            $stmt->execute([$testFirebaseUid, 'test@example.com', 'Test User']);
            echo "   ✓ Test user created\n";
        } catch (Exception $e) {
            echo "   ⚠ Could not create test user: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "\nIf all tests pass above, the issue might be:\n";
echo "- Firebase token validation\n";
echo "- Request data format\n";
echo "- PHP version incompatibility\n";
echo "\nNext step: Check error_log or Apache/PHP error logs\n";

