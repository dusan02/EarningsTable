<?php
/**
 * 🔐 ENCRYPTION HANDLER
 * Enkrypcia a dešifrovanie záloh
 */

class EncryptionHandler {
    private $encryptionKey;
    
    public function __construct() {
        $this->encryptionKey = $this->getEncryptionKey();
    }
    
    /**
     * Získanie enkrypčného kľúča
     */
    private function getEncryptionKey() {
        $keyFile = __DIR__ . '/../../config/backup_key.php';
        
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
     * Enkrypcia súboru
     */
    public function encryptFile($inputFile, $outputFile) {
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
     * Dešifrovanie súboru
     */
    public function decryptFile($inputFile, $outputFile) {
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
}
?>
