<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...\n";

try {
    // Test basic connection
    $pdo = new PDO("mysql:host=localhost;dbname=earnings_db;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ Database connection successful\n";
    
    // Test if tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database: " . implode(", ", $tables) . "\n";
    
    // Test EarningsTickersToday table
    if (in_array('EarningsTickersToday', $tables)) {
        echo "✓ EarningsTickersToday table exists\n";
        $count = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();
        echo "Records in EarningsTickersToday: $count\n";
    } else {
        echo "✗ EarningsTickersToday table does not exist\n";
    }
    
    // Test TodayEarningsMovements table
    if (in_array('TodayEarningsMovements', $tables)) {
        echo "✓ TodayEarningsMovements table exists\n";
        $count = $pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
        echo "Records in TodayEarningsMovements: $count\n";
    } else {
        echo "✗ TodayEarningsMovements table does not exist\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ General error: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
?>
