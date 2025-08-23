<?php
require_once '../config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🧹 Clearing all tables...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Clear all tables
    $tables = [
        'earnings_tickers',
        'company_names', 
        'earnings_eps_revenues',
        'current_prices_mcaps',
        'shares_outstanding'
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
    '../cron/update_company_names.php',
    '../cron/cache_shares_outstanding.php', 
    '../cron/update_earnings_eps_revenues.php',
    '../cron/current_prices_mcaps_updates.php',
    '../cron/fetch_earnings_tickers.php'
];

foreach ($cronJobs as $job) {
    echo "Running: $job\n";
    echo "----------------------------------------\n";
    
    ob_start();
    include $job;
    $output = ob_get_clean();
    
    echo $output;
    echo "✅ $job completed\n";
    echo "----------------------------------------\n\n";
    
    // Flush output
    ob_flush();
    flush();
}

echo "🎉 All cron jobs completed!\n";
echo "📊 Check the database for fresh data.\n";
echo "🔗 <a href='earnings-table-simple.html'>Go to Earnings Table</a>\n";
?>
