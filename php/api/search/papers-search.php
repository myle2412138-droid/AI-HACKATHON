<?php
/**
 * Papers Search API - Clean Simple Version
 */

error_reporting(0);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$query = $_GET['q'] ?? '';
$limit = min((int)($_GET['limit'] ?? 20), 100);

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Query required']);
    exit;
}

$serviceFile = __DIR__ . '/../../services/papers-api.php';
if (!file_exists($serviceFile)) {
    echo json_encode(['success' => false, 'message' => 'Service not found']);
    exit;
}

require_once $serviceFile;

if (!class_exists('PapersAPI')) {
    echo json_encode(['success' => false, 'message' => 'Class not found']);
    exit;
}

try {
    $api = new PapersAPI();
    $papers = [];
    
    try {
        $papers = $api->searchSemanticScholar($query, $limit);
    } catch (Exception $e) {
        error_log('Search error: ' . $e->getMessage());
    }
    
    if (count($papers) < $limit) {
        try {
            $arxiv = $api->searchArXiv($query, $limit - count($papers));
            $papers = array_merge($papers, $arxiv);
        } catch (Exception $e) {
            error_log('arXiv error: ' . $e->getMessage());
        }
    }
    
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
    error_log('Error: ' . $e->getMessage());
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => [],
        'total' => 0,
        'message' => 'Temporarily unavailable'
    ]);
}

