<?php
/**
 * Production Environment Configuration
 * Konfigurácia pre produkčné prostredie
 */

// Override environment
putenv('APP_ENV=production');
putenv('APP_DEBUG=false');
putenv('LOG_LEVEL=WARNING');

// Production-specific settings
putenv('DB_NAME=earnings_table');
putenv('APP_URL=https://your-domain.mydreams.cz');

// Security settings for production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Performance settings
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 60);

// Production-specific error handling
if (!function_exists('prod_error_handler')) {
    function prod_error_handler($errno, $errstr, $errfile, $errline) {
        $message = "[PROD ERROR] $errstr in $errfile on line $errline";
        logError($message);
        
        // Don't display errors in production
        return true;
    }
}

set_error_handler('prod_error_handler');

// Security headers
if (!function_exists('set_security_headers')) {
    function set_security_headers() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

set_security_headers();
?>
