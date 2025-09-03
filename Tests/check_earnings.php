<?php
require_once __DIR__ . '/test_config.php';

echo "Checking EarningsTickersToday table...\n\n";

try {
    // Check total count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM earningstickerstoday");
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total records in earningstickerstoday: $totalCount\n\n";
    
    if ($totalCount > 0) {
        // Check recent dates
        $stmt = $pdo->query("
            SELECT report_date, COUNT(*) as count 
            FROM earningstickerstoday 
            GROUP BY report_date 
            ORDER BY report_date DESC 
            LIMIT 5
        ");
        $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Recent dates with data:\n";
        foreach ($dates as $date) {
            echo "  {$date['report_date']}: {$date['count']} records\n";
        }
        
        // Show sample data from most recent date
        if (!empty($dates)) {
            $recentDate = $dates[0]['report_date'];
            echo "\nSample data from $recentDate:\n";
            echo str_repeat("-", 50) . "\n";
            
            $stmt = $pdo->prepare("
                SELECT ticker, report_time, eps_actual, revenue_actual
                FROM earningstickerstoday 
                WHERE report_date = ?
                ORDER BY ticker ASC
                LIMIT 10
            ");
            $stmt->execute([$recentDate]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            printf("%-8s %-8s %-10s %-15s\n", "Ticker", "Time", "EPS", "Revenue");
            echo str_repeat("-", 50) . "\n";
            
            foreach ($data as $row) {
                $eps = $row['eps_actual'] ? number_format($row['eps_actual'], 2) : 'N/A';
                $revenue = $row['revenue_actual'] ? number_format($row['revenue_actual'] / 1e6, 0) . 'M' : 'N/A';
                
                printf("%-8s %-8s %-10s %-15s\n",
                       $row['ticker'],
                       $row['report_time'],
                       $eps,
                       $revenue);
            }
        }
    } else {
        echo "No data found in EarningsTickersToday table.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
