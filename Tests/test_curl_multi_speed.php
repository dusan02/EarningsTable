<?php
require_once 'config.php';

echo "=== CURL_MULTI SPEED TEST ===\n\n";

// Test tickers - current tickers
require_once 'test_helper.php';
$testTickers = TestHelper::getCurrentTickers(10);
TestHelper::printTickerInfo($testTickers, "Testing cURL Multi Speed for");

echo "Testing with " . count($testTickers) . " tickers...\n\n";

// Test 1: Sequential requests
echo "=== TEST 1: SEQUENTIAL REQUESTS ===\n";
$startTime = microtime(true);

$sequentialResults = [];
foreach ($testTickers as $ticker) {
    $apiKey = POLYGON_API_KEY;
    $url = "https://api.polygon.io/v3/reference/tickers/{$ticker}?apiKey={$apiKey}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => [
                'User-Agent: EarningsTable/1.0',
                'Accept: application/json'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['results'])) {
            $marketCap = $data['results']['market_cap'] ?? null;
            $sequentialResults[$ticker] = $marketCap;
            echo "  ✅ {$ticker}: " . ($marketCap ? '$' . number_format($marketCap / 1000000000, 1) . 'B' : 'No MC') . "\n";
        }
    }
    
    usleep(100000); // 0.1 second delay
}

$sequentialTime = round(microtime(true) - $startTime, 2);
echo "Sequential time: {$sequentialTime}s\n\n";

// Test 2: Concurrent requests with curl_multi
echo "=== TEST 2: CONCURRENT REQUESTS (CURL_MULTI) ===\n";
$startTime = microtime(true);

$concurrentResults = [];

// Create concurrent requests using curl_multi
$mh = curl_multi_init();
$curlHandles = [];

foreach ($testTickers as $ticker) {
    $apiKey = POLYGON_API_KEY;
    $url = "https://api.polygon.io/v3/reference/tickers/{$ticker}?apiKey={$apiKey}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EarningsTable/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    curl_multi_add_handle($mh, $ch);
    $curlHandles[$ticker] = $ch;
}

// Execute concurrent requests
$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

// Process results
foreach ($testTickers as $ticker) {
    $ch = $curlHandles[$ticker];
    $response = curl_multi_getcontent($ch);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['results'])) {
            $marketCap = $data['results']['market_cap'] ?? null;
            $concurrentResults[$ticker] = $marketCap;
            echo "  ✅ {$ticker}: " . ($marketCap ? '$' . number_format($marketCap / 1000000000, 1) . 'B' : 'No MC') . "\n";
        }
    }
    
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}

curl_multi_close($mh);

$concurrentTime = round(microtime(true) - $startTime, 2);
echo "Concurrent time: {$concurrentTime}s\n\n";

// Comparison
echo "=== SPEED COMPARISON ===\n";
$speedup = $sequentialTime > 0 ? round($sequentialTime / $concurrentTime, 2) : 0;
echo "Sequential: {$sequentialTime}s\n";
echo "Concurrent: {$concurrentTime}s\n";
echo "Speedup: {$speedup}x faster\n";

if ($speedup > 1) {
    echo "✅ curl_multi is " . $speedup . "x faster!\n";
} else {
    echo "❌ No significant speedup\n";
}

echo "\n=== RESULTS COMPARISON ===\n";
$matchCount = 0;
foreach ($testTickers as $ticker) {
    $seq = $sequentialResults[$ticker] ?? null;
    $conc = $concurrentResults[$ticker] ?? null;
    
    if ($seq === $conc) {
        $matchCount++;
        echo "✅ {$ticker}: Match\n";
    } else {
        echo "❌ {$ticker}: Different (Seq: " . ($seq ? '$' . number_format($seq / 1000000000, 1) . 'B' : 'null') . 
             ", Conc: " . ($conc ? '$' . number_format($conc / 1000000000, 1) . 'B' : 'null') . ")\n";
    }
}

echo "\nData accuracy: {$matchCount}/" . count($testTickers) . " matches\n";
?>
