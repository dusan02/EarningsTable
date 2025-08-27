<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing database connection...\n";

try {
    // Test basic connection without database
    $pdo = new PDO("mysql:host=127.0.0.1;charset=utf8mb4", "root", "root", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ MySQL connection successful\n";
    
    // Show databases
    echo "\nDatabases:\n";
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($databases as $db) {
        echo " - $db\n";
    }
    
    // Check if earnings_db exists
    if (in_array('earnings_db', $databases)) {
        echo "\n✓ earnings_db exists\n";
        
        // Connect to earnings_db
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=earnings_db;charset=utf8mb4", "root", "root", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "✓ Connected to earnings_db\n";
        
        // Show tables
        echo "\nTables in earnings_db:\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo " - $table\n";
        }
        
        // Check specific tables
        if (in_array('EarningsTickersToday', $tables)) {
            echo "\n✓ EarningsTickersToday table exists\n";
            $count = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();
            echo "Records: $count\n";
        } else {
            echo "\n✗ EarningsTickersToday table does not exist\n";
        }
        
        if (in_array('TodayEarningsMovements', $tables)) {
            echo "\n✓ TodayEarningsMovements table exists\n";
            $count = $pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
            echo "Records: $count\n";
        } else {
            echo "\n✗ TodayEarningsMovements table does not exist\n";
        }
        
    } else {
        echo "\n✗ earnings_db does not exist\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ General error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
