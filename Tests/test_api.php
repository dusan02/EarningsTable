<?php
/**
 * Simple test file to check API endpoint
 */

// Include the API file directly
require_once __DIR__ . '/../config/config.php';

echo "Testing API endpoint...\n\n";

try {
    // Get current date in US Eastern Time
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "Date: $date\n\n";
    
    // Check EarningsTickersToday
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $earningsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "EarningsTickersToday count: $earningsCount\n";
    
    // Check TodayEarningsMovements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM TodayEarningsMovements");
    $movementsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "TodayEarningsMovements count: $movementsCount\n\n";
    
    // Test the main query
    $stmt = $pdo->prepare("
        SELECT 
            e.ticker,
            COALESCE(m.company_name, e.ticker) as company_name,
            COALESCE(m.current_price, 0) as current_price,
            COALESCE(m.previous_close, 0) as previous_close,
            COALESCE(m.market_cap, 0) as market_cap,
            COALESCE(m.size, 'Unknown') as size,
            COALESCE(m.price_change_percent, 0) as price_change_percent,
            e.report_time
        FROM EarningsTickersToday e
        LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
        WHERE e.report_date = ?
        ORDER BY m.market_cap DESC, e.ticker ASC
        LIMIT 10
    ");
    
    $stmt->execute([$date]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Sample data (first 10 records):\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-8s %-20s %-10s %-10s %-15s %-8s %-15s %-8s\n", 
           "Ticker", "Company", "Current", "Previous", "Market Cap", "Size", "Change %", "Time");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($data as $row) {
        $currentPrice = $row['current_price'] > 0 ? '$' . number_format($row['current_price'], 2) : 'N/A';
        $previousClose = $row['previous_close'] > 0 ? '$' . number_format($row['previous_close'], 2) : 'N/A';
        $marketCap = $row['market_cap'] > 0 ? '$' . number_format($row['market_cap'] / 1e9, 2) . 'B' : 'N/A';
        $changePercent = $row['price_change_percent'] != 0 ? number_format($row['price_change_percent'], 2) . '%' : 'N/A';
        
        printf("%-8s %-20s %-10s %-10s %-15s %-8s %-15s %-8s\n",
               $row['ticker'],
               substr($row['company_name'], 0, 18),
               $currentPrice,
               $previousClose,
               $marketCap,
               $row['size'],
               $changePercent,
               $row['report_time']);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
