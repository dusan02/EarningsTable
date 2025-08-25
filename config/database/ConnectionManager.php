<?php
/**
 * 🔗 CONNECTION MANAGER
 * Správa životného cyklu databázových pripojení
 */

require_once __DIR__ . '/ConnectionValidator.php';

class ConnectionManager {
    private $connections = [];
    private $maxConnections = 10;
    private $minConnections = 2;
    private $connectionTimeout = 300; // 5 minút
    private $lastUsed = [];
    private $dbConfig;
    private $lockFile;
    private $connectionValidator;
    
    public function __construct() {
        $this->dbConfig = [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'charset' => DB_CHARSET
        ];
        
        $this->lockFile = sys_get_temp_dir() . '/earnings_db_pool.lock';
        $this->connectionValidator = new ConnectionValidator();
    }
    
    /**
     * Inicializácia connection pool
     */
    public function initializePool() {
        // Vytvorenie minimálneho počtu pripojení
        for ($i = 0; $i < $this->minConnections; $i++) {
            $this->createConnection();
        }
    }
    
    /**
     * Vytvorenie nového pripojenia
     */
    private function createConnection() {
        try {
            $pdo = new PDO(
                "mysql:host=" . $this->dbConfig['host'] . 
                ";dbname=" . $this->dbConfig['name'] . 
                ";charset=" . $this->dbConfig['charset'],
                $this->dbConfig['user'],
                $this->dbConfig['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );
            
            // Nastavenie session premenných
            $pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
            $pdo->exec("SET SESSION time_zone = '+00:00'");
            
            $connectionId = uniqid('conn_', true);
            $this->connections[$connectionId] = $pdo;
            $this->lastUsed[$connectionId] = time();
            
            $this->log("Created new connection: $connectionId");
            return $connectionId;
            
        } catch (PDOException $e) {
            $this->log("Failed to create connection: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Získanie pripojenia z pool
     */
    public function getConnection() {
        $this->acquireLock();
        
        try {
            // Hľadanie voľného pripojenia
            foreach ($this->connections as $id => $pdo) {
                if ($this->connectionValidator->isConnectionAvailable($pdo, $id)) {
                    $this->lastUsed[$id] = time();
                    $this->log("Reusing connection: $id");
                    $this->releaseLock();
                    return $pdo;
                }
            }
            
            // Vytvorenie nového pripojenia ak je možné
            if (count($this->connections) < $this->maxConnections) {
                $connectionId = $this->createConnection();
                $this->lastUsed[$connectionId] = time();
                $this->log("Created new connection for request: $connectionId");
                $this->releaseLock();
                return $this->connections[$connectionId];
            }
            
            // Čakanie na voľné pripojenie
            $this->releaseLock();
            return $this->waitForConnection();
            
        } catch (Exception $e) {
            $this->releaseLock();
            throw $e;
        }
    }
    
    /**
     * Čakanie na voľné pripojenie
     */
    private function waitForConnection() {
        $maxWaitTime = 30; // 30 sekúnd
        $waitTime = 0;
        $waitInterval = 0.1; // 100ms
        
        while ($waitTime < $maxWaitTime) {
            usleep($waitInterval * 1000000);
            $waitTime += $waitInterval;
            
            $this->acquireLock();
            
            // Skontrolovanie dostupných pripojení
            foreach ($this->connections as $id => $pdo) {
                if ($this->connectionValidator->isConnectionAvailable($pdo, $id)) {
                    $this->lastUsed[$id] = time();
                    $this->log("Got connection after waiting: $id");
                    $this->releaseLock();
                    return $pdo;
                }
            }
            
            $this->releaseLock();
        }
        
        throw new Exception("Timeout waiting for available connection");
    }
    
    /**
     * Vrátenie pripojenia do pool
     */
    public function releaseConnection($pdo) {
        $this->acquireLock();
        
        // Nájdenie pripojenia v pool
        foreach ($this->connections as $id => $connection) {
            if ($connection === $pdo) {
                $this->lastUsed[$id] = time();
                $this->log("Released connection: $id");
                break;
            }
        }
        
        $this->releaseLock();
    }
    
    /**
     * Odstránenie pripojenia z pool
     */
    public function removeConnection($connectionId) {
        if (isset($this->connections[$connectionId])) {
            $this->connections[$connectionId] = null;
            unset($this->connections[$connectionId]);
            unset($this->lastUsed[$connectionId]);
            $this->log("Removed dead connection: $connectionId");
        }
    }
    
    /**
     * Získanie statistik pool
     */
    public function getPoolStats() {
        $this->acquireLock();
        
        try {
            $stats = [
                'total_connections' => count($this->connections),
                'max_connections' => $this->maxConnections,
                'min_connections' => $this->minConnections,
                'connection_timeout' => $this->connectionTimeout,
                'oldest_connection' => 0,
                'newest_connection' => 0
            ];
            
            if (!empty($this->lastUsed)) {
                $stats['oldest_connection'] = min($this->lastUsed);
                $stats['newest_connection'] = max($this->lastUsed);
            }
            
            return $stats;
            
        } finally {
            $this->releaseLock();
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
     * Zatvorenie všetkých pripojení
     */
    public function closeAllConnections() {
        $this->acquireLock();
        
        try {
            foreach ($this->connections as $id => $pdo) {
                $pdo = null;
            }
            
            $this->connections = [];
            $this->lastUsed = [];
            
            $this->log("Closed all connections");
            
        } finally {
            $this->releaseLock();
        }
    }
    
    /**
     * Logovanie
     */
    private function log($message, $level = 'INFO') {
        $logFile = __DIR__ . '/../../logs/connection_pool.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    // Gettery pre PoolCleaner
    public function getConnections() {
        return $this->connections;
    }
    
    public function getLastUsed() {
        return $this->lastUsed;
    }
    
    public function getConnectionTimeout() {
        return $this->connectionTimeout;
    }
    
    public function getMinConnections() {
        return $this->minConnections;
    }
}
?>
