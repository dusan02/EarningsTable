<?php
// Debug script to check market_cap_diff data
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

echo "🔍 DEBUG: Market Cap Diff Analysis\n\n";

// Filter records with market_cap_diff
$marketCapDiffs = [];
foreach ($data['data'] as $record) {
    if (isset($record['market_cap_diff']) && $record['market_cap_diff'] !== null) {
        $marketCapDiffs[] = [
            'ticker' => $record['ticker'],
            'market_cap_diff' => $record['market_cap_diff'],
            'market_cap' => $record['market_cap'] ?? 0
        ];
    }
}

// Sort by market_cap_diff
usort($marketCapDiffs, function($a, $b) {
    return $b['market_cap_diff'] <=> $a['market_cap_diff'];
});

echo "📊 Top 10 Market Cap Gainers (highest positive diff):\n";
for ($i = 0; $i < min(10, count($marketCapDiffs)); $i++) {
    $item = $marketCapDiffs[$i];
    if ($item['market_cap_diff'] > 0) {
        $formatted = $item['market_cap_diff'] >= 1000000000 ? 
            number_format($item['market_cap_diff'] / 1000000000, 1) . 'B' :
            number_format($item['market_cap_diff'] / 1000000, 1) . 'M';
        echo "  {$item['ticker']}: +{$formatted} ({$item['market_cap_diff']})\n";
    }
}

echo "\n📉 Top 10 Market Cap Losers (lowest negative diff):\n";
for ($i = count($marketCapDiffs) - 1; $i >= max(0, count($marketCapDiffs) - 10); $i--) {
    $item = $marketCapDiffs[$i];
    if ($item['market_cap_diff'] < 0) {
        $formatted = abs($item['market_cap_diff']) >= 1000000000 ? 
            number_format(abs($item['market_cap_diff']) / 1000000000, 1) . 'B' :
            number_format(abs($item['market_cap_diff']) / 1000000, 1) . 'M';
        echo "  {$item['ticker']}: -{$formatted} ({$item['market_cap_diff']})\n";
    }
}

echo "\n📈 Summary:\n";
echo "  Total records with market_cap_diff: " . count($marketCapDiffs) . "\n";
echo "  Positive diffs: " . count(array_filter($marketCapDiffs, fn($x) => $x['market_cap_diff'] > 0)) . "\n";
echo "  Negative diffs: " . count(array_filter($marketCapDiffs, fn($x) => $x['market_cap_diff'] < 0)) . "\n";
echo "  Zero diffs: " . count(array_filter($marketCapDiffs, fn($x) => $x['market_cap_diff'] == 0)) . "\n";

// Check for specific tickers mentioned in the image
$specificTickers = ['BABA', 'MGIC', 'HYPR', 'LGVN'];
echo "\n🎯 Specific tickers check:\n";
foreach ($specificTickers as $ticker) {
    $found = false;
    foreach ($data['data'] as $record) {
        if ($record['ticker'] === $ticker) {
            $diff = $record['market_cap_diff'] ?? 'NULL';
            $formatted = '';
            if ($diff !== 'NULL' && $diff !== null) {
                $formatted = $diff >= 1000000000 ? 
                    number_format($diff / 1000000000, 1) . 'B' :
                    number_format($diff / 1000000, 1) . 'M';
                $formatted = $diff >= 0 ? "+{$formatted}" : "-{$formatted}";
            }
            echo "  {$ticker}: {$formatted} ({$diff})\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "  {$ticker}: Not found\n";
    }
}
?>
