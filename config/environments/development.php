<?php
/**
 * Development Environment Configuration
 * Konfigurácia pre vývojové prostredie
 */

// Override environment
putenv('APP_ENV=development');
putenv('APP_DEBUG=true');
putenv('LOG_LEVEL=DEBUG');
putenv('TIMEZONE=Europe/Prague');

// Development-specific settings
putenv('DB_NAME=earnings_table_dev');
putenv('APP_URL=http://localhost:8080');

// Memory and execution time for development
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

// Development helpers
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

// Development-specific error handling
if (!function_exists('dev_error_handler')) {
    function dev_error_handler($errno, $errstr, $errfile, $errline) {
        $message = "[DEV ERROR] $errstr in $errfile on line $errline";
        logError($message);
        
        if (ini_get('display_errors')) {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px;'>";
            echo "<strong>Development Error:</strong> $errstr<br>";
            echo "<strong>File:</strong> $errfile<br>";
            echo "<strong>Line:</strong> $errline";
            echo "</div>";
        }
        
        return true;
    }
}

set_error_handler('dev_error_handler');
?>
