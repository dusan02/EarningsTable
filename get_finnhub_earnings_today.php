<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== FINNHUB EARNINGS CALENDAR - TODAY ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n";
echo "Time: " . $usDate->format('H:i:s') . " NY\n\n";

try {
    $finnhub = new Finnhub();
    
    echo "=== FETCHING FINNHUB EARNINGS ===\n";
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $earningsData = $response['earningsCalendar'] ?? [];
    
    if (empty($earningsData)) {
        echo "❌ No earnings found for today\n";
        exit(1);
    }
    
    echo "✅ Success: Found " . count($earningsData) . " earnings reports today\n\n";
    
    // Extract just the tickers
    $tickers = [];
    foreach ($earningsData as $earning) {
        $symbol = $earning['symbol'] ?? '';
        if (!empty($symbol)) {
            $tickers[] = $symbol;
        }
    }
    
    echo "=== TICKERS FROM FINNHUB ===\n";
    echo "Total tickers: " . count($tickers) . "\n\n";
    
    // Display tickers in a clean format
    echo "Tickers:\n";
    foreach ($tickers as $index => $ticker) {
        echo ($index + 1) . ". {$ticker}\n";
    }
    
    // Also show as comma-separated list
    echo "\nComma-separated list:\n";
    echo implode(', ', $tickers) . "\n";
    
    // Show detailed info for first 10 tickers
    echo "\n=== DETAILED INFO (First 10) ===\n";
    $count = 0;
    foreach ($earningsData as $earning) {
        if ($count >= 10) break;
        
        echo ($count + 1) . ". {$earning['symbol']} - {$earning['companyName']}\n";
        echo "   EPS Estimate: " . ($earning['epsEstimate'] ?? 'N/A') . "\n";
        echo "   Revenue Estimate: " . ($earning['revenueEstimate'] ?? 'N/A') . "\n";
        echo "   Report Time: " . ($earning['time'] ?? 'N/A') . "\n";
        echo "   Market Cap: " . ($earning['marketCap'] ?? 'N/A') . "\n";
        echo "\n";
        
        $count++;
    }
    
    // JSON output
    echo "=== JSON OUTPUT ===\n";
    echo json_encode([
        'date' => $date,
        'total_count' => count($tickers),
        'tickers' => $tickers,
        'detailed_data' => array_slice($earningsData, 0, 10)
    ], JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== COMPLETED ===\n";
?>
