<?php
/**
 * Development Configuration
 * Konfigurácia pre vývojové prostredie
 */

// Development settings
define('DEVELOPMENT_MODE', true);
define('DEBUG_MODE', true);
define('LOG_LEVEL', 'DEBUG');

// Database settings (development)
define('DB_HOST', 'localhost');
define('DB_NAME', 'earnings_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// API settings (development)
define('POLYGON_API_KEY', 'your_polygon_api_key_here');
define('FINNHUB_API_KEY', 'your_finnhub_api_key_here');

// Paths
define('PROJECT_ROOT', __DIR__ . '/../');
define('LOGS_PATH', PROJECT_ROOT . 'logs/');
define('STORAGE_PATH', PROJECT_ROOT . 'storage/');
define('TESTS_PATH', PROJECT_ROOT . 'Tests/');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . 'php_errors.log');

// Timezone
date_default_timezone_set('Europe/Prague');

// Memory and execution time
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Development helpers
function dev_log($message) {
    if (DEVELOPMENT_MODE) {
        error_log("[DEV] " . $message);
    }
}

function dev_dump($var) {
    if (DEVELOPMENT_MODE) {
        echo "<pre>";
        var_dump($var);
        echo "</pre>";
    }
}
?>
