<?php
/**
 * Test All APIs
 * Quick verification that all endpoints return proper JSON
 */

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>API Test Results</title>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
.test { margin: 20px 0; padding: 15px; border-radius: 8px; }
.success { background: #0f4c0f; border: 2px solid #0f0; }
.error { background: #4c0f0f; border: 2px solid #f00; }
.warning { background: #4c4c0f; border: 2px solid #ff0; }
h2 { color: #2563eb; }
pre { background: #000; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🧪 API Test Results</h1>";

// Test 1: Log Search (POST)
echo "<div class='test'>";
echo "<h2>1. Log Search API (POST)</h2>";

$data = [
    'user_id' => 'test-user',
    'query' => 'test query',
    'search_type' => 'papers',
    'results_count' => 10
];

$ch = curl_init('https://bkuteam.site/php/api/tracking/log-search.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='error'>❌ CURL Error: $error</div>";
} else {
    $json = json_decode($response, true);
    if ($json && $httpCode === 200) {
        echo "<div class='success'>✅ Status: $httpCode - Returns valid JSON</div>";
        echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='error'>❌ Status: $httpCode - Invalid response</div>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
}
echo "</div>";

// Test 2: Papers Search
echo "<div class='test'>";
echo "<h2>2. Papers Search API (GET)</h2>";

$ch = curl_init('https://bkuteam.site/php/api/search/papers-search.php?q=machine+learning&limit=5');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='error'>❌ CURL Error: $error</div>";
} else {
    $json = json_decode($response, true);
    if ($json) {
        $resultCount = isset($json['results']) ? count($json['results']) : 0;
        if ($httpCode === 200 && $json['success']) {
            echo "<div class='success'>✅ Status: $httpCode - Found $resultCount papers</div>";
        } else {
            echo "<div class='warning'>⚠️ Status: $httpCode - {$json['message']}</div>";
        }
        echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='error'>❌ Status: $httpCode - Invalid JSON response</div>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
}
echo "</div>";

// Test 3: Projects Search
echo "<div class='test'>";
echo "<h2>3. Projects Search API (GET)</h2>";

$ch = curl_init('https://bkuteam.site/php/api/projects/search.php?q=ai&limit=10');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<div class='error'>❌ CURL Error: $error</div>";
} else {
    $json = json_decode($response, true);
    if ($json && $httpCode === 200) {
        $resultCount = isset($json['results']) ? count($json['results']) : 0;
        echo "<div class='success'>✅ Status: $httpCode - Found $resultCount projects</div>";
        echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<div class='error'>❌ Status: $httpCode - Invalid response</div>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
}
echo "</div>";

// Summary
echo "<div class='test' style='background: #0f0f4c; border-color: #00f;'>";
echo "<h2>📊 Summary</h2>";
echo "<p>All APIs tested. Check results above for details.</p>";
echo "<p><strong>Expected:</strong> All should return valid JSON with success=true</p>";
echo "<p><strong>Note:</strong> Some APIs may return empty results if database tables don't exist yet.</p>";
echo "</div>";

echo "</body></html>";
