<?php
require_once '../config.php';
require_once __DIR__ . '/../common/error_handler.php';

header('Content-Type: text/plain; charset=utf-8');

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
    logDatabaseError('clear_and_run', 'DELETE FROM tables', [], $e->getMessage(), [
        'tables' => $tables
    ]);
    displayError("Error clearing tables: " . $e->getMessage());
    exit(1);
}

echo "🚀 Starting all cron jobs...\n\n";

// Run all cron jobs in sequence (shell execution to support args)
$cronJobs = [
            'cron/2_clear_old_data.php --force',
    'cron/fetch_finnhub_earnings_today_tickers.php',
    'cron/fetch_missing_tickers_yahoo.php',
    'cron/fetch_market_data_complete.php'
];

$phpPath = 'D:\\xampp\\php\\php.exe';

foreach ($cronJobs as $job) {
    echo "Running: $job\n";
    echo "----------------------------------------\n";
    
    $cmd = $phpPath . ' ' . $job . ' 2>&1';
    $output = shell_exec($cmd);
    echo $output;
    
    echo "✅ $job completed\n";
    echo "----------------------------------------\n\n";
    
    // Flush output
    if (function_exists('ob_flush')) { ob_flush(); }
    flush();
}

echo "🎉 All cron jobs completed!\n";
echo "📊 Check the database for fresh data.\n";
echo "🔗 <a href='earnings-table-simple.html'>Go to Earnings Table</a>\n";
?>
