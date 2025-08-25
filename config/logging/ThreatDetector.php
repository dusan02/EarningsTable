<?php
/**
 * 🛡️ THREAT DETECTOR
 * Detekcia hrozieb a analýza bezpečnostných udalostí
 */

class ThreatDetector {
    private $stats;
    private $threatPatterns;
    
    public function __construct() {
        $this->stats = [
            'total_events' => 0,
            'threats_detected' => 0,
            'suspicious_ips' => [],
            'event_types' => []
        ];
        
        $this->threatPatterns = [
            'sql_injection' => [
                'union select', 'drop table', 'delete from', 'insert into', 'update set'
            ],
            'xss_attempt' => [
                '<script', 'javascript:', 'onload=', 'onclick='
            ],
            'path_traversal' => [
                '../', '..\\', '/etc/', 'c:\\'
            ]
        ];
    }
    
    /**
     * Analýza udalosti
     */
    public function analyzeEvent($event, $data, $ip) {
        $this->stats['total_events']++;
        
        if (!isset($this->stats['event_types'][$event])) {
            $this->stats['event_types'][$event] = 0;
        }
        $this->stats['event_types'][$event]++;
        
        // Detekcia hrozieb
        if ($this->isThreat($event, $data)) {
            $this->stats['threats_detected']++;
            $this->logThreat($event, $data, $ip);
        }
        
        // Sledovanie podozrivej IP
        if ($this->isSuspiciousIP($ip, $event)) {
            $this->stats['suspicious_ips'][$ip] = [
                'count' => ($this->stats['suspicious_ips'][$ip]['count'] ?? 0) + 1,
                'last_seen' => time(),
                'events' => array_merge(
                    $this->stats['suspicious_ips'][$ip]['events'] ?? [],
                    [$event]
                )
            ];
        }
    }
    
    /**
     * Kontrola či je udalosť hrozbou
     */
    private function isThreat($event, $data) {
        // Kontrola podľa typu udalosti
        if (in_array($event, ['sql_injection', 'xss_attempt'])) {
            return true;
        }
        
        // Kontrola dát pre vzory hrozieb
        foreach ($data as $value) {
            if (is_string($value)) {
                foreach ($this->threatPatterns as $threatType => $patterns) {
                    foreach ($patterns as $pattern) {
                        if (stripos($value, $pattern) !== false) {
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Kontrola či je IP podozrivá
     */
    private function isSuspiciousIP($ip, $event) {
        // IP s veľkým počtom chýb
        if ($event === 'error' || $event === 'warning') {
            return true;
        }
        
        // IP s pokusmi o útok
        if (in_array($event, ['sql_injection', 'xss_attempt'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Logovanie hrozby
     */
    private function logThreat($event, $data, $ip) {
        $threatData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $ip,
            'data' => $data,
            'severity' => $this->getThreatSeverity($event)
        ];
        
        $threatFile = __DIR__ . '/../../logs/security/threats.log';
        $threatLine = json_encode($threatData) . "\n";
        
        file_put_contents($threatFile, $threatLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Získanie závažnosti hrozby
     */
    private function getThreatSeverity($event) {
        $severityMap = [
            'sql_injection' => 'HIGH',
            'xss_attempt' => 'HIGH',
            'path_traversal' => 'HIGH',
            'failed_login' => 'MEDIUM',
            'api_abuse' => 'MEDIUM',
            'file_access' => 'LOW'
        ];
        
        return $severityMap[$event] ?? 'LOW';
    }
    
    /**
     * Získanie štatistík
     */
    public function getStats() {
        return $this->stats;
    }
    
    /**
     * Vyčistenie starých štatistík
     */
    public function cleanupStats() {
        $currentTime = time();
        $maxAge = 24 * 60 * 60; // 24 hodín
        
        foreach ($this->stats['suspicious_ips'] as $ip => $data) {
            if (($currentTime - $data['last_seen']) > $maxAge) {
                unset($this->stats['suspicious_ips'][$ip]);
            }
        }
    }
}
?>
