<?php
/**
 * Security Logger & Monitoring
 * Bezpečnostné logovanie a sledovanie aktivít
 */

class SecurityLogger {
    private static $instance = null;
    private $logDir;
    private $alertThresholds;
    
    /**
     * Konštruktor
     */
    private function __construct() {
        $this->logDir = __DIR__ . '/../logs/security/';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        $this->alertThresholds = [
            'failed_login' => 5,      // 5 neúspešných prihlásení za 15 minút
            'api_abuse' => 100,       // 100 API volaní za minútu
            'sql_injection' => 1,     // 1 pokus o SQL injection
            'xss_attempt' => 1,       // 1 pokus o XSS
            'file_access' => 50,      // 50 prístupov k súborom za minútu
            'error_rate' => 20        // 20 chýb za 5 minút
        ];
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
     * Loguje bezpečnostné udalosti
     */
    public function log($level, $event, $data = [], $ip = null) {
        $ip = $ip ?? $this->getClientIP();
        $timestamp = date('Y-m-d H:i:s');
        $sessionId = session_id() ?? 'no-session';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'event' => $event,
            'ip' => $ip,
            'session_id' => $sessionId,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];
        
        // Zapíš do príslušného log súboru
        $logFile = $this->logDir . $level . '.log';
        $logLine = json_encode($logEntry) . "\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Skontroluj alerting
        $this->checkAlerting($level, $event, $ip);
        
        return true;
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
    public function logSqlInjection($sql, $params, $ip = null) {
        return $this->log('critical', 'sql_injection_attempt', [
            'sql' => $sql,
            'params' => $params,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $ip);
    }
    
    /**
     * Loguje XSS pokus
     */
    public function logXssAttempt($input, $ip = null) {
        return $this->log('critical', 'xss_attempt', [
            'input' => $input,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $ip);
    }
    
    /**
     * Loguje prístup k súborom
     */
    public function logFileAccess($file, $access, $ip = null) {
        $level = ($access === 'read') ? 'info' : 'warning';
        
        return $this->log($level, 'file_access', [
            'file' => $file,
            'access' => $access
        ], $ip);
    }
    
    /**
     * Loguje databázové zmeny
     */
    public function logDatabaseChange($operation, $table, $affectedRows, $ip = null) {
        return $this->log('info', 'database_change', [
            'operation' => $operation,
            'table' => $table,
            'affected_rows' => $affectedRows
        ], $ip);
    }
    
    /**
     * Loguje chyby aplikácie
     */
    public function logError($error, $context = [], $ip = null) {
        return $this->log('error', 'application_error', [
            'error' => $error,
            'context' => $context
        ], $ip);
    }
    
    /**
     * Skontroluje alerting
     */
    private function checkAlerting($level, $event, $ip) {
        $threshold = $this->alertThresholds[$event] ?? null;
        
        if ($threshold) {
            $count = $this->getEventCount($event, $ip, 15); // 15 minút
            
            if ($count >= $threshold) {
                $this->sendAlert($event, $ip, $count, $threshold);
            }
        }
    }
    
    /**
     * Získa počet udalostí za časové obdobie
     */
    private function getEventCount($event, $ip, $minutes) {
        $logFile = $this->logDir . '*.log';
        $files = glob($logFile);
        $count = 0;
        $cutoff = time() - ($minutes * 60);
        
        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            
            foreach ($lines as $line) {
                $data = json_decode($line, true);
                
                if ($data && 
                    $data['event'] === $event && 
                    $data['ip'] === $ip && 
                    strtotime($data['timestamp']) >= $cutoff) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
    
    /**
     * Pošle alert
     */
    private function sendAlert($event, $ip, $count, $threshold) {
        $alertData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $ip,
            'count' => $count,
            'threshold' => $threshold,
            'message' => "Alert: $event from IP $ip ($count/$threshold)"
        ];
        
        $alertFile = $this->logDir . 'alerts.log';
        $alertLine = json_encode($alertData) . "\n";
        
        file_put_contents($alertFile, $alertLine, FILE_APPEND | LOCK_EX);
        
        // Tu by sa mohol poslať email alebo SMS
        $this->sendEmailAlert($alertData);
    }
    
    /**
     * Pošle email alert
     */
    private function sendEmailAlert($alertData) {
        // Implementácia email alertu
        // Pre teraz len logujeme
        error_log("SECURITY ALERT: " . $alertData['message']);
    }
    
    /**
     * Získa IP adresu klienta
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Získa štatistiky logov
     */
    public function getStats($hours = 24) {
        $stats = [
            'total_events' => 0,
            'by_level' => [],
            'by_event' => [],
            'by_ip' => [],
            'alerts' => 0
        ];
        
        $cutoff = time() - ($hours * 3600);
        
        // Prejdi všetky log súbory
        $logFiles = glob($this->logDir . '*.log');
        
        foreach ($logFiles as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            
            foreach ($lines as $line) {
                $data = json_decode($line, true);
                
                if ($data && strtotime($data['timestamp']) >= $cutoff) {
                    $stats['total_events']++;
                    
                    // Štatistiky podľa úrovne
                    $level = $data['level'];
                    $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
                    
                    // Štatistiky podľa udalosti
                    $event = $data['event'];
                    $stats['by_event'][$event] = ($stats['by_event'][$event] ?? 0) + 1;
                    
                    // Štatistiky podľa IP
                    $ip = $data['ip'];
                    $stats['by_ip'][$ip] = ($stats['by_ip'][$ip] ?? 0) + 1;
                }
            }
        }
        
        // Počet alertov
        $alertFile = $this->logDir . 'alerts.log';
        if (file_exists($alertFile)) {
            $alertLines = file($alertFile, FILE_IGNORE_NEW_LINES);
            $stats['alerts'] = count($alertLines);
        }
        
        return $stats;
    }
    
    /**
     * Vyčisti staré logy
     */
    public function cleanup($days = 30) {
        $cutoff = time() - ($days * 24 * 3600);
        $logFiles = glob($this->logDir . '*.log');
        
        foreach ($logFiles as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES);
            $newLines = [];
            
            foreach ($lines as $line) {
                $data = json_decode($line, true);
                
                if ($data && strtotime($data['timestamp']) >= $cutoff) {
                    $newLines[] = $line;
                }
            }
            
            file_put_contents($file, implode("\n", $newLines) . "\n");
        }
    }
}

/**
 * Audit Trail
 * Sledovanie zmien v systéme
 */
class AuditTrail {
    private $logger;
    
    public function __construct() {
        $this->logger = SecurityLogger::getInstance();
    }
    
    /**
     * Loguje zmenu dát
     */
    public function logDataChange($table, $operation, $oldData, $newData, $userId = null) {
        return $this->logger->log('info', 'data_change', [
            'table' => $table,
            'operation' => $operation,
            'old_data' => $oldData,
            'new_data' => $newData,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Loguje zmenu konfigurácie
     */
    public function logConfigChange($config, $oldValue, $newValue, $userId = null) {
        return $this->logger->log('info', 'config_change', [
            'config' => $config,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Loguje prístup k citlivým dátam
     */
    public function logSensitiveDataAccess($dataType, $recordId, $userId = null) {
        return $this->logger->log('warning', 'sensitive_data_access', [
            'data_type' => $dataType,
            'record_id' => $recordId,
            'user_id' => $userId
        ]);
    }
}

/**
 * Performance Monitor
 * Sledovanie výkonu aplikácie
 */
class PerformanceMonitor {
    private $logger;
    private $startTime;
    
    public function __construct() {
        $this->logger = SecurityLogger::getInstance();
        $this->startTime = microtime(true);
    }
    
    /**
     * Začne meranie
     */
    public function start() {
        $this->startTime = microtime(true);
    }
    
    /**
     * Ukončí meranie a loguje
     */
    public function end($operation, $ip = null) {
        $duration = (microtime(true) - $this->startTime) * 1000; // v ms
        
        if ($duration > 1000) { // Ak trvá viac ako 1 sekundu
            $this->logger->log('warning', 'slow_operation', [
                'operation' => $operation,
                'duration' => round($duration, 2)
            ], $ip);
        }
        
        return $duration;
    }
    
    /**
     * Loguje pamäťové využitie
     */
    public function logMemoryUsage($operation) {
        $memory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        if ($memory > 50 * 1024 * 1024) { // Viac ako 50MB
            $this->logger->log('warning', 'high_memory_usage', [
                'operation' => $operation,
                'memory' => $memory,
                'peak_memory' => $peakMemory
            ]);
        }
    }
}
?>
