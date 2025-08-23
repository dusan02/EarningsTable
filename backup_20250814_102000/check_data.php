<?php
require_once __DIR__ . '/config.php';

echo "Checking TodayEarningsMovements table...\n\n";

try {
    // Check count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM TodayEarningsMovements");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total records in TodayEarningsMovements: $count\n\n";
    
    if ($count > 0) {
        // Show sample data
        $stmt = $pdo->query("
            SELECT ticker, company_name, current_price, previous_close, market_cap, size, price_change_percent, updated_at 
            FROM TodayEarningsMovements 
            ORDER BY market_cap DESC 
            LIMIT 5
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample data:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-8s %-20s %-10s %-10s %-15s %-8s %-15s %-20s\n", 
               "Ticker", "Company", "Current", "Previous", "Market Cap", "Size", "Change %", "Updated");
        echo str_repeat("-", 80) . "\n";
        
        foreach ($data as $row) {
            $currentPrice = $row['current_price'] > 0 ? '$' . number_format($row['current_price'], 2) : 'N/A';
            $previousClose = $row['previous_close'] > 0 ? '$' . number_format($row['previous_close'], 2) : 'N/A';
            $marketCap = $row['market_cap'] > 0 ? '$' . number_format($row['market_cap'] / 1e9, 2) . 'B' : 'N/A';
            $changePercent = $row['price_change_percent'] != 0 ? number_format($row['price_change_percent'], 2) . '%' : 'N/A';
            
            printf("%-8s %-20s %-10s %-10s %-15s %-8s %-15s %-20s\n",
                   $row['ticker'],
                   substr($row['company_name'], 0, 18),
                   $currentPrice,
                   $previousClose,
                   $marketCap,
                   $row['size'],
                   $changePercent,
                   $row['updated_at']);
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
