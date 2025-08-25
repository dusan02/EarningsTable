<?php
/**
 * Configuration Template for EarningsTable
 * 
 * Copy this file to config.php and update with your actual values
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'earnings_table');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');

// API Keys
define('FINNHUB_API_KEY', 'your_finnhub_api_key_here');
define('POLYGON_API_KEY', 'your_polygon_api_key_here');

// Application Settings
define('TIMEZONE', 'America/New_York');
define('DEBUG_MODE', false);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Rate Limiting
define('API_RATE_LIMIT_DELAY', 0.1); // seconds between API calls
define('MAX_CONCURRENT_REQUESTS', 10);

// Database Connection Pool
require_once __DIR__ . '/connection_pool.php';

// Použitie connection pool namiesto priameho pripojenia
try {
    $pdo = DatabaseConnection::getConnection();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Logging function
function logMessage($level, $message) {
    if (DEBUG_MODE || $level === 'ERROR') {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = __DIR__ . '/logs/app.log';
        
        // Create logs directory if it doesn't exist
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        
        file_put_contents($logFile, "[$timestamp] [$level] $message" . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
?>
