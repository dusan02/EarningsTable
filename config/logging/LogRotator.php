<?php
/**
 * 📄 LOG ROTATOR
 * Rotácia log súborov
 */

class LogRotator {
    private $logDir;
    private $maxSize = 10 * 1024 * 1024; // 10MB
    private $maxFiles = 5;
    
    public function __construct($logDir) {
        $this->logDir = $logDir;
    }
    
    /**
     * Rotácia logov
     */
    public function rotateLogs() {
        $logFiles = glob($this->logDir . '/*.log');
        
        foreach ($logFiles as $logFile) {
            if (filesize($logFile) > $this->maxSize) {
                $this->rotateFile($logFile);
            }
        }
    }
    
    /**
     * Rotácia konkrétneho súboru
     */
    private function rotateFile($logFile) {
        $baseName = basename($logFile, '.log');
        $dirName = dirname($logFile);
        
        // Posun existujúcich rotovaných súborov
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $dirName . '/' . $baseName . '.' . $i . '.log';
            $newFile = $dirName . '/' . $baseName . '.' . ($i + 1) . '.log';
            
            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }
        
        // Rotácia hlavného súboru
        $rotatedFile = $dirName . '/' . $baseName . '.1.log';
        rename($logFile, $rotatedFile);
        
        // Vytvorenie nového prázdneho súboru
        touch($logFile);
        chmod($logFile, 0644);
    }
    
    /**
     * Vyčistenie starých logov
     */
    public function cleanupOldLogs() {
        $logFiles = glob($this->logDir . '/*.log');
        $currentTime = time();
        $maxAge = 30 * 24 * 60 * 60; // 30 dní
        
        foreach ($logFiles as $logFile) {
            if (($currentTime - filemtime($logFile)) > $maxAge) {
                unlink($logFile);
            }
        }
    }
}
?>
