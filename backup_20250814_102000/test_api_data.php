<?php
// Test API data to check if eps_estimate and revenue_estimate are included
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

echo "📊 API Response Summary:\n";
echo "  Status: " . ($data['status'] ?? 'unknown') . "\n";
echo "  Count: " . ($data['count'] ?? 0) . "\n";
echo "  Date: " . ($data['date'] ?? 'unknown') . "\n\n";

// Check first few records for eps_estimate and revenue_estimate
$sampleCount = min(3, count($data['data']));
echo "📋 Sample records (first $sampleCount):\n";

for ($i = 0; $i < $sampleCount; $i++) {
    $record = $data['data'][$i];
    echo "  {$record['ticker']}:\n";
    echo "    Market Cap: " . ($record['market_cap'] ?? 'NULL') . "\n";
    echo "    Market Cap Diff: " . ($record['market_cap_diff'] ?? 'NULL') . "\n";
    echo "    Price Change %: " . ($record['price_change_percent'] ?? 'NULL') . "\n";
    echo "    EPS Actual: " . ($record['eps_actual'] ?? 'NULL') . "\n";
    echo "    EPS Estimate: " . ($record['eps_estimate'] ?? 'NULL') . "\n";
    echo "    Revenue Actual: " . ($record['revenue_actual'] ?? 'NULL') . "\n";
    echo "    Revenue Estimate: " . ($record['revenue_estimate'] ?? 'NULL') . "\n";
    echo "\n";
}

// Count records with actual data
$withEpsActual = 0;
$withRevenueActual = 0;
$withEpsEstimate = 0;
$withRevenueEstimate = 0;

foreach ($data['data'] as $record) {
    if (!empty($record['eps_actual']) && $record['eps_actual'] !== '/') $withEpsActual++;
    if (!empty($record['revenue_actual']) && $record['revenue_actual'] !== '/') $withRevenueActual++;
    if (!empty($record['eps_estimate']) && $record['eps_estimate'] !== '/') $withEpsEstimate++;
    if (!empty($record['revenue_estimate']) && $record['revenue_estimate'] !== '/') $withRevenueEstimate++;
}

echo "📈 Data Summary:\n";
echo "  Total records: " . count($data['data']) . "\n";
echo "  With EPS Actual: $withEpsActual\n";
echo "  With EPS Estimate: $withEpsEstimate\n";
echo "  With Revenue Actual: $withRevenueActual\n";
echo "  With Revenue Estimate: $withRevenueEstimate\n";
?>
