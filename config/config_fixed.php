<?php
/**
 * Configuration for EarningsTable
 */

// Load environment variables from .env file
require_once __DIR__ . '/config/env_loader.php';

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', EnvLoader::get('DB_HOST', 'localhost'));
if (!defined('DB_NAME')) define('DB_NAME', EnvLoader::get('DB_NAME', 'earnings_table'));
if (!defined('DB_USER')) define('DB_USER', EnvLoader::get('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', EnvLoader::get('DB_PASS', ''));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// API Keys - Direct setting
if (!defined('FINNHUB_API_KEY')) define('FINNHUB_API_KEY', EnvLoader::get('FINNHUB_API_KEY', 'your_finnhub_api_key_here'));
if (!defined('POLYGON_API_KEY')) define('POLYGON_API_KEY', 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX');

// Application Settings
if (!defined('TIMEZONE')) define('TIMEZONE', EnvLoader::get('TIMEZONE', 'America/New_York'));
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', EnvLoader::get('APP_DEBUG', 'true') === 'true');
if (!defined('LOG_LEVEL')) define('LOG_LEVEL', EnvLoader::get('LOG_LEVEL', 'INFO')); // DEBUG, INFO, WARNING, ERROR

// Rate Limiting
if (!defined('API_RATE_LIMIT_DELAY')) define('API_RATE_LIMIT_DELAY', 0.1); // seconds between API calls
if (!defined('MAX_CONCURRENT_REQUESTS')) define('MAX_CONCURRENT_REQUESTS', 10);

// Database Connection Pool
require_once __DIR__ . '/config/connection_pool.php';

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
