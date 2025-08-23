<?php
// Test script that simulates JavaScript calculateMetrics function
$url = "http://localhost/api/today-earnings-movements.php";
$response = file_get_contents($url);

if ($response === false) {
    echo "❌ Error fetching API data\n";
    exit;
}

$data = json_decode($response, true);

if (!$data || !isset($data['data'])) {
    echo "❌ Invalid API response\n";
    exit;
}

echo "🧮 SIMULATING JAVASCRIPT calculateMetrics FUNCTION\n\n";

// Simulate JavaScript filter for market_cap_diff
$marketCapChanges = [];
foreach ($data['data'] as $item) {
    if ($item['market_cap_diff'] !== null && 
        isset($item['market_cap_diff']) && 
        floatval($item['market_cap_diff']) !== 0) {
        $marketCapChanges[] = $item;
    }
}

echo "📊 Market Cap Changes count: " . count($marketCapChanges) . "\n";

if (count($marketCapChanges) > 0) {
    // Simulate JavaScript reduce for max (Gainer)
    $mcGainer = $marketCapChanges[0];
    foreach ($marketCapChanges as $item) {
        if (floatval($item['market_cap_diff']) > floatval($mcGainer['market_cap_diff'])) {
            $mcGainer = $item;
        }
    }
    
    // Simulate JavaScript reduce for min (Loser)
    $mcLoser = $marketCapChanges[0];
    foreach ($marketCapChanges as $item) {
        if (floatval($item['market_cap_diff']) < floatval($mcLoser['market_cap_diff'])) {
            $mcLoser = $item;
        }
    }
    
    echo "🏆 MC Gainer: {$mcGainer['ticker']} ({$mcGainer['market_cap_diff']})\n";
    echo "📉 MC Loser: {$mcLoser['ticker']} ({$mcLoser['market_cap_diff']})\n";
    
    // Format values like JavaScript formatMarketCapDiff
    $gainerFormatted = '';
    $loserFormatted = '';
    
    if ($mcGainer['market_cap_diff'] > 0) {
        $gainerFormatted = '+' . formatMarketCapDiff($mcGainer['market_cap_diff']);
    }
    
    if ($mcLoser['market_cap_diff'] < 0) {
        $loserFormatted = formatMarketCapDiff($mcLoser['market_cap_diff']);
    }
    
    echo "\n🎯 FORMATTED VALUES (like JavaScript):\n";
    echo "  MC Gainer: {$mcGainer['ticker']} {$gainerFormatted}\n";
    echo "  MC Loser: {$mcLoser['ticker']} {$loserFormatted}\n";
    
} else {
    echo "❌ No market cap changes found\n";
}

// Also test price changes
$priceChanges = [];
foreach ($data['data'] as $item) {
    if ($item['price_change_percent'] !== null && isset($item['price_change_percent'])) {
        $priceChanges[] = $item;
    }
}

if (count($priceChanges) > 0) {
    // Simulate JavaScript reduce for price gainer
    $priceGainer = $priceChanges[0];
    foreach ($priceChanges as $item) {
        if (floatval($item['price_change_percent']) > floatval($priceGainer['price_change_percent'])) {
            $priceGainer = $item;
        }
    }
    
    // Simulate JavaScript reduce for price loser
    $priceLoser = $priceChanges[0];
    foreach ($priceChanges as $item) {
        if (floatval($item['price_change_percent']) < floatval($priceLoser['price_change_percent'])) {
            $priceLoser = $item;
        }
    }
    
    echo "\n💰 PRICE CHANGES:\n";
    echo "  Price Gainer: {$priceGainer['ticker']} ({$priceGainer['price_change_percent']}%)\n";
    echo "  Price Loser: {$priceLoser['ticker']} ({$priceLoser['price_change_percent']}%)\n";
}

function formatMarketCapDiff($value) {
    $num = floatval($value);
    $sign = $num >= 0 ? '+' : '';
    if (abs($num) >= 1000000000) {
        return $sign . number_format($num / 1000000000, 1) . 'B';
    } else if (abs($num) >= 1000000) {
        return $sign . number_format($num / 1000000, 1) . 'M';
    } else if (abs($num) >= 1000) {
        return $sign . number_format($num / 1000, 1) . 'K';
    }
    return $sign . number_format($num);
}
?>
