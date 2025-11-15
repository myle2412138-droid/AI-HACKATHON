<?php
/**
 * Network Connectivity Test
 * Diagnose why external API calls fail
 */

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>Network Test</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#0f0;}
.pass{color:#0f0;} .fail{color:#f00;} .warn{color:#ff0;} pre{background:#000;padding:10px;}
</style></head><body>";

echo "<h1>🔍 Network Connectivity Diagnostic</h1>";

// Test 1: Basic DNS resolution
echo "<h2>1. DNS Resolution Test</h2>";
$host = 'api.semanticscholar.org';
$ip = gethostbyname($host);
if ($ip !== $host) {
    echo "<p class='pass'>✅ DNS works: $host → $ip</p>";
} else {
    echo "<p class='fail'>❌ DNS failed: Cannot resolve $host</p>";
    echo "<p>Solution: Check DNS settings in Windows Server</p>";
}

// Test 2: Socket connection
echo "<h2>2. Socket Connection Test</h2>";
$errno = 0;
$errstr = '';
$socket = @fsockopen('api.semanticscholar.org', 443, $errno, $errstr, 5);
if ($socket) {
    echo "<p class='pass'>✅ Can open socket to api.semanticscholar.org:443</p>";
    fclose($socket);
} else {
    echo "<p class='fail'>❌ Socket failed: $errstr (Error: $errno)</p>";
    echo "<p class='warn'>⚠️ This means Windows Firewall is blocking outbound HTTPS!</p>";
    echo "<pre>Fix: Run as Administrator in PowerShell:
New-NetFirewallRule -DisplayName 'Allow HTTPS Outbound' -Direction Outbound -Protocol TCP -RemotePort 443 -Action Allow
</pre>";
}

// Test 3: file_get_contents with detailed error
echo "<h2>3. file_get_contents Test (Detailed)</h2>";
$url = 'https://api.semanticscholar.org/graph/v1/paper/search?query=test&limit=1&fields=title';

$opts = [
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\n" .
                   "User-Agent: PHP/" . phpversion() . "\r\n"
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
];

$context = stream_context_create($opts);

// Capture errors
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<p>Attempting to fetch: <code>$url</code></p>";

$result = @file_get_contents($url, false, $context);
$error = error_get_last();

if ($result !== false) {
    $data = json_decode($result, true);
    if ($data && isset($data['data'])) {
        echo "<p class='pass'>✅ SUCCESS! API is accessible</p>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p class='warn'>⚠️ Got response but invalid JSON</p>";
        echo "<pre>" . htmlspecialchars($result) . "</pre>";
    }
} else {
    echo "<p class='fail'>❌ FAILED: Cannot access API</p>";
    
    if ($error) {
        echo "<p>Error: " . htmlspecialchars($error['message']) . "</p>";
    }
    
    // Show HTTP response headers if available
    if (isset($http_response_header)) {
        echo "<h3>HTTP Response Headers:</h3><pre>";
        foreach ($http_response_header as $header) {
            echo htmlspecialchars($header) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='fail'>No HTTP response received at all!</p>";
        echo "<p class='warn'>⚠️ Most likely cause: <strong>Windows Firewall blocking outbound HTTPS</strong></p>";
    }
}

// Test 4: Alternative test with simple HTTP (not HTTPS)
echo "<h2>4. HTTP Test (Port 80)</h2>";
$httpUrl = 'http://export.arxiv.org/api/query?search_query=test&max_results=1';
$httpResult = @file_get_contents($httpUrl, false, stream_context_create([
    'http' => ['timeout' => 5, 'ignore_errors' => true]
]));

if ($httpResult !== false) {
    echo "<p class='pass'>✅ HTTP (port 80) works</p>";
    echo "<p class='warn'>⚠️ But HTTPS (port 443) is blocked!</p>";
} else {
    echo "<p class='fail'>❌ Both HTTP and HTTPS are blocked</p>";
}

// Test 5: Check stream wrappers
echo "<h2>5. PHP Stream Wrappers</h2>";
$wrappers = stream_get_wrappers();
echo "<p>Available: " . implode(', ', $wrappers) . "</p>";
if (in_array('https', $wrappers)) {
    echo "<p class='pass'>✅ HTTPS wrapper is enabled</p>";
} else {
    echo "<p class='fail'>❌ HTTPS wrapper is disabled</p>";
    echo "<p>Solution: Enable OpenSSL extension in php.ini</p>";
}

// Final recommendations
echo "<h2>📋 Recommended Actions</h2>";
echo "<ol>";
echo "<li><strong>Enable Windows Firewall Rule:</strong><pre>
PowerShell (as Admin):
New-NetFirewallRule -DisplayName 'PHP HTTPS Outbound' -Direction Outbound -Protocol TCP -RemotePort 443 -Action Allow
</pre></li>";

echo "<li><strong>Or disable Windows Firewall temporarily to test:</strong><pre>
netsh advfirewall set allprofiles state off
(Test, then turn back on)
netsh advfirewall set allprofiles state on
</pre></li>";

echo "<li><strong>Check IIS Application Pool Identity:</strong><br>
Make sure it has network access permissions</li>";

echo "<li><strong>Contact hosting provider if on shared hosting</strong></li>";
echo "</ol>";

echo "<h2>🔧 Quick Fix Commands</h2>";
echo "<pre>
# Run these commands in PowerShell as Administrator on your server:

# 1. Allow HTTPS outbound
New-NetFirewallRule -DisplayName 'Allow HTTPS Out' -Direction Outbound -Protocol TCP -RemotePort 443 -Action Allow

# 2. Restart IIS
iisreset

# 3. Test again
# Visit this page again and check if tests pass
</pre>";

echo "</body></html>";
?>
