<?php
/**
 * Minimal Update Test - No validation, just basic update
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

// Send simple response
echo json_encode([
    'success' => true,
    'message' => 'Test endpoint works!',
    'received_data' => $input,
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders()
], JSON_PRETTY_PRINT);

