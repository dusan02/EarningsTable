<?php
/**
 * 🚀 BENZINGA CORPORATE GUIDANCE UPDATES CRON
 * 
 * Samostatný cron pre získavanie guidance dát z Benzinga Corporate Guidance API (cez Polygon)
 * - Používa tickery zo statického cronu (earnings calendar z Finnhub)
 * - Mapuje JSON polia priamo na stĺpce tabuľky benzinga_guidance
 * - Spúšťa sa ako 4. krok v 1_enhanced_master_cron.php
 */

require_once dirname(__DIR__) . '/common/BenzingaGuidance.php';

// Spustenie Benzinga Corporate Guidance procesu
$benzingaGuidance = new BenzingaGuidance();
$benzingaGuidance->run();
?>
