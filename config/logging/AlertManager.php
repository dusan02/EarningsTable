<?php
/**
 * 🚨 ALERT MANAGER
 * Správa bezpečnostných alertov
 */

class AlertManager {
    private $alertThresholds;
    private $alertCache;
    
    public function __construct() {
        $this->alertThresholds = [
            'failed_login' => 5,      // 5 neúspešných prihlásení za 15 minút
            'api_abuse' => 100,       // 100 API volaní za minútu
            'sql_injection' => 1,     // 1 pokus o SQL injection
            'xss_attempt' => 1,       // 1 pokus o XSS
            'file_access' => 50,      // 50 prístupov k súborom za minútu
            'error_rate' => 20        // 20 chýb za 5 minút
        ];
        
        $this->alertCache = [];
    }
    
    /**
     * Skontrolovanie alertov
     */
    public function checkAlerting($level, $event, $ip) {
        $key = $event . '_' . $ip;
        $currentTime = time();
        
        if (!isset($this->alertCache[$key])) {
            $this->alertCache[$key] = [];
        }
        
        // Pridanie udalosti
        $this->alertCache[$key][] = $currentTime;
        
        // Vyčistenie starých udalostí
        $this->alertCache[$key] = array_filter(
            $this->alertCache[$key],
            function($time) use ($currentTime) {
                return ($currentTime - $time) < 900; // 15 minút
            }
        );
        
        // Skontrolovanie threshold
        if (isset($this->alertThresholds[$event])) {
            $threshold = $this->alertThresholds[$event];
            $count = count($this->alertCache[$key]);
            
            if ($count >= $threshold) {
                $this->triggerAlert($event, $ip, $count);
            }
        }
    }
    
    /**
     * Spustenie alertu
     */
    private function triggerAlert($event, $ip, $count) {
        $alertData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $ip,
            'count' => $count,
            'threshold' => $this->alertThresholds[$event] ?? 0
        ];
        
        $alertFile = __DIR__ . '/../../logs/security/alerts.log';
        $alertLine = json_encode($alertData) . "\n";
        
        file_put_contents($alertFile, $alertLine, FILE_APPEND | LOCK_EX);
        
        // Tu by sa mohol pridať email alert alebo iné notifikácie
        $this->sendNotification($alertData);
    }
    
    /**
     * Odoslanie notifikácie
     */
    private function sendNotification($alertData) {
        // Implementácia notifikácií (email, SMS, Slack, atď.)
        // Pre teraz len logujeme cez centralizovaný error handler
        logSecurityIssue("Security alert triggered: " . json_encode($alertData), $alertData);
    }
}
?>
