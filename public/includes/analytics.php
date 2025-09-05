<?php
/**
 * Google Analytics Include File
 * 
 * Tento súbor obsahuje Google Analytics kód pre vloženie do HTML stránok
 */

// Načítať konfiguráciu
require_once __DIR__ . '/../../config/analytics.php';

// Získať Google Analytics kód
$gaCode = getGoogleAnalyticsCode();

// Vypísať kód len ak je povolený a má platné ID
if (!empty($gaCode)) {
    echo $gaCode;
}
?>
