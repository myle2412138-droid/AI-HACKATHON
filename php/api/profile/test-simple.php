<?php
/**
 * Simple Test - Check if PHP works
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'PHP is working!',
    'timestamp' => time(),
    'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'php_version' => phpversion()
]);

