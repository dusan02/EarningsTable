<?php
require_once __DIR__ . '/config.php';

echo "Checking for tickers with valid prices...\n\n";

try {
    // Check for tickers with non-zero current prices
    $stmt = $pdo->query("
        SELECT ticker, company_name, current_price, previous_close, market_cap, price_change_percent, size
        FROM TodayEarningsMovements 
        WHERE current_price > 0 AND previous_close > 0
        ORDER BY market_cap DESC
        LIMIT 20
    ");
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Tickers with valid prices: " . count($data) . "\n\n";
    
    if (count($data) > 0) {
        echo "Sample data:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-8s %-25s %-10s %-10s %-15s %-15s %-8s\n", 
               "Ticker", "Company", "Current", "Previous", "Market Cap", "Change %", "Size");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($data as $row) {
            $currentPrice = '$' . number_format($row['current_price'], 2);
            $previousClose = '$' . number_format($row['previous_close'], 2);
            $marketCap = $row['market_cap'] > 0 ? '$' . number_format($row['market_cap'] / 1e9, 2) . 'B' : 'N/A';
            $changePercent = number_format($row['price_change_percent'], 2) . '%';
            
            printf("%-8s %-25s %-10s %-10s %-15s %-15s %-8s\n",
                   $row['ticker'],
                   substr($row['company_name'], 0, 23),
                   $currentPrice,
                   $previousClose,
                   $marketCap,
                   $changePercent,
                   $row['size']);
        }
    } else {
        echo "No tickers found with valid prices.\n";
        
        // Check what we have
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN current_price > 0 THEN 1 ELSE 0 END) as with_current_price,
                SUM(CASE WHEN previous_close > 0 THEN 1 ELSE 0 END) as with_previous_close,
                SUM(CASE WHEN market_cap > 0 THEN 1 ELSE 0 END) as with_market_cap
            FROM TodayEarningsMovements
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nStatistics:\n";
        echo "  Total records: {$stats['total']}\n";
        echo "  With current price > 0: {$stats['with_current_price']}\n";
        echo "  With previous close > 0: {$stats['with_previous_close']}\n";
        echo "  With market cap > 0: {$stats['with_market_cap']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
