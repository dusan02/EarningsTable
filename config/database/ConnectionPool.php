<?php
/**
 * 🔗 CONNECTION POOL - Main Class
 * Hlavná trieda pre connection pooling systém
 */

require_once __DIR__ . '/ConnectionManager.php';
require_once __DIR__ . '/PoolCleaner.php';
require_once __DIR__ . '/ConnectionValidator.php';

class ConnectionPool {
    private static $instance = null;
    private $connectionManager;
    private $poolCleaner;
    private $connectionValidator;
    private $lockFile;
    
    private function __construct() {
        $this->connectionManager = new ConnectionManager();
        $this->poolCleaner = new PoolCleaner($this->connectionManager);
        $this->connectionValidator = new ConnectionValidator();
        $this->lockFile = sys_get_temp_dir() . '/earnings_db_pool.lock';
        
        $this->initializePool();
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
     * Inicializácia connection pool
     */
    private function initializePool() {
        $this->connectionManager->initializePool();
        $this->poolCleaner->startCleanupProcess();
    }
    
    /**
     * Získanie pripojenia z pool
     */
    public function getConnection() {
        return $this->connectionManager->getConnection();
    }
    
    /**
     * Vrátenie pripojenia do pool
     */
    public function releaseConnection($pdo) {
        $this->connectionManager->releaseConnection($pdo);
    }
    
    /**
     * Získanie statistik pool
     */
    public function getPoolStats() {
        return $this->connectionManager->getPoolStats();
    }
    
    /**
     * Zatvorenie všetkých pripojení
     */
    public function closeAllConnections() {
        $this->connectionManager->closeAllConnections();
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
}
?>
