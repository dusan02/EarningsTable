<?php
require_once __DIR__ . '/config.php';

// Test API endpoint
$url = 'http://localhost/api/earnings-tickers-today.php';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "API Response Count Test:\n";
echo "=======================\n";

if (isset($data['data']) && is_array($data['data'])) {
    echo "Total records returned: " . count($data['data']) . "\n";
    echo "Expected: 948 (from database)\n";
    
    // Check if MGIC is in the response
    $mgicFound = false;
    foreach ($data['data'] as $item) {
        if ($item['ticker'] === 'MGIC') {
            $mgicFound = true;
            echo "MGIC found at position: " . (array_search($item, $data['data']) + 1) . "\n";
            echo "MGIC data: market_cap_diff_billions = " . ($item['market_cap_diff_billions'] ?? 'NULL') . "\n";
            break;
        }
    }
    
    if (!$mgicFound) {
        echo "MGIC NOT FOUND in API response!\n";
    }
    
    // Check first 10 records
    echo "\nFirst 10 records:\n";
    for ($i = 0; $i < min(10, count($data['data'])); $i++) {
        $item = $data['data'][$i];
        echo sprintf(
            "%d. %s: market_cap_diff_billions=%.4f\n",
            $i + 1,
            $item['ticker'],
            $item['market_cap_diff_billions'] ?? 0
        );
    }
    
} else {
    echo "Error: Invalid response format\n";
    print_r($data);
}
?>
