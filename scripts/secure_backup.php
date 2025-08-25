<?php
/**
 * 🔒 SECURE DATABASE BACKUP WITH ENCRYPTION - REFACTORED
 * Bezpečné zálohovanie databázy s enkrypciou a kompresiou
 * 
 * REFACTORED: Rozdelené na menšie súbory:
 * - scripts/backup/BackupManager.php (hlavná trieda)
 * - scripts/backup/EncryptionHandler.php (enkrypcia/dekrypcia)
 * - scripts/backup/CompressionHandler.php (kompresia)
 * - scripts/backup/BackupVerifier.php (overenie integrity)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/backup/BackupManager.php';

// Pre spätnú kompatibilitu - exportujeme triedu
class_alias('BackupManager', 'SecureBackup');

// Spustenie zálohy ak je skript spustený priamo
if (php_sapi_name() === 'cli') {
    $backup = new BackupManager();
    
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

// Pôvodný súbor je v archive/secure_backup_original.php
?>
