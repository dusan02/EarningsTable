<?php
/**
 * 🔍 BACKUP VERIFIER
 * Overenie integrity záloh
 */

class BackupVerifier {
    private $encryptionHandler;
    
    public function __construct($encryptionHandler) {
        $this->encryptionHandler = $encryptionHandler;
    }
    
    /**
     * Overenie integrity zálohy
     */
    public function verifyBackup($backupFile) {
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
            base64_decode($this->encryptionHandler->getEncryptionKey()),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($decrypted === false) {
            throw new Exception("Backup verification failed: " . openssl_error_string());
        }
        
        echo "✅ Backup integrity verified\n";
    }
}
?>
