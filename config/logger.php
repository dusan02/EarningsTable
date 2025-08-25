<?php
/**
 * 🔒 SECURITY LOGGER & MONITORING - REFACTORED
 * Bezpečnostné logovanie a sledovanie aktivít
 * 
 * REFACTORED: Rozdelené na menšie súbory:
 * - config/logging/SecurityLogger.php (hlavná trieda)
 * - config/logging/AlertManager.php (správa alertov)
 * - config/logging/LogRotator.php (rotácia logov)
 * - config/logging/ThreatDetector.php (detekcia hrozieb)
 */

require_once __DIR__ . '/logging/SecurityLogger.php';

// Pre spätnú kompatibilitu - exportujeme triedu
class_alias('SecurityLogger', 'SecurityLogger');

// Pôvodný súbor je v archive/logger_original.php
?>
