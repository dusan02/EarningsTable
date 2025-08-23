<?php
require_once 'config.php';
require_once 'utils/database.php';

try {
    // Use US Eastern Time to match the cron jobs
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "=== TODAY'S EARNINGS REPORT ===\n";
    echo "Date: " . $date . "\n\n";
    
    // Count total tickers for today
    $result = $pdo->query("SELECT COUNT(*) as count FROM EarningsTickersToday WHERE report_date = '$date'");
    $row = $result->fetch();
    $totalTickers = $row['count'];
    
    echo "📊 Total tickers reporting today: " . $totalTickers . "\n\n";
    
    // Count by report time
    $result = $pdo->query("
        SELECT report_time, COUNT(*) as count 
        FROM EarningsTickersToday 
        WHERE report_date = '$date' 
        GROUP BY report_time 
        ORDER BY report_time
    ");
    
    echo "📅 Breakdown by report time:\n";
    while ($row = $result->fetch()) {
        echo "   " . $row['report_time'] . ": " . $row['count'] . " tickers\n";
    }
    
    echo "\n📋 Sample tickers reporting today:\n";
    $result = $pdo->query("
        SELECT ticker, report_time 
        FROM EarningsTickersToday 
        WHERE report_date = '$date' 
        ORDER BY report_time, ticker 
        LIMIT 10
    ");
    
    while ($row = $result->fetch()) {
        echo "   " . $row['ticker'] . " (" . $row['report_time'] . ")\n";
    }
    
    if ($totalTickers > 10) {
        echo "   ... and " . ($totalTickers - 10) . " more\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
