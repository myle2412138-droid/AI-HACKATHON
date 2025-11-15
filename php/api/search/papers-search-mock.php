<?php
/**
 * Papers Search API - Simple Mock Version
 * Trả về mock data để frontend hoạt động trong khi fix backend
 * 
 * GET /api/search/papers-search-mock.php?q=machine+learning&limit=20
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = $_GET['q'] ?? '';
$limit = (int)($_GET['limit'] ?? 20);

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Query required']);
    exit;
}

// Mock data
$mockPapers = [
    [
        'id' => 'mock_1',
        'source' => 'semantic_scholar',
        'title' => 'Deep Learning Applications in ' . $query,
        'abstract' => 'This paper presents a comprehensive study on ' . $query . '. We propose a novel approach that achieves state-of-the-art results on multiple benchmarks. Our method demonstrates significant improvements over existing techniques.',
        'authors' => 'John Doe, Jane Smith, Alex Johnson',
        'year' => 2024,
        'citations' => 156,
        'url' => 'https://www.semanticscholar.org',
        'pdf_url' => null,
        'venue' => 'NeurIPS 2024',
        'thumbnail' => '/assets/paper-default.png'
    ],
    [
        'id' => 'mock_2',
        'source' => 'arxiv',
        'title' => 'Advanced Techniques for ' . $query,
        'abstract' => 'We introduce advanced techniques for solving problems related to ' . $query . '. Our experimental results show promising outcomes across various datasets and real-world scenarios.',
        'authors' => 'Sarah Wilson, Michael Brown',
        'year' => 2023,
        'citations' => 89,
        'url' => 'https://arxiv.org',
        'pdf_url' => 'https://arxiv.org/pdf/2301.00000.pdf',
        'venue' => 'arXiv preprint',
        'thumbnail' => '/assets/paper-default.png'
    ],
    [
        'id' => 'mock_3',
        'source' => 'semantic_scholar',
        'title' => 'A Survey on ' . $query . ' Methods',
        'abstract' => 'This survey provides a comprehensive overview of recent advances in ' . $query . '. We categorize existing methods, discuss their strengths and limitations, and identify future research directions.',
        'authors' => 'David Lee, Emma Davis, Robert Taylor',
        'year' => 2024,
        'citations' => 234,
        'url' => 'https://www.semanticscholar.org',
        'pdf_url' => null,
        'venue' => 'ACM Computing Surveys',
        'thumbnail' => '/assets/paper-default.png'
    ],
    [
        'id' => 'mock_4',
        'source' => 'semantic_scholar',
        'title' => 'Real-world Applications of ' . $query,
        'abstract' => 'We present several real-world applications of ' . $query . ' in industry settings. Case studies demonstrate the practical impact and effectiveness of our proposed solutions.',
        'authors' => 'Lisa Anderson, James Martinez',
        'year' => 2023,
        'citations' => 67,
        'url' => 'https://www.semanticscholar.org',
        'pdf_url' => null,
        'venue' => 'ICML 2023',
        'thumbnail' => '/assets/paper-default.png'
    ],
    [
        'id' => 'mock_5',
        'source' => 'arxiv',
        'title' => 'Novel Framework for ' . $query,
        'abstract' => 'This work proposes a novel framework addressing key challenges in ' . $query . '. Through extensive experiments, we validate the effectiveness and scalability of our approach.',
        'authors' => 'Chris Wilson, Amanda Clark',
        'year' => 2024,
        'citations' => 42,
        'url' => 'https://arxiv.org',
        'pdf_url' => 'https://arxiv.org/pdf/2402.00000.pdf',
        'venue' => 'arXiv preprint',
        'thumbnail' => '/assets/paper-default.png'
    ]
];

// Limit results
$papers = array_slice($mockPapers, 0, min($limit, count($mockPapers)));

echo json_encode([
    'success' => true,
    'query' => $query,
    'results' => $papers,
    'total' => count($papers),
    'sources' => ['semantic_scholar', 'arxiv'],
    'mock' => true,
    'message' => 'Using mock data - backend being fixed'
], JSON_UNESCAPED_UNICODE);
