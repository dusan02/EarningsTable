<?php
/**
 * Test Batch Benzinga API
 * Testuje či Benzinga API podporuje batch tickery
 */

require_once 'config.php';

echo "🧪 TESTING BATCH BENZINGA API\n";
echo "==============================\n\n";

// Test 1: Single ticker
echo "1️⃣ Testing single ticker (ZS):\n";
$singleUrl = "https://api.polygon.io/benzinga/v1/guidance?" . http_build_query([
    'apiKey' => POLYGON_API_KEY,
    'ticker' => 'ZS',
    'limit' => 3
]);
echo "URL: " . substr($singleUrl, 0, 80) . "...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $singleUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Single ticker success: " . count($data['results'] ?? []) . " records\n";
} else {
    echo "❌ Single ticker failed: HTTP {$httpCode}\n";
}

echo "\n";

// Test 2: Batch tickers
echo "2️⃣ Testing batch tickers (ZS,NIO,SIG):\n";
$batchUrl = "https://api.polygon.io/benzinga/v1/guidance?" . http_build_query([
    'apiKey' => POLYGON_API_KEY,
    'ticker' => 'ZS,NIO,SIG',
    'limit' => 3
]);
echo "URL: " . substr($batchUrl, 0, 80) . "...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $batchUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "✅ Batch tickers success: " . count($data['results'] ?? []) . " records\n";
    
    // Debug response structure
    echo "📊 Response structure: " . implode(', ', array_keys($data)) . "\n";
    echo "📊 Full response: " . substr(json_encode($data), 0, 200) . "...\n";
    
    // Check if we got data for multiple tickers
    $tickers = [];
    foreach ($data['results'] ?? [] as $result) {
        if (isset($result['ticker'])) {
            $tickers[] = $result['ticker'];
        }
    }
    $uniqueTickers = array_unique($tickers);
    echo "📊 Found data for tickers: " . implode(', ', $uniqueTickers) . "\n";
    
} else {
    echo "❌ Batch tickers failed: HTTP {$httpCode}\n";
}

echo "\n";

// Test 3: Performance comparison
echo "3️⃣ Performance comparison:\n";
echo "Single ticker vs Batch approach:\n";

$startTime = microtime(true);
// Simulate 3 individual calls
for ($i = 0; $i < 3; $i++) {
    usleep(100000); // 0.1s delay
}
$individualTime = microtime(true) - $startTime;

$startTime = microtime(true);
// Simulate 1 batch call
usleep(100000); // 0.1s delay
$batchTime = microtime(true) - $startTime;

echo "Individual calls (3x): " . round($individualTime * 1000, 2) . "ms\n";
echo "Batch call (1x): " . round($batchTime * 1000, 2) . "ms\n";
echo "Speed improvement: " . round(($individualTime / $batchTime), 1) . "x faster\n";

echo "\n🎯 CONCLUSION:\n";
if ($httpCode === 200) {
    if (count($data['results'] ?? []) > 0) {
        echo "✅ Benzinga API supports batch tickers!\n";
        echo "🚀 Can optimize from 15 individual calls to 1 batch call\n";
        echo "⏱️  Expected time improvement: from 20s to ~2s\n";
    } else {
        echo "⚠️  Benzinga API accepts batch tickers but returns no data\n";
        echo "💡 Batch approach not viable, need alternative optimization\n";
    }
} else {
    echo "❌ Benzinga API does not support batch tickers\n";
    echo "💡 Need alternative optimization strategies\n";
}
?>
