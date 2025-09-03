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
     * Rotuje konkrétny log súbor
     */
    private function rotateFile($logFile) {
        $baseName = basename($logFile);
        $extension = pathinfo($baseName, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($baseName, PATHINFO_FILENAME);
        
        // Presun existujúce rotované súbory
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $this->logDir . '/' . $nameWithoutExt . '.' . $i . '.' . $extension;
            $newFile = $this->logDir . '/' . $nameWithoutExt . '.' . ($i + 1) . '.' . $extension;
            
            if (file_exists($oldFile)) {
                if ($i == $this->maxFiles - 1) {
                    unlink($oldFile); // Vymaž najstarší
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Premenuj aktuálny log súbor
        $rotatedFile = $this->logDir . '/' . $nameWithoutExt . '.1.' . $extension;
        rename($logFile, $rotatedFile);
        
        // Vytvor nový prázdny log súbor
        touch($logFile);
        chmod($logFile, 0644);
        
        // Loguj rotáciu
        $this->logger->info("Log file rotated: {$baseName}", [
            'original_size' => filesize($rotatedFile),
            'max_size' => $this->maxFileSize
        ], 'maintenance');
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
