<?php
require_once 'config.php';

echo "=== ALPHA VANTAGE EARNINGS CALENDAR - TODAY ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n";
echo "Time: " . $usDate->format('H:i:s') . " NY\n\n";

// Alpha Vantage API key (from your existing code)
$apiKey = 'YFO8D5S1D0E4F80C';
$baseUrl = 'https://www.alphavantage.co/query';

// Alpha Vantage earnings calendar endpoint
$url = "{$baseUrl}?function=EARNINGS_CALENDAR&horizon=3month&apikey={$apiKey}";

echo "Fetching from: {$baseUrl}?function=EARNINGS_CALENDAR&horizon=3month&apikey=***\n\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 30,
        'header' => [
            'User-Agent: EarningsTable/1.0',
            'Accept: application/json'
        ]
    ]
]);

$startTime = microtime(true);
$response = file_get_contents($url, false, $context);
$endTime = microtime(true);

if ($response === false) {
    echo "❌ Failed to fetch data from Alpha Vantage\n";
    exit(1);
}

$timeToFirstByte = round(($endTime - $startTime) * 1000, 2);
echo "⏱️  Response time: {$timeToFirstByte}ms\n";

// Alpha Vantage returns CSV format for earnings calendar
$lines = explode("\n", $response);
$headers = str_getcsv($lines[0]);

echo "✅ Successfully fetched data from Alpha Vantage\n";
echo "Response size: " . strlen($response) . " bytes\n";
echo "Total lines: " . count($lines) . "\n\n";

// Parse CSV data
$earnings = [];
$todayEarnings = [];

for ($i = 1; $i < count($lines); $i++) {
    if (empty(trim($lines[$i]))) continue;
    
    $row = str_getcsv($lines[$i]);
    if (count($row) < count($headers)) continue;
    
    $earning = array_combine($headers, $row);
    
    // Check if this earnings is for today
    if (isset($earning['reportDate']) && $earning['reportDate'] === $date) {
        $todayEarnings[] = $earning;
    }
    
    $earnings[] = $earning;
}

echo "✅ Found " . count($earnings) . " total earnings in 3-month horizon\n";
echo "✅ Found " . count($todayEarnings) . " earnings for today ({$date})\n\n";

if (empty($todayEarnings)) {
    echo "❌ No earnings found for today\n";
    echo "Available dates in response:\n";
    $dates = [];
    foreach ($earnings as $earning) {
        $dates[] = $earning['reportDate'];
    }
    $uniqueDates = array_unique($dates);
    sort($uniqueDates);
    foreach (array_slice($uniqueDates, 0, 10) as $date) {
        echo "  {$date}\n";
    }
    exit(1);
}

// Extract tickers for today
$tickers = [];
foreach ($todayEarnings as $earning) {
    $symbol = $earning['symbol'] ?? '';
    if (!empty($symbol)) {
        $tickers[] = $symbol;
    }
}

echo "=== TICKERS FROM ALPHA VANTAGE (TODAY) ===\n";
echo "Total tickers: " . count($tickers) . "\n\n";

// Display tickers
echo "Tickers:\n";
foreach ($tickers as $index => $ticker) {
    echo ($index + 1) . ". {$ticker}\n";
}

// Comma-separated list
echo "\nComma-separated list:\n";
echo implode(', ', $tickers) . "\n";

// Show detailed info for first 10
echo "\n=== DETAILED INFO (First 10) ===\n";
$count = 0;
foreach ($todayEarnings as $earning) {
    if ($count >= 10) break;
    
    echo ($count + 1) . ". {$earning['symbol']} - {$earning['name']}\n";
    echo "   Report Date: " . $earning['reportDate'] . "\n";
    echo "   Time: " . ($earning['time'] ?? 'N/A') . "\n";
    echo "   EPS Estimate: " . ($earning['estimate'] ?? 'N/A') . "\n";
    echo "   Revenue Estimate: " . ($earning['revenueEstimate'] ?? 'N/A') . "\n";
    echo "   Currency: " . ($earning['currency'] ?? 'N/A') . "\n";
    echo "\n";
    
    $count++;
}

// JSON output
echo "=== JSON OUTPUT ===\n";
echo json_encode([
    'date' => $date,
    'total_count' => count($tickers),
    'tickers' => $tickers,
    'detailed_data' => array_slice($todayEarnings, 0, 10),
    'response_headers' => $headers
], JSON_PRETTY_PRINT) . "\n";

echo "\n=== COMPLETED ===\n";
?>
