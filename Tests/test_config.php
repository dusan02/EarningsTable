<?php
/**
 * Simple test configuration for Tests folder
 * Avoids complex dependencies from main config.php
 */

// Basic database connection for tests
$dbHost = 'localhost';
$dbName = 'earnings_table';  // ✅ Opravené: používať earnings_table namiesto earnings_db
$dbUser = 'root';
$dbPass = '';

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Set timezone
    date_default_timezone_set('Europe/Prague');
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Basic API keys (if needed for tests)
define('POLYGON_API_KEY', 'your_polygon_api_key_here');
define('FINNHUB_API_KEY', 'your_finnhub_api_key_here');

echo "Test configuration loaded successfully\n";
?>
