<?php
// Final test to verify objects display correct values
echo "🎯 FINAL TEST: Market Cap Objects Verification\n\n";

// Get API data
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

// Filter records with non-zero market_cap_diff
$marketCapDiffs = [];
foreach ($data['data'] as $item) {
    if (isset($item['market_cap_diff']) && 
        $item['market_cap_diff'] !== null && 
        floatval($item['market_cap_diff']) !== 0) {
        $marketCapDiffs[] = $item;
    }
}

// Sort by market_cap_diff
usort($marketCapDiffs, function($a, $b) {
    return $b['market_cap_diff'] <=> $a['market_cap_diff'];
});

echo "📊 Total records with non-zero market_cap_diff: " . count($marketCapDiffs) . "\n\n";

// Find max and min
$maxDiff = $marketCapDiffs[0] ?? null;
$minDiff = end($marketCapDiffs) ?? null;

if ($maxDiff && $minDiff) {
    echo "🏆 EXPECTED MARKET CAP GAINER:\n";
    echo "  Ticker: {$maxDiff['ticker']}\n";
    echo "  Value: " . formatMarketCapDiff($maxDiff['market_cap_diff']) . "\n";
    echo "  Raw: {$maxDiff['market_cap_diff']}\n\n";
    
    echo "📉 EXPECTED MARKET CAP LOSER:\n";
    echo "  Ticker: {$minDiff['ticker']}\n";
    echo "  Value: " . formatMarketCapDiff($minDiff['market_cap_diff']) . "\n";
    echo "  Raw: {$minDiff['market_cap_diff']}\n\n";
    
    echo "✅ VERIFICATION:\n";
    echo "  - Market Cap Gainer should show: {$maxDiff['ticker']} " . formatMarketCapDiff($maxDiff['market_cap_diff']) . "\n";
    echo "  - Market Cap Loser should show: {$minDiff['ticker']} " . formatMarketCapDiff($minDiff['market_cap_diff']) . "\n";
    
    // Check if these match what we expect from the image
    if ($maxDiff['ticker'] === 'BABA' && $maxDiff['market_cap_diff'] > 9000000000) {
        echo "  ✅ BABA with +9.5B+ confirmed as top gainer\n";
    } else {
        echo "  ⚠️  Expected BABA as top gainer, got {$maxDiff['ticker']}\n";
    }
    
    if ($minDiff['ticker'] === 'MGIC' && $minDiff['market_cap_diff'] < -35000000) {
        echo "  ✅ MGIC with -35M+ confirmed as top loser\n";
    } else {
        echo "  ⚠️  Expected MGIC as top loser, got {$minDiff['ticker']}\n";
    }
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
