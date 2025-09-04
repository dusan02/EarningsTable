<?php
/**
 * 🚀 BENZINGA CORPORATE GUIDANCE UPDATES - 5 MINUTE CRON
 * 
 * 5-minútový cron pre získavanie guidance dát z Benzinga Corporate Guidance API
 * - Používa tickery zo statického cronu (earnings calendar z Finnhub)
 * - Mapuje JSON polia priamo na stĺpce tabuľky benzinga_guidance
 * - Spúšťa sa každých 5 minút pre aktualizáciu corporate guidance
 */

echo "🚀 Starting Benzinga Guidance 5-Minute Cron...\n";
echo "📅 Date: " . date('Y-m-d H:i:s') . "\n\n";

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

echo "4. Running 5-minute guidance update...\n";
$benzingaGuidance->run();
echo "✅ 5-minute guidance update completed\n";

echo "\n🎯 Benzinga Guidance 5-Minute Cron completed successfully!\n";
?>
