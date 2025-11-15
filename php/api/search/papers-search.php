<?php
/**
 * Papers Search API
 * Search papers from multiple sources + AI analysis
 * 
 * GET /api/search/papers-search.php?q=machine+learning&limit=20
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../services/papers-api.php';

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 20);

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Query required']);
    exit;
}

try {
    $papersAPI = new PapersAPI();
    
    // Search from multiple sources
    $semanticScholar = $papersAPI->searchSemanticScholar($query, $limit);
    $arxiv = $papersAPI->searchArXiv($query, 10);
    
    // Merge and rank
    $papers = $papersAPI->mergeAndRank([
        'semantic_scholar' => $semanticScholar,
        'arxiv' => $arxiv
    ], $query);
    
    // Limit results
    $papers = array_slice($papers, 0, $limit);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $papers,
        'total' => count($papers),
        'sources' => ['semantic_scholar', 'arxiv']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Papers search error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Search error',
        'error' => $e->getMessage()
    ]);
}

