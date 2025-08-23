<?php
require_once __DIR__ . '/config.php';

// Test API endpoint
$url = 'http://localhost/api/earnings-tickers-today.php';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "API Response Test:\n";
echo "==================\n";

if (isset($data['data']) && is_array($data['data'])) {
    echo "First 5 records:\n";
    for ($i = 0; $i < min(5, count($data['data'])); $i++) {
        $item = $data['data'][$i];
        echo sprintf(
            "%d. %s: market_cap=%.2f, market_cap_diff_billions=%.4f\n",
            $i + 1,
            $item['ticker'],
            $item['market_cap'] ?? 0,
            $item['market_cap_diff_billions'] ?? 0
        );
    }
    
    // Find BABA and MGIC
    echo "\nBABA and MGIC data:\n";
    foreach ($data['data'] as $item) {
        if (in_array($item['ticker'], ['BABA', 'MGIC'])) {
            echo sprintf(
                "%s: market_cap=%.2f, market_cap_diff_billions=%.4f\n",
                $item['ticker'],
                $item['market_cap'] ?? 0,
                $item['market_cap_diff_billions'] ?? 0
            );
        }
    }
} else {
    echo "Error: Invalid response format\n";
    print_r($data);
}
?>
