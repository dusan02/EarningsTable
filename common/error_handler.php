<?php
/**
 * 🛠️ ERROR HANDLER - Centralized error handling for the application
 * Provides consistent error logging and handling across all components
 */

/**
 * Centralized error handler class
 */
class ErrorHandler {
    private static $logFile = null;
    private static $initialized = false;
    private static $cliMode = false;
    
    /**
     * Initialize error handler
     */
    public static function init($logFile = null) {
        if (self::$initialized) return;
        
        self::$logFile = $logFile ?: __DIR__ . '/../logs/application_errors.log';
        self::$cliMode = php_sapi_name() === 'cli';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Set error handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        
        self::$initialized = true;
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        $errorType = self::getErrorType($errno);
        $message = "[{$errorType}] {$errstr} in {$errfile} on line {$errline}";
        
        self::logError($message, [
            'type' => $errorType,
            'file' => $errfile,
            'line' => $errline,
            'errno' => $errno
        ]);
        
        // Don't execute PHP internal error handler for non-fatal errors
        if ($errno !== E_ERROR && $errno !== E_PARSE && $errno !== E_CORE_ERROR && 
            $errno !== E_COMPILE_ERROR && $errno !== E_USER_ERROR) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Handle exceptions
     */
    public static function handleException($exception) {
        $message = "[EXCEPTION] " . $exception->getMessage();
        $message .= " in " . $exception->getFile() . " on line " . $exception->getLine();
        
        self::logError($message, [
            'type' => 'EXCEPTION',
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'class' => get_class($exception)
        ]);
        
        // Display error in CLI mode
        if (self::$cliMode) {
            self::displayCliError($exception->getMessage());
        }
    }
    
    /**
     * Handle fatal errors
     */
    public static function handleFatalError() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = "[FATAL ERROR] {$error['message']} in {$error['file']} on line {$error['line']}";
            
            self::logError($message, [
                'type' => 'FATAL_ERROR',
                'file' => $error['file'],
                'line' => $error['line'],
                'errno' => $error['type']
            ]);
            
            // Display error in CLI mode
            if (self::$cliMode) {
                self::displayCliError($error['message'], true);
            }
        }
    }
    
    /**
     * Log error message
     */
    public static function logError($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        
        if (!empty($context)) {
            $logEntry .= " | Context: " . json_encode($context);
        }
        
        $logEntry .= "\n";
        
        // Write to log file
        if (self::$logFile) {
            file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
        
        // Also log to PHP error log for system monitoring
        error_log($message);
    }
    
    /**
     * Log API errors
     */
    public static function logApiError($api, $url, $response, $context = []) {
        $message = "[API ERROR] {$api} API call failed";
        
        $context['api'] = $api;
        $context['url'] = $url;
        $context['response'] = $response;
        
        self::logError($message, $context);
    }
    
    /**
     * Log database errors
     */
    public static function logDatabaseError($operation, $sql, $params, $error, $context = []) {
        $message = "[DATABASE ERROR] {$operation} failed: {$error}";
        
        $context['operation'] = $operation;
        $context['sql'] = $sql;
        $context['params'] = $params;
        $context['error'] = $error;
        
        self::logError($message, $context);
    }
    
    /**
     * Log security issues
     */
    public static function logSecurityIssue($issue, $context = []) {
        $message = "[SECURITY] {$issue}";
        
        $context['security_issue'] = $issue;
        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        self::logError($message, $context);
    }
    
    /**
     * Log cron job errors
     */
    public static function logCronError($cronName, $error, $context = []) {
        $message = "[CRON ERROR] {$cronName} failed: {$error}";
        
        $context['cron_name'] = $cronName;
        $context['error'] = $error;
        
        self::logError($message, $context);
    }
    
    /**
     * Log configuration errors
     */
    public static function logConfigError($config, $error, $context = []) {
        $message = "[CONFIG ERROR] {$config} configuration error: {$error}";
        
        $context['config'] = $config;
        $context['error'] = $error;
        
        self::logError($message, $context);
    }
    
    /**
     * Display user-friendly error message
     */
    public static function displayError($message, $isFatal = false) {
        if (self::$cliMode) {
            self::displayCliError($message, $isFatal);
        } else {
            // For web requests, you might want to redirect to an error page
            // or display a user-friendly message
            http_response_code(500);
            echo "<h1>Application Error</h1>";
            echo "<p>An error occurred. Please try again later.</p>";
        }
    }
    
    /**
     * Display CLI error with proper formatting
     */
    public static function displayCliError($message, $isFatal = false) {
        $prefix = $isFatal ? "❌ FATAL ERROR: " : "❌ ERROR: ";
        echo $prefix . $message . "\n";
    }
    
    /**
     * Display CLI success message
     */
    public static function displayCliSuccess($message) {
        echo "✅ " . $message . "\n";
    }
    
    /**
     * Display CLI warning message
     */
    public static function displayCliWarning($message) {
        echo "⚠️  " . $message . "\n";
    }
    
    /**
     * Display CLI info message
     */
    public static function displayCliInfo($message) {
        echo "ℹ️  " . $message . "\n";
    }
    
    /**
     * Get error type string from error number
     */
    private static function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR: return 'E_ERROR';
            case E_WARNING: return 'E_WARNING';
            case E_PARSE: return 'E_PARSE';
            case E_NOTICE: return 'E_NOTICE';
            case E_CORE_ERROR: return 'E_CORE_ERROR';
            case E_CORE_WARNING: return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: return 'E_COMPILE_WARNING';
            case E_USER_ERROR: return 'E_USER_ERROR';
            case E_USER_WARNING: return 'E_USER_WARNING';
            case E_USER_NOTICE: return 'E_USER_NOTICE';
            case E_STRICT: return 'E_STRICT';
            case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: return 'E_DEPRECATED';
            case E_USER_DEPRECATED: return 'E_USER_DEPRECATED';
            default: return 'UNKNOWN';
        }
    }
}

/**
 * Convenience functions for quick error logging
 */

/**
 * Log a simple error message
 */
function logError($message, $context = []) {
    ErrorHandler::logError($message, $context);
}

/**
 * Log an API error
 */
function logApiError($api, $url, $response, $context = []) {
    ErrorHandler::logApiError($api, $url, $response, $context);
}

/**
 * Log a database error
 */
function logDatabaseError($operation, $sql, $params, $error, $context = []) {
    ErrorHandler::logDatabaseError($operation, $sql, $params, $error, $context);
}

/**
 * Log a security issue
 */
function logSecurityIssue($issue, $context = []) {
    ErrorHandler::logSecurityIssue($issue, $context);
}

/**
 * Log a cron job error
 */
function logCronError($cronName, $error, $context = []) {
    ErrorHandler::logCronError($cronName, $error, $context);
}

/**
 * Log a configuration error
 */
function logConfigError($config, $error, $context = []) {
    ErrorHandler::logConfigError($config, $error, $context);
}

/**
 * Display user-friendly error
 */
function displayError($message, $isFatal = false) {
    ErrorHandler::displayError($message, $isFatal);
}

/**
 * Display CLI success message
 */
function displaySuccess($message) {
    ErrorHandler::displayCliSuccess($message);
}

/**
 * Display CLI warning message
 */
function displayWarning($message) {
    ErrorHandler::displayCliWarning($message);
}

/**
 * Display CLI info message
 */
function displayInfo($message) {
    ErrorHandler::displayCliInfo($message);
}

// Initialize error handler when this file is included
ErrorHandler::init();
