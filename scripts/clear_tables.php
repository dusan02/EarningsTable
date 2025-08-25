<?php
require_once 'config.php';

echo "🧹 Clearing all tables...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Clear all tables
    $tables = [
        'EarningsTickersToday',
        'TodayEarningsMovements'
    ];
    
    foreach ($tables as $table) {
        echo "Clearing table: $table\n";
        $stmt = $pdo->prepare("DELETE FROM $table");
        $stmt->execute();
        $count = $stmt->rowCount();
        echo "Deleted $count rows from $table\n";
    }
    
    echo "✅ All tables cleared successfully!\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error clearing tables: " . $e->getMessage() . "\n";
    exit(1);
}

echo "🚀 Starting all cron jobs...\n\n";

// Run all cron jobs in sequence
$cronJobs = [
    'cron/clear_old_data.php --force',
    'cron/fetch_finnhub_earnings_today_tickers.php',
    'cron/fetch_missing_tickers_yahoo.php',
    'cron/fetch_market_data_complete.php'
];

// Use full PHP path for reliability
$phpPath = 'D:\\xampp\\php\\php.exe';

foreach ($cronJobs as $job) {
    echo "Running: $job\n";
    echo "----------------------------------------\n";
    
    $cmd = $phpPath . ' ' . $job . ' 2>&1';
    passthru($cmd, $returnCode);
    echo "\n";
    echo $returnCode === 0 ? "✅ $job completed successfully\n" : "❌ $job failed with code: $returnCode\n";
    
    echo "----------------------------------------\n\n";
    
    // Wait a bit between jobs
    sleep(2);
}

echo "🎉 All cron jobs completed!\n";
echo "📊 Check the database for fresh data.\n";
?>
