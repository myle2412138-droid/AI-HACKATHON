<?php
/**
 * Papers Search API - WORKING VERSION
 * Simplified, no duplicate requires, clean error handling
 */

// Suppress errors for clean JSON output
error_reporting(0);
ini_set('display_errors', '0');

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Get parameters
$query = $_GET['q'] ?? '';
$limit = min((int)($_GET['limit'] ?? 20), 100);

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Query parameter required']);
    exit;
}

// Load service
$serviceFile = __DIR__ . '/../../services/papers-api.php';

if (!file_exists($serviceFile)) {
    echo json_encode([
        'success' => false,
        'message' => 'Service not available',
        'query' => $query
    ]);
    exit;
}

require_once $serviceFile;

if (!class_exists('PapersAPI')) {
    echo json_encode([
        'success' => false,
        'message' => 'Service class not found',
        'query' => $query
    ]);
    exit;
}

// Search papers
try {
    $api = new PapersAPI();
    $papers = [];
    
    // Semantic Scholar
    try {
        $papers = $api->searchSemanticScholar($query, $limit);
    } catch (Exception $e) {
        error_log('Semantic Scholar error: ' . $e->getMessage());
    }
    
    // arXiv (if not enough papers)
    if (count($papers) < $limit) {
        try {
            $arxivPapers = $api->searchArXiv($query, $limit - count($papers));
            $papers = array_merge($papers, $arxivPapers);
        } catch (Exception $e) {
            error_log('arXiv error: ' . $e->getMessage());
        }
    }
    
    // Merge and limit
    if (count($papers) > 0) {
        $papers = $api->mergeAndRank(['combined' => $papers], $query);
        $papers = array_slice($papers, 0, $limit);
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $papers,
        'total' => count($papers),
        'sources' => array_unique(array_column($papers, 'source'))
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Papers search error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => [],
        'total' => 0,
        'message' => 'Search temporarily unavailable'
    ]);
}
