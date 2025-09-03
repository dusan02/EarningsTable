<?php
/**
 * 📝 UNIFIED LOGGER
 * 
 * Konsoliduje všetky logging systémy do jednej triedy:
 * - Eliminuje duplicitný kód
 * - Centralizuje logging logiku
 * - Zjednodušuje údržbu
 * - Poskytuje konzistentné rozhranie
 */

class UnifiedLogger {
    
    // ========================================
    // CONFIGURATION - UNIFIED
    // ========================================
    
    private $config;
    private $logDir;
    private $maxFileSize;
    private $maxFiles;
    private $logLevel;
    
    public function __construct($config = []) {
        $this->config = array_merge([
            'log_dir' => __DIR__ . '/../logs',
            'max_file_size' => 10 * 1024 * 1024, // 10MB
            'max_files' => 5,
            'log_level' => 'INFO'
        ], $config);
        
        $this->logDir = $this->config['log_dir'];
        $this->maxFileSize = $this->config['max_file_size'];
        $this->maxFiles = $this->config['max_files'];
        $this->logLevel = $this->config['log_level'];
        
        // Ensure log directory exists
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    // ========================================
    // CORE LOGGING - UNIFIED
    // ========================================
    
    /**
     * Loguje udalosť s rôznymi úrovňami
     */
    public function log($level, $message, $context = [], $category = 'general') {
        $timestamp = date('Y-m-d H:i:s');
        $level = strtoupper($level);
        
        // Check log level
        if (!$this->shouldLog($level)) {
            return;
        }
        
        // Format message
        $formattedMessage = $this->formatMessage($timestamp, $level, $message, $context);
        
        // Write to appropriate log file
        $logFile = $this->getLogFile($category);
        $this->writeToFile($logFile, $formattedMessage);
        
        // Rotate logs if needed
        $this->rotateLogs($logFile);
        
        // Update statistics
        $this->updateStats($level, $category);
    }
    
    /**
     * Convenience methods for different log levels
     */
    public function emergency($message, $context = [], $category = 'general') {
        $this->log('EMERGENCY', $message, $context, $category);
    }
    
    public function alert($message, $context = [], $category = 'general') {
        $this->log('ALERT', $message, $context, $category);
    }
    
    public function critical($message, $context = [], $category = 'general') {
        $this->log('CRITICAL', $message, $context, $category);
    }
    
    public function error($message, $context = [], $category = 'general') {
        $this->log('ERROR', $message, $context, $category);
    }
    
    public function warning($message, $context = [], $category = 'general') {
        $this->log('WARNING', $message, $context, $category);
    }
    
    public function notice($message, $context = [], $category = 'general') {
        $this->log('NOTICE', $message, $context, $category);
    }
    
    public function info($message, $context = [], $category = 'general') {
        $this->log('INFO', $message, $context, $category);
    }
    
    public function debug($message, $context = [], $category = 'general') {
        $this->log('DEBUG', $message, $context, $category);
    }
    
    // ========================================
    // SECURITY LOGGING - UNIFIED
    // ========================================
    
    /**
     * Loguje security udalosti
     */
    public function logSecurityEvent($event, $data = [], $severity = 'WARNING') {
        $context = [
            'event_type' => $event,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'timestamp' => time(),
            'data' => $data
        ];
        
        $this->log($severity, "Security event: {$event}", $context, 'security');
        
        // Check for threat patterns
        $threatLevel = $this->assessThreatLevel($event, $data);
        if ($threatLevel > 0) {
            $this->triggerAlert($event, $data, $threatLevel);
        }
    }
    
    /**
     * Loguje SQL injection pokusy
     */
    public function logSqlInjection($query, $ip = null) {
        $context = [
            'query' => $query,
            'ip_address' => $ip ?? $this->getClientIp(),
            'pattern_detected' => 'sql_injection'
        ];
        
        $this->log('ALERT', 'SQL injection attempt detected', $context, 'security');
    }
    
    /**
     * Loguje XSS pokusy
     */
    public function logXssAttempt($input, $ip = null) {
        $context = [
            'input' => $input,
            'ip_address' => $ip ?? $this->getClientIp(),
            'pattern_detected' => 'xss_attempt'
        ];
        
        $this->log('ALERT', 'XSS attempt detected', $context, 'security');
    }
    
    /**
     * Loguje path traversal pokusy
     */
    public function logPathTraversal($path, $ip = null) {
        $context = [
            'path' => $path,
            'ip_address' => $ip ?? $this->getClientIp(),
            'pattern_detected' => 'path_traversal'
        ];
        
        $this->log('ALERT', 'Path traversal attempt detected', $context, 'security');
    }
    
    // ========================================
    // PERFORMANCE LOGGING - UNIFIED
    // ========================================
    
    /**
     * Loguje API performance metrics
     */
    public function logApiPerformance($api, $duration, $success, $endpoint = '') {
        $context = [
            'api' => $api,
            'duration' => $duration,
            'success' => $success,
            'endpoint' => $endpoint,
            'timestamp' => time()
        ];
        
        $level = $success ? 'INFO' : 'WARNING';
        $this->log($level, "API call: {$api}", $context, 'performance');
    }
    
    /**
     * Loguje database performance
     */
    public function logDatabasePerformance($query, $duration, $rows = 0) {
        $context = [
            'query' => $query,
            'duration' => $duration,
            'rows_affected' => $rows,
            'timestamp' => time()
        ];
        
        $level = $duration > 1.0 ? 'WARNING' : 'INFO';
        $this->log($level, "Database query executed", $context, 'performance');
    }
    
    // ========================================
    // ALERT MANAGEMENT - UNIFIED
    // ========================================
    
    /**
     * Spúšťa alert na základe threat level
     */
    private function triggerAlert($event, $data, $threatLevel) {
        $alertData = [
            'event' => $event,
            'threat_level' => $threatLevel,
            'data' => $data,
            'timestamp' => time(),
            'ip_address' => $this->getClientIp()
        ];
        
        // Log alert
        $this->log('ALERT', "Security alert triggered: {$event}", $alertData, 'alerts');
        
        // Store alert for monitoring
        $this->storeAlert($alertData);
        
        // Check if we need to escalate
        if ($threatLevel >= 8) {
            $this->escalateAlert($alertData);
        }
    }
    
    /**
     * Ukladá alert pre monitoring
     */
    private function storeAlert($alertData) {
        $alertFile = $this->logDir . '/alerts.json';
        $alerts = [];
        
        if (file_exists($alertFile)) {
            $alerts = json_decode(file_get_contents($alertFile), true) ?? [];
        }
        
        $alerts[] = $alertData;
        
        // Keep only recent alerts
        if (count($alerts) > 100) {
            $alerts = array_slice($alerts, -100);
        }
        
        file_put_contents($alertFile, json_encode($alerts, JSON_PRETTY_PRINT));
    }
    
    /**
     * Eskaluje alert (email, SMS, etc.)
     */
    private function escalateAlert($alertData) {
        // Implementation for alert escalation
        // Could send email, SMS, or trigger webhook
        $this->log('CRITICAL', 'Alert escalated', $alertData, 'alerts');
    }
    
    // ========================================
    // LOG ROTATION - UNIFIED
    // ========================================
    
    /**
     * Rotuje log súbory
     */
    private function rotateLogs($logFile) {
        if (!file_exists($logFile)) {
            return;
        }
        
        $fileSize = filesize($logFile);
        if ($fileSize < $this->maxFileSize) {
            return;
        }
        
        // Rotate existing files
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $logFile . '.' . $i;
            $newFile = $logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i == $this->maxFiles - 1) {
                    unlink($oldFile); // Delete oldest
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Rename current log file
        rename($logFile, $logFile . '.1');
        
        // Create new log file
        touch($logFile);
        chmod($logFile, 0644);
    }
    
    // ========================================
    // UTILITY METHODS - UNIFIED
    // ========================================
    
    /**
     * Kontroluje či sa má logovať na základe úrovne
     */
    private function shouldLog($level) {
        $levels = [
            'EMERGENCY' => 0,
            'ALERT' => 1,
            'CRITICAL' => 2,
            'ERROR' => 3,
            'WARNING' => 4,
            'NOTICE' => 5,
            'INFO' => 6,
            'DEBUG' => 7
        ];
        
        $currentLevel = $levels[$this->logLevel] ?? 6;
        $messageLevel = $levels[$level] ?? 6;
        
        return $messageLevel <= $currentLevel;
    }
    
    /**
     * Formátuje log správu
     */
    private function formatMessage($timestamp, $level, $message, $context) {
        $formatted = "[{$timestamp}] [{$level}] {$message}";
        
        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        
        return $formatted . PHP_EOL;
    }
    
    /**
     * Získa log súbor pre kategóriu
     */
    private function getLogFile($category) {
        $filename = $category . '.log';
        return $this->logDir . '/' . $filename;
    }
    
    /**
     * Zapíše do súboru
     */
    private function writeToFile($file, $content) {
        file_put_contents($file, $content, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Získa IP adresu klienta
     */
    private function getClientIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Vyhodnocuje threat level
     */
    private function assessThreatLevel($event, $data) {
        $baseLevel = 5; // Base threat level
        
        // Increase based on event type
        switch ($event) {
            case 'sql_injection':
                $baseLevel += 3;
                break;
            case 'xss_attempt':
                $baseLevel += 2;
                break;
            case 'path_traversal':
                $baseLevel += 2;
                break;
        }
        
        // Increase based on frequency from same IP
        $ip = $this->getClientIp();
        $recentEvents = $this->getRecentEventsFromIp($ip, 300); // 5 minutes
        $baseLevel += min(count($recentEvents), 3);
        
        return min($baseLevel, 10); // Max threat level 10
    }
    
    /**
     * Získa recent udalosti z IP
     */
    private function getRecentEventsFromIp($ip, $seconds) {
        // Simple implementation - could be enhanced with database
        $cutoff = time() - $seconds;
        $events = [];
        
        // Check security log for recent events from this IP
        $securityLog = $this->logDir . '/security.log';
        if (file_exists($securityLog)) {
            $lines = file($securityLog, FILE_IGNORE_NEW_LINES);
            foreach ($lines as $line) {
                if (strpos($line, $ip) !== false && preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $timestamp = strtotime($matches[1]);
                    if ($timestamp >= $cutoff) {
                        $events[] = $line;
                    }
                }
            }
        }
        
        return $events;
    }
    
    /**
     * Aktualizuje štatistiky
     */
    private function updateStats($level, $category) {
        $statsFile = $this->logDir . '/stats.json';
        $stats = [];
        
        if (file_exists($statsFile)) {
            $stats = json_decode(file_get_contents($statsFile), true) ?? [];
        }
        
        // Initialize if not exists
        if (!isset($stats['total_events'])) {
            $stats = [
                'total_events' => 0,
                'events_by_level' => [],
                'events_by_category' => [],
                'last_updated' => time()
            ];
        }
        
        // Update counters
        $stats['total_events']++;
        $stats['events_by_level'][$level] = ($stats['events_by_level'][$level] ?? 0) + 1;
        $stats['events_by_category'][$category] = ($stats['events_by_category'][$category] ?? 0) + 1;
        $stats['last_updated'] = time();
        
        // Save stats
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
    }
    
    /**
     * Získa štatistiky
     */
    public function getStats() {
        $statsFile = $this->logDir . '/stats.json';
        
        if (!file_exists($statsFile)) {
            return [
                'total_events' => 0,
                'events_by_level' => [],
                'events_by_category' => [],
                'last_updated' => null
            ];
        }
        
        return json_decode(file_get_contents($statsFile), true) ?? [];
    }
    
    /**
     * Vyčistí staré logy
     */
    public function cleanupOldLogs($days = 30) {
        $cutoff = time() - ($days * 24 * 60 * 60);
        $files = glob($this->logDir . '/*.log.*');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $this->info("Deleted old log file: {$file}", [], 'maintenance');
            }
        }
    }
}
?>
