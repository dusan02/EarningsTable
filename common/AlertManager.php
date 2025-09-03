<?php
/**
 * 🚨 ALERT MANAGER
 * Správa bezpečnostných alertov
 */

require_once __DIR__ . '/../common/UnifiedLogger.php';

class AlertManager {
    private $logger;
    private $alerts;
    private $thresholds;
    
    public function __construct() {
        $this->logger = new UnifiedLogger();
        $this->alerts = [];
        $this->thresholds = [
            'sql_injection' => 3,
            'xss_attempt' => 5,
            'path_traversal' => 2,
            'brute_force' => 10
        ];
    }
    
    /**
     * Skontrolovanie alertov
     */
    public function checkAlerting($level, $event, $ip) {
        $key = $event . '_' . $ip;
        $currentTime = time();
        
        if (!isset($this->alerts[$key])) {
            $this->alerts[$key] = [];
        }
        
        // Pridanie udalosti
        $this->alerts[$key][] = $currentTime;
        
        // Vyčistenie starých udalostí
        $this->alerts[$key] = array_filter(
            $this->alerts[$key],
            function($time) use ($currentTime) {
                return ($currentTime - $time) < 900; // 15 minút
            }
        );
        
        // Skontrolovanie threshold
        if (isset($this->thresholds[$event])) {
            $threshold = $this->thresholds[$event];
            $count = count($this->alerts[$key]);
            
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
            'threshold' => $this->thresholds[$event] ?? 0
        ];
        
        $this->logger->logSecurityIssue("Security alert triggered: " . json_encode($alertData), $alertData);
    }
    
    /**
     * Odoslanie notifikácie
     */
    private function sendNotification($alertData) {
        // Implementácia notifikácií (email, SMS, Slack, atď.)
        // Pre teraz len logujeme cez centralizovaný error handler
        // Tu by sa mohol pridať email alert alebo iné notifikácie
    }
}
?>
