<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

$finnhub = new Finnhub();
$date = date('Y-m-d');

echo "=== FINNHUB EARNINGS CALENDAR TEST ===\n";
echo "Date: " . $date . "\n";

$response = $finnhub->getEarningsCalendar('', $date, $date);
$earnings = $response['earningsCalendar'] ?? [];

echo "Tickers found: " . count($earnings) . "\n";

if (!empty($earnings)) {
    echo "First 10 tickers:\n";
    $first10 = array_slice($earnings, 0, 10);
    foreach ($first10 as $earning) {
        $symbol = $earning['symbol'] ?? 'N/A';
        $epsEst = $earning['epsEstimate'] ?? 'N/A';
        $revEst = $earning['revenueEstimate'] ?? 'N/A';
        echo "  {$symbol}: EPS={$epsEst}, Revenue={$revEst}\n";
    }
} else {
    echo "No earnings found for today\n";
}

echo "\n=== VERIFICATION ===\n";
echo "These tickers are the ones that report earnings TODAY\n";
echo "Polygon API should only be called for these specific tickers\n";
?>
