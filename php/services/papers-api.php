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
            'limit' => $limit,
            'fields' => 'paperId,title,abstract,authors,year,citationCount,url,openAccessPdf,publicationDate,venue'
        ]);
        
        $ch = curl_init("$url?$params");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log('Semantic Scholar API error: ' . $response);
            return [];
        }
        
        $data = json_decode($response, true);
        $papers = $data['data'] ?? [];
        
        // Format papers
        return array_map(function($paper) {
            return [
                'id' => $paper['paperId'],
                'source' => 'semantic_scholar',
                'title' => $paper['title'],
                'abstract' => $paper['abstract'] ?? '',
                'authors' => implode(', ', array_column($paper['authors'] ?? [], 'name')),
                'year' => $paper['year'],
                'citations' => $paper['citationCount'] ?? 0,
                'url' => $paper['url'] ?? '',
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
        $url = "http://export.arxiv.org/api/query";
        $params = http_build_query([
            'search_query' => "all:$query",
            'start' => 0,
            'max_results' => $maxResults,
            'sortBy' => 'relevance',
            'sortOrder' => 'descending'
        ]);
        
        $response = @file_get_contents("$url?$params");
        
        if (!$response) {
            return [];
        }
        
        // Parse XML
        $xml = simplexml_load_string($response);
        $papers = [];
        
        foreach ($xml->entry as $entry) {
            $papers[] = [
                'id' => (string)$entry->id,
                'source' => 'arxiv',
                'title' => (string)$entry->title,
                'abstract' => (string)$entry->summary,
                'authors' => implode(', ', array_map(fn($a) => (string)$a->name, iterator_to_array($entry->author))),
                'year' => substr((string)$entry->published, 0, 4),
                'citations' => 0,
                'url' => (string)$entry->id,
                'pdf_url' => (string)$entry->id . '.pdf',
                'thumbnail' => '/assets/arxiv-default.png',
                'published_date' => (string)$entry->published
            ];
        }
        
        return $papers;
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

