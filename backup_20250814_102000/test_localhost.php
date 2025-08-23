<?php
error_reporting(-1);
ini_set('display_errors', '1');

echo "[DEBUG] Script start\n";

// Test with localhost
echo "[DEBUG] Testing connection with localhost...\n";
try {
    $dsn = 'mysql:host=localhost;port=3306;charset=utf8mb4';
    $opt = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, 'root', '', $opt);
    echo "[DEBUG] Connection with localhost OK\n";
    
    // Show databases
    echo "[DEBUG] Getting databases...\n";
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "[DEBUG] Databases: " . implode(", ", $databases) . "\n";
    
    if (in_array('earnings_db', $databases)) {
        echo "[DEBUG] earnings_db exists\n";
    } else {
        echo "[DEBUG] earnings_db does not exist - creating it\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS earnings_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "[DEBUG] earnings_db created\n";
    }
    
} catch (Throwable $e) {
    echo "[DEBUG] ERROR: " . $e->getMessage() . "\n";
    echo "[DEBUG] Error type: " . get_class($e) . "\n";
}

echo "[DEBUG] Script completed\n";
?>
