<?php
error_reporting(-1);
ini_set('display_errors', '1');

echo "[DEBUG] Script start\n";
echo "[DEBUG] Testing basic PHP\n";

// Test 1: Basic PHP
echo "[DEBUG] PHP version: " . PHP_VERSION . "\n";
echo "[DEBUG] PDO available: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n";

// Test 2: Try to connect without database
echo "[DEBUG] Testing connection without database...\n";
try {
    $dsn = 'mysql:host=127.0.0.1;port=3306;charset=utf8mb4';
    $opt = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, 'root', '', $opt);
    echo "[DEBUG] Connection without database OK\n";
    
    // Test 3: Show databases
    echo "[DEBUG] Getting databases...\n";
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "[DEBUG] Databases: " . implode(", ", $databases) . "\n";
    
    // Test 4: Check if earnings_db exists
    if (in_array('earnings_db', $databases)) {
        echo "[DEBUG] earnings_db exists\n";
        
        // Test 5: Try to connect to earnings_db
        echo "[DEBUG] Testing connection to earnings_db...\n";
        $dsn2 = 'mysql:host=127.0.0.1;port=3306;dbname=earnings_db;charset=utf8mb4';
        $pdo2 = new PDO($dsn2, 'root', '', $opt);
        echo "[DEBUG] Connection to earnings_db OK\n";
        
        // Test 6: Show tables
        echo "[DEBUG] Getting tables...\n";
        $tables = $pdo2->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "[DEBUG] Tables: " . implode(", ", $tables) . "\n";
        
    } else {
        echo "[DEBUG] earnings_db does not exist\n";
    }
    
} catch (Throwable $e) {
    echo "[DEBUG] ERROR: " . $e->getMessage() . "\n";
    echo "[DEBUG] Error type: " . get_class($e) . "\n";
}

echo "[DEBUG] Script completed\n";
?>
