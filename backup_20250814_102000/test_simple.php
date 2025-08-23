<?php
require_once __DIR__ . '/config.php';

echo "Testing simple API query...\n\n";

try {
    // Get current date in US Eastern Time
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "Date: $date\n\n";
    
    // Test basic query from EarningsTickersToday
    $stmt = $pdo->prepare("
        SELECT ticker, report_time, eps_actual, revenue_actual
        FROM EarningsTickersToday 
        WHERE report_date = ?
        ORDER BY ticker ASC
        LIMIT 10
    ");
    
    $stmt->execute([$date]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "EarningsTickersToday data (first 10):\n";
    echo str_repeat("-", 50) . "\n";
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
    
    echo "\nTotal count: " . count($data) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
