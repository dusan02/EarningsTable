<?php
/**
 * GitHub Actions Test File
 * Simple test that doesn't depend on config files
 */

echo "=== GITHUB ACTIONS TEST ===\n\n";

// Test 1: Basic PHP functionality
echo "✓ PHP version: " . PHP_VERSION . "\n";
echo "✓ PHP extensions loaded: " . implode(', ', get_loaded_extensions()) . "\n\n";

// Test 2: Database connection
echo "=== DATABASE CONNECTION TEST ===\n";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;charset=utf8mb4", "root", "root", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ MySQL connection successful\n";
    
    // Show databases
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Available databases: " . implode(', ', $databases) . "\n";
    
    // Check if earnings_db exists
    if (in_array('earnings_db', $databases)) {
        echo "✓ earnings_db exists\n";
        
        // Connect to earnings_db
        $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=earnings_db;charset=utf8mb4", "root", "root", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "✓ Connected to earnings_db\n";
        
        // Show tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "✓ Tables in earnings_db: " . implode(', ', $tables) . "\n";
        
        // Check specific tables
        if (in_array('EarningsTickersToday', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();
            echo "✓ EarningsTickersToday: $count records\n";
        } else {
            echo "✗ EarningsTickersToday table does not exist\n";
        }
        
        if (in_array('TodayEarningsMovements', $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
            echo "✓ TodayEarningsMovements: $count records\n";
        } else {
            echo "✗ TodayEarningsMovements table does not exist\n";
        }
        
    } else {
        echo "✗ earnings_db does not exist\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ General error: " . $e->getMessage() . "\n";
}

echo "\n=== FILE SYSTEM TEST ===\n";
$testFiles = [
    'config/config.php',
    'sql/setup_all_tables.sql',
    'Tests/README.md',
    'composer.json',
    'README.md'
];

foreach ($testFiles as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

echo "\n=== TEST COMPLETED ===\n";
echo "✅ GitHub Actions test finished successfully!\n";
?>
