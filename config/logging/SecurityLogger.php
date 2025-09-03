<?php
/**
 * 🔒 SECURITY LOGGER - Main Class
 * Hlavná trieda pre bezpečnostné logovanie
 */

require_once __DIR__ . '/AlertManager.php';
require_once __DIR__ . '/LogRotator.php';
require_once __DIR__ . '/ThreatDetector.php';

class SecurityLogger {
    private static $instance = null;
    private $logDir;
    private $alertManager;
    private $logRotator;
    private $threatDetector;
    private $stats;
    
    /**
     * Konštruktor
     */
    private function __construct() {
        $this->logDir = __DIR__ . '/../../logs/security/';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        $this->alertManager = new AlertManager();
        $this->logRotator = new LogRotator($this->logDir);
        $this->threatDetector = new ThreatDetector();
        $this->stats = ['security_events' => 0, 'event_types' => []];
    }
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Loguje security udalosť
     */
    public function logSecurityEvent($event, $data = []) {
        // Update local stats
        $this->stats['security_events']++;
        $this->stats['event_types'][$event] = ($this->stats['event_types'][$event] ?? 0) + 1;
        
        // Check if IP should be blocked
        if (isset($data['ip_address'])) {
            $this->checkIpBlocking($data['ip_address'], $event);
        }
    }
    
    /**
     * Loguje prihlásenie
     */
    public function logLogin($username, $success, $ip = null) {
        $event = $success ? 'login_success' : 'login_failed';
        $level = $success ? 'info' : 'warning';
        
        return $this->log($level, $event, [
            'username' => $username,
            'success' => $success
        ], $ip);
    }
    
    /**
     * Loguje API volania
     */
    public function logApiCall($endpoint, $method, $responseCode, $duration, $ip = null) {
        $level = ($responseCode >= 400) ? 'warning' : 'info';
        
        return $this->log($level, 'api_call', [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $responseCode,
            'duration' => $duration
        ], $ip);
    }
    
    /**
     * Loguje SQL injection pokus
     */
    public function logSqlInjection($query, $ip = null) {
        return $this->log('error', 'sql_injection', [
            'query' => $query,
            'pattern' => $this->detectSqlPattern($query)
        ], $ip);
    }
    
    /**
     * Loguje XSS pokus
     */
    public function logXssAttempt($input, $ip = null) {
        return $this->log('error', 'xss_attempt', [
            'input' => $input,
            'pattern' => $this->detectXssPattern($input)
        ], $ip);
    }
    
    /**
     * Loguje prístup k súborom
     */
    public function logFileAccess($file, $action, $ip = null) {
        return $this->log('info', 'file_access', [
            'file' => $file,
            'action' => $action
        ], $ip);
    }
    
    /**
     * Loguje chyby
     */
    public function logError($error, $context = [], $ip = null) {
        return $this->log('error', 'application_error', [
            'error' => $error,
            'context' => $context
        ], $ip);
    }
    
    /**
     * Získanie IP adresy klienta
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Detekcia SQL injection pattern
     */
    private function detectSqlPattern($query) {
        $patterns = [
            'union' => '/union\s+select/i',
            'drop' => '/drop\s+table/i',
            'delete' => '/delete\s+from/i',
            'insert' => '/insert\s+into/i',
            'update' => '/update\s+.+\s+set/i'
        ];
        
        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $query)) {
                return $type;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Detekcia XSS pattern
     */
    private function detectXssPattern($input) {
        $patterns = [
            'script' => '/<script[^>]*>/i',
            'javascript' => '/javascript:/i',
            'onload' => '/onload\s*=/i',
            'onclick' => '/onclick\s*=/i'
        ];
        
        foreach ($patterns as $type => $pattern) {
            if (preg_match($pattern, $input)) {
                return $type;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Rotácia logov
     */
    public function rotateLogs() {
        return $this->logRotator->rotateLogs();
    }
    
    /**
     * Získanie štatistík
     */
    public function getStats() {
        return $this->threatDetector->getStats();
    }
}
?>
