<?php
/**
 * 🔗 CONNECTION POOLING SYSTEM - REFACTORED
 * Optimalizácia databázových pripojení s connection pooling
 * 
 * REFACTORED: Rozdelené na menšie súbory:
 * - config/database/ConnectionPool.php (hlavná trieda)
 * - config/database/ConnectionManager.php (správa pripojení)
 * - config/database/PoolCleaner.php (cleanup proces)
 * - config/database/ConnectionValidator.php (validácia pripojení)
 * - config/database/DatabaseConnection.php (wrapper)
 */

// Include refactored classes
require_once __DIR__ . '/database/DatabaseConnection.php';

// Pre spätnú kompatibilitu - exportujeme triedy len ak ešte neexistujú
if (!class_exists('DatabaseConnection')) {
    class_alias('DatabaseConnection', 'DatabaseConnection');
}
if (!class_exists('AutoReleaseConnection')) {
    class_alias('AutoReleaseConnection', 'AutoReleaseConnection');
}
if (!class_exists('ConnectionPool')) {
    class_alias('ConnectionPool', 'ConnectionPool');
}

// Pôvodný súbor je v archive/connection_pool_original.php
?>
