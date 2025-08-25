<?php
/**
 * Database Helper with SQL Injection Protection
 * Bezpečné databázové operácie s prepared statements
 */

class DatabaseHelper {
    private $pdo;
    private static $instance = null;
    
    /**
     * Konštruktor
     */
    private function __construct() {
        global $pdo;
        $this->pdo = $pdo;
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
     * Bezpečné vykonanie SELECT dotazu
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError('SELECT', $sql, $params, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Bezpečné vykonanie INSERT dotazu
     */
    public function insert($table, $data) {
        try {
            $columns = array_keys($data);
            $placeholders = ':' . implode(', :', $columns);
            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES ($placeholders)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->logError('INSERT', $sql ?? '', $data, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Bezpečné vykonanie UPDATE dotazu
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $setParts = [];
            foreach (array_keys($data) as $column) {
                $setParts[] = "$column = :$column";
            }
            
            $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE $where";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge($data, $whereParams));
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('UPDATE', $sql ?? '', array_merge($data, $whereParams), $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Bezpečné vykonanie DELETE dotazu
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM $table WHERE $where";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError('DELETE', $sql, $params, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Bezpečné vykonanie COUNT dotazu
     */
    public function count($table, $where = '1', $params = []) {
        try {
            $sql = "SELECT COUNT(*) as count FROM $table WHERE $where";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int) $result['count'];
        } catch (PDOException $e) {
            $this->logError('COUNT', $sql, $params, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Bezpečné vykonanie EXISTS dotazu
     */
    public function exists($table, $where, $params = []) {
        try {
            $sql = "SELECT 1 FROM $table WHERE $where LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            $this->logError('EXISTS', $sql, $params, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Bezpečné vykonanie custom dotazu
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError('EXECUTE', $sql, $params, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Začatie transakcie
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transakcie
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transakcie
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Logovanie chýb
     */
    private function logError($operation, $sql, $params, $error) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operation' => $operation,
            'sql' => $sql,
            'params' => $params,
            'error' => $error,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $logFile = __DIR__ . '/../logs/database_errors.log';
        $logEntry = json_encode($logData) . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Získanie PDO objektu
     */
    public function getPdo() {
        return $this->pdo;
    }
}

/**
 * Input Validator
 * Validácia a sanitizácia vstupov
 */
class InputValidator {
    /**
     * Sanitizuje string
     */
    public static function sanitizeString($input, $maxLength = 255) {
        if (!is_string($input)) {
            return '';
        }
        
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }
    
    /**
     * Validuje email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validuje integer
     */
    public static function validateInteger($input, $min = null, $max = null) {
        $int = filter_var($input, FILTER_VALIDATE_INT);
        
        if ($int === false) {
            return false;
        }
        
        if ($min !== null && $int < $min) {
            return false;
        }
        
        if ($max !== null && $int > $max) {
            return false;
        }
        
        return $int;
    }
    
    /**
     * Validuje float
     */
    public static function validateFloat($input, $min = null, $max = null) {
        $float = filter_var($input, FILTER_VALIDATE_FLOAT);
        
        if ($float === false) {
            return false;
        }
        
        if ($min !== null && $float < $min) {
            return false;
        }
        
        if ($max !== null && $float > $max) {
            return false;
        }
        
        return $float;
    }
    
    /**
     * Validuje date
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validuje ticker symbol
     */
    public static function validateTicker($ticker) {
        // Ticker musí byť 1-10 znakov, len písmená a čísla
        return preg_match('/^[A-Z0-9]{1,10}$/', strtoupper($ticker));
    }
    
    /**
     * Validuje array
     */
    public static function validateArray($input, $allowedKeys = []) {
        if (!is_array($input)) {
            return false;
        }
        
        if (!empty($allowedKeys)) {
            foreach (array_keys($input) as $key) {
                if (!in_array($key, $allowedKeys)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Escape pre LIKE dotazy
     */
    public static function escapeLike($string) {
        return str_replace(['%', '_'], ['\\%', '\\_'], $string);
    }
}

/**
 * Query Builder
 * Bezpečné budovanie SQL dotazov
 */
class QueryBuilder {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseHelper::getInstance();
    }
    
    /**
     * Vytvorí SELECT dotaz
     */
    public function select($table, $columns = ['*'], $where = '1', $params = [], $orderBy = '', $limit = '') {
        $columnsStr = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "SELECT $columnsStr FROM $table WHERE $where";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        return $this->db->select($sql, $params);
    }
    
    /**
     * Vytvorí INSERT dotaz
     */
    public function insert($table, $data) {
        return $this->db->insert($table, $data);
    }
    
    /**
     * Vytvorí UPDATE dotaz
     */
    public function update($table, $data, $where, $whereParams = []) {
        return $this->db->update($table, $data, $where, $whereParams);
    }
    
    /**
     * Vytvorí DELETE dotaz
     */
    public function delete($table, $where, $params = []) {
        return $this->db->delete($table, $where, $params);
    }
    
    /**
     * Vytvorí COUNT dotaz
     */
    public function count($table, $where = '1', $params = []) {
        return $this->db->count($table, $where, $params);
    }
    
    /**
     * Vytvorí EXISTS dotaz
     */
    public function exists($table, $where, $params = []) {
        return $this->db->exists($table, $where, $params);
    }
}
?>
