<?php
/**
 * 🚀 BENZINGA CORPORATE GUIDANCE UPDATES CRON
 * 
 * Samostatný cron pre získavanie guidance dát z Benzinga Corporate Guidance API (cez Polygon)
 * - Používa tickery zo statického cronu (earnings calendar z Finnhub)
 * - Mapuje JSON polia priamo na stĺpce tabuľky benzinga_guidance
 * - Spúšťa sa ako 4. krok v 1_enhanced_master_cron.php
 */

echo "🚀 Starting Benzinga Guidance Cron...\n";

// Load configuration first
echo "1. Loading config.php...\n";
require_once dirname(__DIR__) . '/config.php';
echo "✅ Config loaded\n";

echo "2. Loading BenzingaGuidance class...\n";
require_once dirname(__DIR__) . '/common/BenzingaGuidance.php';
echo "✅ Class loaded\n";

// Spustenie Benzinga Corporate Guidance procesu
echo "3. Creating instance...\n";
$benzingaGuidance = new BenzingaGuidance();
echo "✅ Instance created\n";

echo "4. Running process...\n";
$benzingaGuidance->run();
echo "✅ Process completed\n";
?>
