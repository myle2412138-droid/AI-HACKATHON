<?php
/**
 * Test Papers API
 * Debug endpoint to check what's wrong
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$debug = [];

// Check PHP version
$debug['php_version'] = phpversion();

// Check if file exists
$papersApiPath = __DIR__ . '/../../services/papers-api.php';
$debug['papers_api_path'] = $papersApiPath;
$debug['papers_api_exists'] = file_exists($papersApiPath);

// Try to require
try {
    require_once $papersApiPath;
    $debug['require_success'] = true;
} catch (Exception $e) {
    $debug['require_error'] = $e->getMessage();
}

// Check if class exists
$debug['class_exists'] = class_exists('PapersAPI');

// Try to create instance
if (class_exists('PapersAPI')) {
    try {
        $api = new PapersAPI();
        $debug['instance_created'] = true;
        
        // Try a simple search
        $results = $api->searchSemanticScholar('machine learning', 5);
        $debug['search_test'] = [
            'success' => true,
            'count' => count($results),
            'first_result' => $results[0] ?? null
        ];
    } catch (Exception $e) {
        $debug['instance_error'] = $e->getMessage();
    }
}

// Check CURL
$debug['curl_available'] = function_exists('curl_init');

echo json_encode([
    'success' => true,
    'debug' => $debug
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
