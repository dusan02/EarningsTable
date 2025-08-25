<?php
/**
 * 🔒 SECURITY TESTING SCRIPT
 * Komplexné testovanie všetkých bezpečnostných opatrení
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/connection_pool.php';

class SecurityTester {
    private $results = [];
    private $errors = [];
    
    public function runAllTests() {
        echo "🔒 Starting comprehensive security tests...\n\n";
        
        $this->testDatabasePermissions();
        $this->testConnectionPool();
        $this->testBackupSystem();
        $this->testFilePermissions();
        $this->testInputValidation();
        $this->testEncryption();
        $this->testLogging();
        
        $this->displayResults();
    }
    
    /**
     * Test databázových oprávnení
     */
    private function testDatabasePermissions() {
        echo "📊 Testing database permissions...\n";
        
        try {
            $pdo = DatabaseConnection::getConnection();
            
            // Test SELECT
            $stmt = $pdo->query("SELECT 1 as test");
            $result = $stmt->fetch();
            $this->addResult('Database SELECT', $result['test'] == 1, 'SELECT permission working');
            
            // Test INSERT (na testovacej tabuľke)
            $stmt = $pdo->prepare("CREATE TEMPORARY TABLE security_test (id INT, value VARCHAR(10))");
            $stmt->execute();
            $this->addResult('Database CREATE TEMP', true, 'Temporary table creation allowed');
            
            // Test DDL restrictions
            try {
                $stmt = $pdo->prepare("CREATE TABLE security_test_perm (id INT)");
                $stmt->execute();
                $this->addResult('Database DDL Restriction', false, 'DDL should be restricted');
            } catch (PDOException $e) {
                $this->addResult('Database DDL Restriction', true, 'DDL properly restricted');
            }
            
            // Test administrative restrictions
            try {
                $stmt = $pdo->query("SHOW PROCESSLIST");
                $this->addResult('Database Admin Restriction', false, 'Admin commands should be restricted');
            } catch (PDOException $e) {
                $this->addResult('Database Admin Restriction', true, 'Admin commands properly restricted');
            }
            
            // Cleanup
            $stmt = $pdo->prepare("DROP TEMPORARY TABLE security_test");
            $stmt->execute();
            
        } catch (Exception $e) {
            $this->addError('Database Permissions', $e->getMessage());
        }
    }
    
    /**
     * Test connection pool
     */
    private function testConnectionPool() {
        echo "🔗 Testing connection pool...\n";
        
        try {
            $pool = ConnectionPool::getInstance();
            $stats = $pool->getPoolStats();
            
            $this->addResult('Connection Pool Min', 
                $stats['total_connections'] >= $stats['min_connections'], 
                "Pool has minimum {$stats['min_connections']} connections");
            
            $this->addResult('Connection Pool Max', 
                $stats['total_connections'] <= $stats['max_connections'], 
                "Pool respects maximum {$stats['max_connections']} connections");
            
            // Test connection reuse
            $connections = [];
            for ($i = 0; $i < 5; $i++) {
                $connections[] = DatabaseConnection::getConnection();
            }
            
            $newStats = $pool->getPoolStats();
            $this->addResult('Connection Pool Reuse', 
                $newStats['total_connections'] <= $stats['max_connections'], 
                'Connections are being reused');
            
            // Release connections
            foreach ($connections as $conn) {
                DatabaseConnection::releaseConnection($conn);
            }
            
        } catch (Exception $e) {
            $this->addError('Connection Pool', $e->getMessage());
        }
    }
    
    /**
     * Test backup systému
     */
    private function testBackupSystem() {
        echo "💾 Testing backup system...\n";
        
        try {
            // Test backup key
            $keyFile = __DIR__ . '/../config/backup_key.php';
            $this->addResult('Backup Key Exists', 
                file_exists($keyFile), 
                'Backup encryption key exists');
            
            if (file_exists($keyFile)) {
                $key = include $keyFile;
                $this->addResult('Backup Key Valid', 
                    strlen($key) >= 32, 
                    'Backup key is properly sized');
            }
            
            // Test backup directory
            $backupDir = __DIR__ . '/../storage/backups';
            $this->addResult('Backup Directory', 
                is_dir($backupDir), 
                'Backup directory exists');
            
            if (is_dir($backupDir)) {
                $this->addResult('Backup Directory Permissions', 
                    substr(sprintf('%o', fileperms($backupDir)), -4) == '0750', 
                    'Backup directory has correct permissions (750)');
            }
            
            // Test backup script
            $backupScript = __DIR__ . '/secure_backup.php';
            $this->addResult('Backup Script', 
                file_exists($backupScript), 
                'Backup script exists');
            
        } catch (Exception $e) {
            $this->addError('Backup System', $e->getMessage());
        }
    }
    
    /**
     * Test oprávnení súborov
     */
    private function testFilePermissions() {
        echo "📁 Testing file permissions...\n";
        
        $files = [
            'config/config.php' => '640',
            'config/backup_key.php' => '600',
            'logs/' => '750',
            'storage/backups/' => '750'
        ];
        
        foreach ($files as $file => $expectedPerms) {
            $fullPath = __DIR__ . '/../' . $file;
            
            if (file_exists($fullPath)) {
                $actualPerms = substr(sprintf('%o', fileperms($fullPath)), -4);
                $this->addResult("File Permissions: $file", 
                    $actualPerms == $expectedPerms, 
                    "Expected: $expectedPerms, Got: $actualPerms");
            } else {
                $this->addResult("File Permissions: $file", 
                    false, 
                    'File does not exist');
            }
        }
    }
    
    /**
     * Test validácie vstupov
     */
    private function testInputValidation() {
        echo "✅ Testing input validation...\n";
        
        require_once __DIR__ . '/../config/database_helper.php';
        
        // Test string sanitization
        $testInput = "<script>alert('xss')</script>";
        $sanitized = InputValidator::sanitizeString($testInput);
        $this->addResult('Input Sanitization', 
            strpos($sanitized, '<script>') === false, 
            'XSS input properly sanitized');
        
        // Test email validation
        $this->addResult('Email Validation Valid', 
            InputValidator::validateEmail('test@example.com'), 
            'Valid email accepted');
        
        $this->addResult('Email Validation Invalid', 
            !InputValidator::validateEmail('invalid-email'), 
            'Invalid email rejected');
        
        // Test ticker validation
        $this->addResult('Ticker Validation Valid', 
            InputValidator::validateTicker('AAPL'), 
            'Valid ticker accepted');
        
        $this->addResult('Ticker Validation Invalid', 
            !InputValidator::validateTicker('AAPL!'), 
            'Invalid ticker rejected');
        
        // Test integer validation
        $this->addResult('Integer Validation', 
            InputValidator::validateInteger('123', 0, 1000) === 123, 
            'Integer validation working');
    }
    
    /**
     * Test enkrypcie
     */
    private function testEncryption() {
        echo "🔐 Testing encryption...\n";
        
        try {
            // Test OpenSSL availability
            $this->addResult('OpenSSL Available', 
                extension_loaded('openssl'), 
                'OpenSSL extension is loaded');
            
            if (extension_loaded('openssl')) {
                // Test AES encryption
                $data = 'test data for encryption';
                $key = random_bytes(32);
                $iv = random_bytes(16);
                
                $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
                
                $this->addResult('AES Encryption', 
                    $decrypted === $data, 
                    'AES-256-CBC encryption/decryption working');
            }
            
            // Test random bytes
            $random1 = random_bytes(16);
            $random2 = random_bytes(16);
            $this->addResult('Random Bytes', 
                $random1 !== $random2, 
                'Random bytes are unique');
            
        } catch (Exception $e) {
            $this->addError('Encryption', $e->getMessage());
        }
    }
    
    /**
     * Test logovania
     */
    private function testLogging() {
        echo "📝 Testing logging...\n";
        
        $logDir = __DIR__ . '/../logs';
        
        $this->addResult('Log Directory', 
            is_dir($logDir), 
            'Log directory exists');
        
        if (is_dir($logDir)) {
            $this->addResult('Log Directory Writable', 
                is_writable($logDir), 
                'Log directory is writable');
            
            // Test log file creation
            $testLogFile = $logDir . '/security_test.log';
            $testMessage = 'Security test log entry';
            
            if (file_put_contents($testLogFile, $testMessage) !== false) {
                $this->addResult('Log File Creation', 
                    file_exists($testLogFile), 
                    'Log file can be created');
                
                // Cleanup
                unlink($testLogFile);
            } else {
                $this->addResult('Log File Creation', 
                    false, 
                    'Cannot create log file');
            }
        }
    }
    
    /**
     * Pridanie výsledku testu
     */
    private function addResult($test, $passed, $message) {
        $this->results[] = [
            'test' => $test,
            'passed' => $passed,
            'message' => $message
        ];
        
        echo $passed ? "  ✅ " : "  ❌ ";
        echo "$test: $message\n";
    }
    
    /**
     * Pridanie chyby
     */
    private function addError($test, $error) {
        $this->errors[] = [
            'test' => $test,
            'error' => $error
        ];
        
        echo "  ❌ $test: ERROR - $error\n";
    }
    
    /**
     * Zobrazenie výsledkov
     */
    private function displayResults() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🔒 SECURITY TEST RESULTS\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->results as $result) {
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        echo "📊 Summary:\n";
        echo "  ✅ Passed: $passed\n";
        echo "  ❌ Failed: $failed\n";
        echo "  📝 Total: " . count($this->results) . "\n\n";
        
        if (!empty($this->errors)) {
            echo "🚨 Errors:\n";
            foreach ($this->errors as $error) {
                echo "  ❌ {$error['test']}: {$error['error']}\n";
            }
            echo "\n";
        }
        
        if ($failed > 0) {
            echo "⚠️  Failed Tests:\n";
            foreach ($this->results as $result) {
                if (!$result['passed']) {
                    echo "  ❌ {$result['test']}: {$result['message']}\n";
                }
            }
            echo "\n";
        }
        
        if ($failed == 0 && empty($this->errors)) {
            echo "🎉 All security tests passed! Your system is secure.\n";
        } else {
            echo "⚠️  Some security issues found. Please review and fix.\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
    }
}

// Spustenie testov ak je skript spustený priamo
if (php_sapi_name() === 'cli') {
    $tester = new SecurityTester();
    $tester->runAllTests();
}
?>
