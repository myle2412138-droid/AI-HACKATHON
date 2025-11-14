<?php
/**
 * Path Test - Debug include paths
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$info = [
    '__FILE__' => __FILE__,
    '__DIR__' => __DIR__,
    'getcwd()' => getcwd(),
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
    'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
    'paths_to_check' => [],
    'file_exists' => []
];

// Check different path combinations
$pathsToCheck = [
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../config/database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/php/config/database.php',
    dirname(__DIR__, 2) . '/config/database.php',
    realpath(__DIR__ . '/../../config/database.php'),
];

foreach ($pathsToCheck as $path) {
    $info['paths_to_check'][] = $path;
    $info['file_exists'][$path] = file_exists($path) ? 'YES' : 'NO';
}

// Try to include database.php
try {
    $configPath = realpath(__DIR__ . '/../../config/database.php');
    if ($configPath && file_exists($configPath)) {
        require_once $configPath;
        $info['database_included'] = 'SUCCESS';
        $info['used_path'] = $configPath;
        
        // Test connection
        try {
            $pdo = getDBConnection();
            $info['database_connection'] = 'SUCCESS';
        } catch (Exception $e) {
            $info['database_connection'] = 'FAILED: ' . $e->getMessage();
        }
    } else {
        $info['database_included'] = 'FAILED - file not found';
    }
} catch (Exception $e) {
    $info['database_included'] = 'ERROR: ' . $e->getMessage();
}

echo json_encode($info, JSON_PRETTY_PRINT);
