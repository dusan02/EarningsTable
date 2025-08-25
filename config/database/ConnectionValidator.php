<?php
/**
 * 🔗 CONNECTION VALIDATOR
 * Validácia a testovanie databázových pripojení
 */

class ConnectionValidator {
    
    /**
     * Overenie dostupnosti pripojenia
     */
    public function isConnectionAvailable($pdo, $connectionId) {
        if (!$pdo) {
            return false;
        }
        
        try {
            // Test pripojenia
            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetch();
            
            if ($result === false) {
                $this->log("Connection $connectionId failed SELECT 1 test", 'WARNING');
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            $this->log("Connection $connectionId is dead: " . $e->getMessage(), 'WARNING');
            return false;
        }
    }
    
    /**
     * Test pripojenia s timeout
     */
    public function testConnectionWithTimeout($pdo, $timeout = 5) {
        if (!$pdo) {
            return false;
        }
        
        try {
            // Nastavenie timeout pre test
            $pdo->setAttribute(PDO::ATTR_TIMEOUT, $timeout);
            
            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetch();
            
            return $result !== false;
            
        } catch (PDOException $e) {
            $this->log("Connection test failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Overenie transakčného stavu
     */
    public function isInTransaction($pdo) {
        if (!$pdo) {
            return false;
        }
        
        try {
            return $pdo->inTransaction();
        } catch (PDOException $e) {
            $this->log("Cannot check transaction state: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Overenie read-only stavu
     */
    public function isReadOnly($pdo) {
        if (!$pdo) {
            return false;
        }
        
        try {
            $stmt = $pdo->query("SELECT @@read_only");
            $result = $stmt->fetch();
            return $result && $result['@@read_only'] == 1;
        } catch (PDOException $e) {
            $this->log("Cannot check read-only state: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Overenie server verzie
     */
    public function getServerVersion($pdo) {
        if (!$pdo) {
            return null;
        }
        
        try {
            return $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (PDOException $e) {
            $this->log("Cannot get server version: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    /**
     * Overenie connection ID
     */
    public function getConnectionId($pdo) {
        if (!$pdo) {
            return null;
        }
        
        try {
            $stmt = $pdo->query("SELECT CONNECTION_ID() as conn_id");
            $result = $stmt->fetch();
            return $result ? $result['conn_id'] : null;
        } catch (PDOException $e) {
            $this->log("Cannot get connection ID: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    /**
     * Overenie dostupnosti tabulky
     */
    public function tableExists($pdo, $tableName) {
        if (!$pdo || !$tableName) {
            return false;
        }
        
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->log("Cannot check table existence: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Overenie oprávnení
     */
    public function checkPermissions($pdo) {
        if (!$pdo) {
            return false;
        }
        
        try {
            // Test SELECT
            $stmt = $pdo->query("SELECT 1");
            $selectOk = $stmt->fetch() !== false;
            
            // Test INSERT (vytvorenie dočasnej tabuľky)
            $tempTable = 'temp_test_' . uniqid();
            $stmt = $pdo->prepare("CREATE TEMPORARY TABLE $tempTable (id INT)");
            $insertOk = $stmt->execute();
            
            // Vyčistenie
            if ($insertOk) {
                $pdo->exec("DROP TEMPORARY TABLE IF EXISTS $tempTable");
            }
            
            return $selectOk && $insertOk;
            
        } catch (PDOException $e) {
            $this->log("Permission check failed: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Logovanie
     */
    private function log($message, $level = 'INFO') {
        $logFile = __DIR__ . '/../../logs/connection_validator.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>
