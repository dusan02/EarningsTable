<?php
/**
 * 🔒 SECURE DATABASE BACKUP WITH ENCRYPTION
 * Bezpečné zálohovanie databázy s enkrypciou a kompresiou
 */

require_once __DIR__ . '/../config/config.php';

class SecureBackup {
    private $dbConfig;
    private $backupDir;
    private $encryptionKey;
    private $maxBackups;
    
    public function __construct() {
        $this->dbConfig = [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'charset' => DB_CHARSET
        ];
        
        $this->backupDir = __DIR__ . '/../storage/backups';
        $this->encryptionKey = $this->getEncryptionKey();
        $this->maxBackups = 10;
        
        // Vytvorenie backup adresára
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0750, true);
        }
    }
    
    /**
     * Získanie enkrypčného kľúča
     */
    private function getEncryptionKey() {
        $keyFile = __DIR__ . '/../config/backup_key.php';
        
        if (!file_exists($keyFile)) {
            // Generovanie nového kľúča
            $key = base64_encode(random_bytes(32));
            $keyContent = "<?php\nreturn '" . $key . "';\n";
            file_put_contents($keyFile, $keyContent);
            chmod($keyFile, 0600); // Len pre vlastníka
        }
        
        return include $keyFile;
    }
    
    /**
     * Vytvorenie zálohy databázy
     */
    public function createBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupDir . "/backup_{$timestamp}.sql";
            $compressedFile = $backupFile . '.gz';
            $encryptedFile = $compressedFile . '.enc';
            
            echo "🔒 Starting secure backup...\n";
            
            // 1. Vytvorenie SQL dump
            $this->createSqlDump($backupFile);
            
            // 2. Kompresia
            $this->compressFile($backupFile, $compressedFile);
            
            // 3. Enkrypcia
            $this->encryptFile($compressedFile, $encryptedFile);
            
            // 4. Overenie integrity
            $this->verifyBackup($encryptedFile);
            
            // 5. Vyčistenie
            unlink($backupFile);
            unlink($compressedFile);
            
            // 6. Rotácia starých záloh
            $this->rotateOldBackups();
            
            // 7. Logovanie
            $this->logBackup($encryptedFile);
            
            echo "✅ Secure backup completed: " . basename($encryptedFile) . "\n";
            return $encryptedFile;
            
        } catch (Exception $e) {
            echo "❌ Backup failed: " . $e->getMessage() . "\n";
            $this->logError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Vytvorenie SQL dump
     */
    private function createSqlDump($outputFile) {
        echo "📦 Creating SQL dump...\n";
        
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers --add-drop-database --databases %s > %s',
            escapeshellarg($this->dbConfig['host']),
            escapeshellarg($this->dbConfig['user']),
            escapeshellarg($this->dbConfig['pass']),
            escapeshellarg($this->dbConfig['name']),
            escapeshellarg($outputFile)
        );
        
        $returnCode = 0;
        $output = [];
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("SQL dump failed: " . implode("\n", $output));
        }
        
        if (!file_exists($outputFile) || filesize($outputFile) === 0) {
            throw new Exception("SQL dump file is empty or missing");
        }
        
        echo "✅ SQL dump created: " . number_format(filesize($outputFile)) . " bytes\n";
    }
    
    /**
     * Kompresia súboru
     */
    private function compressFile($inputFile, $outputFile) {
        echo "🗜️ Compressing backup...\n";
        
        $input = gzopen($inputFile, 'rb');
        $output = fopen($outputFile, 'wb');
        
        if (!$input || !$output) {
            throw new Exception("Failed to open files for compression");
        }
        
        while (!gzeof($input)) {
            fwrite($output, gzread($input, 8192));
        }
        
        gzclose($input);
        fclose($output);
        
        echo "✅ Compression completed: " . number_format(filesize($outputFile)) . " bytes\n";
    }
    
    /**
     * Enkrypcia súboru
     */
    private function encryptFile($inputFile, $outputFile) {
        echo "🔐 Encrypting backup...\n";
        
        $data = file_get_contents($inputFile);
        if ($data === false) {
            throw new Exception("Failed to read file for encryption");
        }
        
        // Generovanie IV
        $iv = random_bytes(16);
        
        // Enkrypcia
        $encrypted = openssl_encrypt(
            $data,
            'AES-256-CBC',
            base64_decode($this->encryptionKey),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            throw new Exception("Encryption failed: " . openssl_error_string());
        }
        
        // Uloženie IV + enkryptovaných dát
        $output = $iv . $encrypted;
        
        if (file_put_contents($outputFile, $output) === false) {
            throw new Exception("Failed to write encrypted file");
        }
        
        echo "✅ Encryption completed: " . number_format(filesize($outputFile)) . " bytes\n";
    }
    
    /**
     * Overenie integrity zálohy
     */
    private function verifyBackup($backupFile) {
        echo "🔍 Verifying backup integrity...\n";
        
        if (!file_exists($backupFile)) {
            throw new Exception("Backup file not found");
        }
        
        $data = file_get_contents($backupFile);
        if (strlen($data) < 16) {
            throw new Exception("Backup file too small");
        }
        
        // Extrakcia IV
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        // Pokus o dešifrovanie
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            base64_decode($this->encryptionKey),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($decrypted === false) {
            throw new Exception("Backup verification failed: " . openssl_error_string());
        }
        
        echo "✅ Backup integrity verified\n";
    }
    
    /**
     * Rotácia starých záloh
     */
    private function rotateOldBackups() {
        echo "🧹 Rotating old backups...\n";
        
        $files = glob($this->backupDir . '/*.enc');
        if (count($files) <= $this->maxBackups) {
            return;
        }
        
        // Zoradenie podľa času modifikácie
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Vymazanie starých súborov
        $filesToDelete = array_slice($files, $this->maxBackups);
        foreach ($filesToDelete as $file) {
            unlink($file);
            echo "🗑️ Deleted old backup: " . basename($file) . "\n";
        }
    }
    
    /**
     * Logovanie zálohy
     */
    private function logBackup($backupFile) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => basename($backupFile),
            'size' => filesize($backupFile),
            'checksum' => hash_file('sha256', $backupFile)
        ];
        
        $logFile = $this->backupDir . '/backup_log.json';
        $logs = [];
        
        if (file_exists($logFile)) {
            $logs = json_decode(file_get_contents($logFile), true) ?: [];
        }
        
        $logs[] = $logData;
        
        // Zachovanie len posledných 100 záznamov
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
    }
    
    /**
     * Logovanie chýb
     */
    private function logError($error) {
        $logFile = $this->backupDir . '/backup_errors.log';
        $logEntry = date('Y-m-d H:i:s') . " - " . $error . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Obnovenie zálohy
     */
    public function restoreBackup($backupFile) {
        try {
            echo "🔄 Starting backup restoration...\n";
            
            if (!file_exists($backupFile)) {
                throw new Exception("Backup file not found");
            }
            
            // 1. Dešifrovanie
            $decryptedFile = $backupFile . '.decrypted';
            $this->decryptFile($backupFile, $decryptedFile);
            
            // 2. Dekompresia
            $decompressedFile = $decryptedFile . '.sql';
            $this->decompressFile($decryptedFile, $decompressedFile);
            
            // 3. Obnovenie databázy
            $this->restoreDatabase($decompressedFile);
            
            // 4. Vyčistenie
            unlink($decryptedFile);
            unlink($decompressedFile);
            
            echo "✅ Backup restoration completed\n";
            return true;
            
        } catch (Exception $e) {
            echo "❌ Restoration failed: " . $e->getMessage() . "\n";
            $this->logError("Restoration failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Dešifrovanie súboru
     */
    private function decryptFile($inputFile, $outputFile) {
        $data = file_get_contents($inputFile);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            base64_decode($this->encryptionKey),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($decrypted === false) {
            throw new Exception("Decryption failed");
        }
        
        file_put_contents($outputFile, $decrypted);
    }
    
    /**
     * Dekompresia súboru
     */
    private function decompressFile($inputFile, $outputFile) {
        $input = gzopen($inputFile, 'rb');
        $output = fopen($outputFile, 'wb');
        
        while (!gzeof($input)) {
            fwrite($output, gzread($input, 8192));
        }
        
        gzclose($input);
        fclose($output);
    }
    
    /**
     * Obnovenie databázy
     */
    private function restoreDatabase($sqlFile) {
        $command = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s',
            escapeshellarg($this->dbConfig['host']),
            escapeshellarg($this->dbConfig['user']),
            escapeshellarg($this->dbConfig['pass']),
            escapeshellarg($this->dbConfig['name']),
            escapeshellarg($sqlFile)
        );
        
        $returnCode = 0;
        $output = [];
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Database restoration failed: " . implode("\n", $output));
        }
    }
    
    /**
     * Zobrazenie dostupných záloh
     */
    public function listBackups() {
        $files = glob($this->backupDir . '/*.enc');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'file' => basename($file),
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'checksum' => hash_file('sha256', $file)
            ];
        }
        
        // Zoradenie podľa dátumu
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $backups;
    }
}

// Spustenie zálohy ak je skript spustený priamo
if (php_sapi_name() === 'cli') {
    $backup = new SecureBackup();
    
    if (isset($argv[1]) && $argv[1] === 'list') {
        echo "📋 Available backups:\n";
        $backups = $backup->listBackups();
        foreach ($backups as $backup) {
            echo sprintf(
                "  %s - %s - %s bytes - %s\n",
                $backup['file'],
                $backup['date'],
                number_format($backup['size']),
                $backup['checksum']
            );
        }
    } elseif (isset($argv[1]) && $argv[1] === 'restore' && isset($argv[2])) {
        $backupFile = $backup->backupDir . '/' . $argv[2];
        $backup->restoreBackup($backupFile);
    } else {
        $backup->createBackup();
    }
}
?>
