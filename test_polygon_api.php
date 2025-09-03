<?php
require_once 'config.php';

echo "=== TESTING POLYGON API ===\n\n";

// Check if API key is set
echo "Polygon API Key: " . (defined('POLYGON_API_KEY') ? substr(POLYGON_API_KEY, 0, 10) . '...' : 'NOT SET') . "\n\n";

// Test simple API call
$ticker = 'AAPL';
$url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers/{$ticker}?apikey=" . POLYGON_API_KEY;

echo "Testing URL: " . substr($url, 0, 80) . "...\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'EarningsTable/1.0'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Curl Error: " . ($error ?: 'None') . "\n";
echo "Response Length: " . strlen($response) . " bytes\n";

if ($response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "JSON Valid: Yes\n";
        if (isset($data['status'])) {
            echo "API Status: {$data['status']}\n";
        }
        if (isset($data['error'])) {
            echo "API Error: {$data['error']}\n";
        }
    } else {
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "No response received\n";
}

echo "\n=== CHECKING ENVIRONMENT ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "cURL Extension: " . (extension_loaded('curl') ? 'Loaded' : 'Not Loaded') . "\n";
echo "OpenSSL Extension: " . (extension_loaded('openssl') ? 'Loaded' : 'Not Loaded') . "\n";
?>
