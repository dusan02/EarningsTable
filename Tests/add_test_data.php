<?php
/**
 * Add Test Data Script
 * Pridá testovacie earnings tickers pre demonštráciu cron jobov
 */

require_once __DIR__ . '/config.php';

echo "🚀 ADDING TEST EARNINGS DATA\n";
echo "============================\n\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n\n";
    
    // Test earnings tickers
    $testTickers = [
        ['ticker' => 'AAPL', 'report_time' => 'AMC', 'eps_estimate' => 1.25, 'revenue_estimate' => 85000000000],
        ['ticker' => 'MSFT', 'report_time' => 'AMC', 'eps_estimate' => 2.45, 'revenue_estimate' => 55000000000],
        ['ticker' => 'GOOGL', 'report_time' => 'AMC', 'eps_estimate' => 1.35, 'revenue_estimate' => 75000000000],
        ['ticker' => 'AMZN', 'report_time' => 'AMC', 'eps_estimate' => 0.85, 'revenue_estimate' => 140000000000],
        ['ticker' => 'TSLA', 'report_time' => 'AMC', 'eps_estimate' => 0.75, 'revenue_estimate' => 25000000000]
    ];
    
    echo "=== ADDING TEST TICKERS ===\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO EarningsTickersToday (
            report_date, ticker, report_time, eps_estimate, revenue_estimate
        ) VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            eps_estimate = VALUES(eps_estimate),
            revenue_estimate = VALUES(revenue_estimate)
    ");
    
    $added = 0;
    foreach ($testTickers as $ticker) {
        $stmt->execute([
            $date,
            $ticker['ticker'],
            $ticker['report_time'],
            $ticker['eps_estimate'],
            $ticker['revenue_estimate']
        ]);
        echo "✅ Added {$ticker['ticker']} ({$ticker['report_time']})\n";
        $added++;
    }
    
    echo "\n=== VERIFICATION ===\n";
    
    // Count total earnings tickers
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $count = $stmt->fetchColumn();
    
    echo "📊 Total earnings tickers for today: {$count}\n";
    
    // Show sample data
    $stmt = $pdo->prepare("SELECT * FROM EarningsTickersToday WHERE report_date = ? LIMIT 3");
    $stmt->execute([$date]);
    $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Sample data:\n";
    foreach ($sample as $row) {
        echo "- {$row['ticker']}: {$row['report_time']} (EPS est: {$row['eps_estimate']})\n";
    }
    
    echo "\n✅ Test data added successfully!\n";
    echo "Now you can run the cron jobs in sequence.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
