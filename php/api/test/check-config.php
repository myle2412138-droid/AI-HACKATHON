<?php
/**
 * PHP Configuration Check
 * Check server capabilities for API integration
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$config = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'extensions' => [
        'curl' => extension_loaded('curl'),
        'json' => extension_loaded('json'),
        'simplexml' => extension_loaded('simplexml'),
        'mbstring' => extension_loaded('mbstring')
    ],
    'settings' => [
        'allow_url_fopen' => (bool)ini_get('allow_url_fopen'),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'display_errors' => ini_get('display_errors'),
        'error_reporting' => error_reporting()
    ],
    'functions' => [
        'file_get_contents' => function_exists('file_get_contents'),
        'curl_init' => function_exists('curl_init'),
        'simplexml_load_string' => function_exists('simplexml_load_string')
    ],
    'paths' => [
        'script_dir' => __DIR__,
        'service_file' => __DIR__ . '/../../services/papers-api.php',
        'service_exists' => file_exists(__DIR__ . '/../../services/papers-api.php')
    ]
];

// Test external URL access
$config['network_test'] = [
    'can_access_external' => false,
    'test_url' => 'https://api.semanticscholar.org/graph/v1/paper/search?query=test&limit=1&fields=paperId,title',
    'error' => null
];

if (ini_get('allow_url_fopen')) {
    try {
        $context = stream_context_create([
            'http' => ['timeout' => 5, 'ignore_errors' => true],
            'ssl' => ['verify_peer' => false]
        ]);
        $result = @file_get_contents($config['network_test']['test_url'], false, $context);
        if ($result !== false) {
            $config['network_test']['can_access_external'] = true;
            $data = json_decode($result, true);
            $config['network_test']['response_valid'] = isset($data['data']);
        } else {
            $config['network_test']['error'] = 'file_get_contents returned false';
        }
    } catch (Exception $e) {
        $config['network_test']['error'] = $e->getMessage();
    }
}

// Recommendations
$config['recommendations'] = [];

if (!$config['settings']['allow_url_fopen']) {
    $config['recommendations'][] = 'Enable allow_url_fopen in php.ini';
}

if (!$config['extensions']['curl'] && !$config['settings']['allow_url_fopen']) {
    $config['recommendations'][] = 'CRITICAL: Either enable CURL extension OR allow_url_fopen';
}

if (!$config['extensions']['simplexml']) {
    $config['recommendations'][] = 'Enable simplexml extension for arXiv API support';
}

if ($config['settings']['display_errors'] === '1') {
    $config['recommendations'][] = 'Set display_errors = Off in php.ini for production';
}

$config['status'] = empty($config['recommendations']) ? 'ready' : 'needs_configuration';

echo json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
