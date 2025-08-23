<?php
require_once __DIR__ . '/config.php';

// Simulate what frontend receives
$url = 'http://localhost/api/earnings-tickers-today.php';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "Frontend Data Test:\n";
echo "==================\n";

if (isset($data['data']) && is_array($data['data'])) {
    // Find BABA and MGIC
    $baba = null;
    $mgic = null;
    
    foreach ($data['data'] as $item) {
        if ($item['ticker'] === 'BABA') {
            $baba = $item;
        }
        if ($item['ticker'] === 'MGIC') {
            $mgic = $item;
        }
    }
    
    echo "BABA data:\n";
    if ($baba) {
        echo "  market_cap: " . ($baba['market_cap'] ?? 'NULL') . "\n";
        echo "  market_cap_diff: " . ($baba['market_cap_diff'] ?? 'NULL') . "\n";
        echo "  market_cap_diff_billions: " . ($baba['market_cap_diff_billions'] ?? 'NULL') . "\n";
    } else {
        echo "  NOT FOUND\n";
    }
    
    echo "\nMGIC data:\n";
    if ($mgic) {
        echo "  market_cap: " . ($mgic['market_cap'] ?? 'NULL') . "\n";
        echo "  market_cap_diff: " . ($mgic['market_cap_diff'] ?? 'NULL') . "\n";
        echo "  market_cap_diff_billions: " . ($mgic['market_cap_diff_billions'] ?? 'NULL') . "\n";
    } else {
        echo "  NOT FOUND\n";
    }
    
    // Simulate JavaScript calculation
    echo "\nJavaScript calculation simulation:\n";
    $mcGainer = null;
    $maxDiff = 0;
    
    foreach ($data['data'] as $item) {
        $diff = 0;
        if ($item['market_cap_diff'] !== null) {
            $diff = floatval($item['market_cap_diff']);
        } elseif ($item['market_cap_diff_billions'] !== null) {
            $diff = floatval($item['market_cap_diff_billions']) * 1e9;
        }
        
        if ($diff > 0 && $diff > $maxDiff) {
            $maxDiff = $diff;
            $mcGainer = $item;
        }
    }
    
    if ($mcGainer) {
        echo "  Market Cap Gainer: " . $mcGainer['ticker'] . "\n";
        echo "  Diff value: " . $maxDiff . "\n";
        echo "  Diff billions: " . ($maxDiff / 1e9) . "\n";
    } else {
        echo "  No gainer found\n";
    }
    
} else {
    echo "Error: Invalid response format\n";
}
?>
