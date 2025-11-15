<?php
/**
 * Papers API Service
 * Tích hợp với Semantic Scholar, arXiv, PubMed
 */

class PapersAPI {
    
    /**
     * Search Semantic Scholar (200M+ papers, có thumbnails)
     */
    public function searchSemanticScholar($query, $limit = 20) {
        $url = "https://api.semanticscholar.org/graph/v1/paper/search";
        $params = http_build_query([
            'query' => $query,
            'limit' => min($limit, 100), // Max 100 per request
            'fields' => 'paperId,title,abstract,authors,year,citationCount,url,openAccessPdf,publicationDate,venue'
        ]);
        
        // Use file_get_contents instead of CURL (for servers without curl extension)
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'timeout' => 10,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents("$url?$params", false, $context);
        
        if ($response === false) {
            error_log('Semantic Scholar API: Network error');
            throw new Exception('Network error: Could not connect to Semantic Scholar API');
        }
        
        // Get HTTP response code
        $httpCode = 200;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    break;
                }
            }
        }
        
        if ($httpCode !== 200) {
            error_log('Semantic Scholar API error (' . $httpCode . '): ' . $response);
            throw new Exception('API returned status ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['data'])) {
            error_log('Invalid JSON response from Semantic Scholar');
            throw new Exception('Invalid API response');
        }
        
        $papers = $data['data'] ?? [];
        
        // Format papers
        return array_map(function($paper) {
            return [
                'id' => $paper['paperId'],
                'source' => 'semantic_scholar',
                'title' => $paper['title'],
                'abstract' => $paper['abstract'] ?? 'No abstract available',
                'authors' => implode(', ', array_column($paper['authors'] ?? [], 'name')),
                'year' => $paper['year'] ?? 'N/A',
                'citations' => $paper['citationCount'] ?? 0,
                'url' => $paper['url'] ?? 'https://www.semanticscholar.org/paper/' . $paper['paperId'],
                'pdf_url' => $paper['openAccessPdf']['url'] ?? null,
                'venue' => $paper['venue'] ?? 'Unknown',
                'thumbnail' => $this->generateThumbnail($paper),
                'published_date' => $paper['publicationDate'] ?? null
            ];
        }, $papers);
    }
    
    /**
     * Search arXiv
     */
    public function searchArXiv($query, $maxResults = 20) {
        try {
            $url = "http://export.arxiv.org/api/query";
            $params = http_build_query([
                'search_query' => "all:$query",
                'start' => 0,
                'max_results' => $maxResults,
                'sortBy' => 'relevance',
                'sortOrder' => 'descending'
            ]);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents("$url?$params", false, $context);
            
            if (!$response) {
                error_log('arXiv API: No response');
                return [];
            }
            
            // Parse XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            
            if (!$xml) {
                error_log('arXiv API: XML parse error');
                return [];
            }
            
            $papers = [];
            
            if (!isset($xml->entry)) {
                return [];
            }
            
            foreach ($xml->entry as $entry) {
                $authors = [];
                if (isset($entry->author)) {
                    foreach ($entry->author as $author) {
                        if (isset($author->name)) {
                            $authors[] = (string)$author->name;
                        }
                    }
                }
                
                $papers[] = [
                    'id' => (string)$entry->id,
                    'source' => 'arxiv',
                    'title' => (string)$entry->title,
                    'abstract' => (string)$entry->summary,
                    'authors' => implode(', ', $authors),
                    'year' => substr((string)$entry->published, 0, 4),
                    'citations' => 0,
                    'url' => (string)$entry->id,
                    'pdf_url' => (string)$entry->id . '.pdf',
                    'thumbnail' => '/assets/arxiv-default.png',
                    'published_date' => (string)$entry->published
                ];
            }
            
            return $papers;
        } catch (Exception $e) {
            error_log('arXiv API exception: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Merge results từ nhiều nguồn
     */
    public function mergeAndRank($results, $query = '') {
        $merged = [];
        
        foreach ($results as $source => $papers) {
            $merged = array_merge($merged, $papers);
        }
        
        // Remove duplicates by title
        $unique = [];
        $seen = [];
        
        foreach ($merged as $paper) {
            $key = strtolower(trim($paper['title']));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $paper;
            }
        }
        
        // Sort by citations (descending)
        usort($unique, function($a, $b) {
            return $b['citations'] - $a['citations'];
        });
        
        return $unique;
    }
    
    /**
     * Generate thumbnail cho paper
     */
    private function generateThumbnail($paper) {
        // Semantic Scholar có thể có thumbnail
        // Fallback: Use first author initial + color
        $initial = substr($paper['title'], 0, 1);
        $color = $this->stringToColor($paper['title']);
        
        // Return placeholder image URL
        return "https://ui-avatars.com/api/?name=" . urlencode($initial) . "&background=$color&color=fff&size=400";
    }
    
    /**
     * Convert string to color hex
     */
    private function stringToColor($str) {
        $hash = md5($str);
        return substr($hash, 0, 6);
    }
}

