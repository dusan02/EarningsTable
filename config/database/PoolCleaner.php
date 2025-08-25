<?php
/**
 * 🔗 POOL CLEANER
 * Cleanup proces pre staré databázové pripojenia
 */

class PoolCleaner {
    private $connectionManager;
    private $lockFile;
    
    public function __construct($connectionManager) {
        $this->connectionManager = $connectionManager;
        $this->lockFile = sys_get_temp_dir() . '/earnings_db_pool.lock';
    }
    
    /**
     * Cleanup proces pre staré pripojenia
     */
    public function startCleanupProcess() {
        // Spustenie cleanup procesu len raz
        if (file_exists($this->lockFile)) {
            $lockData = json_decode(file_get_contents($this->lockFile), true);
            if ($lockData && (time() - $lockData['last_cleanup']) < 60) {
                return; // Cleanup už beží
            }
        }
        
        $this->cleanupOldConnections();
    }
    
    /**
     * Vyčistenie starých pripojení
     */
    public function cleanupOldConnections() {
        $this->acquireLock();
        
        try {
            $currentTime = time();
            $connectionsToRemove = [];
            
            $connections = $this->connectionManager->getConnections();
            $lastUsed = $this->connectionManager->getLastUsed();
            $connectionTimeout = $this->connectionManager->getConnectionTimeout();
            $minConnections = $this->connectionManager->getMinConnections();
            
            foreach ($lastUsed as $id => $lastUsedTime) {
                if (($currentTime - $lastUsedTime) > $connectionTimeout) {
                    $connectionsToRemove[] = $id;
                }
            }
            
            // Zachovanie minimálneho počtu pripojení
            $currentCount = count($connections);
            $toRemove = count($connectionsToRemove);
            
            if (($currentCount - $toRemove) < $minConnections) {
                $connectionsToRemove = array_slice($connectionsToRemove, 0, $currentCount - $minConnections);
            }
            
            // Odstránenie starých pripojení
            foreach ($connectionsToRemove as $id) {
                $this->connectionManager->removeConnection($id);
            }
            
            // Aktualizácia lock súboru
            $lockData = [
                'last_cleanup' => time(),
                'active_connections' => count($this->connectionManager->getConnections()),
                'pid' => getmypid()
            ];
            file_put_contents($this->lockFile, json_encode($lockData));
            
            if (!empty($connectionsToRemove)) {
                $this->log("Cleaned up " . count($connectionsToRemove) . " old connections");
            }
            
        } finally {
            $this->releaseLock();
        }
    }
    
    /**
     * Vynútené vyčistenie všetkých pripojení
     */
    public function forceCleanup() {
        $this->acquireLock();
        
        try {
            $connections = $this->connectionManager->getConnections();
            $minConnections = $this->connectionManager->getMinConnections();
            
            // Zachovanie len minimálneho počtu pripojení
            $connectionsToKeep = array_slice(array_keys($connections), 0, $minConnections);
            $connectionsToRemove = array_diff(array_keys($connections), $connectionsToKeep);
            
            foreach ($connectionsToRemove as $id) {
                $this->connectionManager->removeConnection($id);
            }
            
            $this->log("Force cleaned up " . count($connectionsToRemove) . " connections");
            
        } finally {
            $this->releaseLock();
        }
    }
    
    /**
     * Cleanup na základe pamäti
     */
    public function cleanupByMemoryUsage() {
        $memoryLimit = 128 * 1024 * 1024; // 128MB
        $currentMemory = memory_get_usage(true);
        
        if ($currentMemory > $memoryLimit) {
            $this->log("Memory usage high ($currentMemory bytes), forcing cleanup");
            $this->forceCleanup();
        }
    }
    
    /**
     * Cleanup na základe času
     */
    public function cleanupByTime() {
        $currentHour = (int)date('H');
        
        // Cleanup v noci (2-4 AM)
        if ($currentHour >= 2 && $currentHour <= 4) {
            $this->log("Night time cleanup initiated");
            $this->forceCleanup();
        }
    }
    
    /**
     * Získanie lock súboru
     */
    private function acquireLock() {
        $lockDir = dirname($this->lockFile);
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        
        $lockHandle = fopen($this->lockFile, 'c+');
        if (!$lockHandle) {
            throw new Exception("Cannot create lock file");
        }
        
        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);
            throw new Exception("Cannot acquire lock");
        }
        
        return $lockHandle;
    }
    
    /**
     * Uvoľnenie lock súboru
     */
    private function releaseLock() {
        // Lock sa automaticky uvoľní pri ukončení skriptu
    }
    
    /**
     * Logovanie
     */
    private function log($message, $level = 'INFO') {
        $logFile = __DIR__ . '/../../logs/pool_cleaner.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>
