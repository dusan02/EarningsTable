<?php
/**
 * 🔗 DATABASE CONNECTION WRAPPER
 * Wrapper pre spätnú kompatibilitu s pôvodným connection_pool.php
 */

require_once __DIR__ . '/ConnectionPool.php';

/**
 * Wrapper pre connection pool
 */
class DatabaseConnection {
    private static $pool;
    
    public static function getConnection() {
        if (self::$pool === null) {
            self::$pool = ConnectionPool::getInstance();
        }
        return self::$pool->getConnection();
    }
    
    public static function releaseConnection($pdo) {
        if (self::$pool !== null) {
            self::$pool->releaseConnection($pdo);
        }
    }
    
    public static function getPoolStats() {
        if (self::$pool === null) {
            self::$pool = ConnectionPool::getInstance();
        }
        return self::$pool->getPoolStats();
    }
}

/**
 * Automatické uvoľnenie pripojenia
 */
class AutoReleaseConnection {
    private $pdo;
    private $pool;
    
    public function __construct() {
        $this->pool = ConnectionPool::getInstance();
        $this->pdo = $this->pool->getConnection();
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function __destruct() {
        if ($this->pdo && $this->pool) {
            $this->pool->releaseConnection($this->pdo);
        }
    }
}

// Registrácia shutdown funkcie
register_shutdown_function(function() {
    if (ConnectionPool::getInstance()) {
        ConnectionPool::getInstance()->closeAllConnections();
    }
});

// Príklad použitia:
/*
try {
    $autoConn = new AutoReleaseConnection();
    $pdo = $autoConn->getConnection();
    
    // Použitie pripojenia
    $stmt = $pdo->prepare("SELECT * FROM earnings_today LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    
    // Pripojenie sa automaticky uvoľní pri ukončení skriptu
} catch (Exception $e) {
    logDatabaseError('example_query', 'SELECT * FROM earnings_today LIMIT 1', [], $e->getMessage());
    displayError("Database error: " . $e->getMessage());
}
*/
?>
