<?php
// Simple database test
$dbHost = 'localhost';
$dbName = 'earnings_db';
$dbUser = 'root';
$dbPass = '';

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "🔍 Checking Finnhub Data Counts\n";
    echo "===============================\n\n";
    
    // Check EPS Actual counts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM todayearningsmovements WHERE eps_actual IS NOT NULL AND eps_actual != '' AND eps_actual != 'N/A'");
    $result = $stmt->fetch();
    echo "✅ EPS Actual records: " . $result['count'] . "\n";
    
    // Check Revenue Actual counts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM todayearningsmovements WHERE revenue_actual IS NOT NULL AND revenue_actual != '' AND revenue_actual != 'N/A'");
    $result = $stmt->fetch();
    echo "✅ Revenue Actual records: " . $result['count'] . "\n";
    
    // Check total records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM todayearningsmovements");
    $result = $stmt->fetch();
    echo "📊 Total records: " . $result['count'] . "\n";
    
    // Show sample of actual data
    echo "\n📋 Sample of records with actual data:\n";
    $stmt = $pdo->query("
        SELECT ticker, eps_actual, revenue_actual, updated_at 
        FROM todayearningsmovements 
        WHERE (eps_actual IS NOT NULL AND eps_actual != '' AND eps_actual != 'N/A')
           OR (revenue_actual IS NOT NULL AND revenue_actual != '' AND revenue_actual != 'N/A')
        LIMIT 10
    ");
    
    $records = $stmt->fetchAll();
    foreach ($records as $record) {
        echo "   " . $record['ticker'] . ": ";
        if ($record['eps_actual'] && $record['eps_actual'] != 'N/A') {
            echo "EPS=" . $record['eps_actual'];
        }
        if ($record['revenue_actual'] && $record['revenue_actual'] != 'N/A') {
            if ($record['eps_actual'] && $record['eps_actual'] != 'N/A') echo ", ";
            echo "Revenue=$" . number_format($record['revenue_actual'], 0);
        }
        echo " (updated: " . $record['updated_at'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
