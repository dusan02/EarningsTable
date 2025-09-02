<?php
/**
 * Unified Configuration File
 * Jednotný konfiguračný súbor s backward compatibility
 */

require_once __DIR__ . '/AppConfig.php';

// Initialize configuration
$config = AppConfig::getInstance();

// Set timezone
date_default_timezone_set($config->get('app.timezone'));

// Define constants for backward compatibility
if (!defined('DB_HOST')) define('DB_HOST', $config->get('database.host'));
if (!defined('DB_NAME')) define('DB_NAME', $config->get('database.name'));
if (!defined('DB_USER')) define('DB_USER', $config->get('database.user'));
if (!defined('DB_PASS')) define('DB_PASS', $config->get('database.pass'));
if (!defined('DB_CHARSET')) define('DB_CHARSET', $config->get('database.charset'));

if (!defined('FINNHUB_API_KEY')) define('FINNHUB_API_KEY', $config->get('api.finnhub.key'));
if (!defined('FINNHUB_BASE_URL')) define('FINNHUB_BASE_URL', $config->get('api.finnhub.base_url'));
if (!defined('POLYGON_API_KEY')) define('POLYGON_API_KEY', $config->get('api.polygon.key'));
if (!defined('POLYGON_BASE_URL')) define('POLYGON_BASE_URL', $config->get('api.polygon.base_url'));
if (!defined('BENZINGA_API_KEY')) define('BENZINGA_API_KEY', $config->get('api.benzinga.key'));
if (!defined('BENZINGA_BASE_URL')) define('BENZINGA_BASE_URL', $config->get('api.benzinga.base_url'));

if (!defined('TIMEZONE')) define('TIMEZONE', $config->get('app.timezone'));
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', $config->isDebug());
if (!defined('LOG_LEVEL')) define('LOG_LEVEL', $config->get('logging.level'));

if (!defined('API_RATE_LIMIT_DELAY')) define('API_RATE_LIMIT_DELAY', $config->get('rate_limiting.delay'));
if (!defined('MAX_CONCURRENT_REQUESTS')) define('MAX_CONCURRENT_REQUESTS', $config->get('rate_limiting.max_concurrent'));

// Environment-specific constants
if (!defined('APP_TIMEZONE')) define('APP_TIMEZONE', $config->get('app.timezone'));
if (!defined('APP_DEBUG')) define('APP_DEBUG', $config->isDebug());
if (!defined('APP_ENV')) define('APP_ENV', $config->getEnvironment());
if (!defined('BASE_URL')) define('BASE_URL', $config->get('app.base_url'));

// Error reporting
if ($config->isDebug()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database connection
try {
    require_once __DIR__ . '/connection_pool.php';
    $pdo = DatabaseConnection::getConnection();
} catch (PDOException $e) {
    logDatabaseError('connection', '', [], $e->getMessage(), [
        'host' => DB_HOST,
        'database' => DB_NAME
    ]);
    
    if ($config->isDebug()) {
        throw $e;
    } else {
        displayError("Database connection failed");
        exit(1);
    }
}

// Backward compatibility logging function
if (!function_exists('logMessage')) {
    function logMessage($level, $message) {
        if (DEBUG_MODE || $level === 'ERROR') {
            $timestamp = date('Y-m-d H:i:s');
            $logFile = __DIR__ . '/../logs/app.log';
            
            // Create logs directory if it doesn't exist
            if (!is_dir(dirname($logFile))) {
                mkdir(dirname($logFile), 0755, true);
            }
            
            file_put_contents($logFile, "[$timestamp] [$level] $message" . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
}

// Development helpers (if in development mode)
if ($config->isDevelopment()) {
    if (!function_exists('dev_log')) {
        function dev_log($message) {
            logError("[DEV] " . $message);
        }
    }
    
    if (!function_exists('dev_dump')) {
        function dev_dump($var) {
            echo "<pre>";
            var_dump($var);
            echo "</pre>";
        }
    }
}

// Configuration validation
if ($config->isDebug()) {
    // Log configuration summary
    logMessage('INFO', 'Configuration loaded: ' . $config->getEnvironment() . ' environment');
    logMessage('INFO', 'Database: ' . DB_NAME . '@' . DB_HOST);
    logMessage('INFO', 'Debug mode: ' . ($config->isDebug() ? 'enabled' : 'disabled'));
}
?>
