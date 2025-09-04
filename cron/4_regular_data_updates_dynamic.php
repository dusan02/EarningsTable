<?php
/**
 * ⚡ REGULAR DATA UPDATES - DYNAMIC (OPTIMALIZOVANÁ VERZIA)
 * 
 * Používa triedu RegularDataUpdatesDynamic s optimalizáciami:
 * - Kódová organizácia (triedy)
 * - Batch SELECT/UPDATE (95% zrýchlenie DB operácií)
 * - Retry logic (zvýšenie reliability)
 * - Performance optimalizácie
 * - Data validation
 * - Configuration management
 * - Monitoring a metrics
 * 
 * Tento cron sa spúšťa každých 5 minút a aktualizuje len dynamické dáta
 */

require_once dirname(__DIR__) . '/common/RegularDataUpdatesDynamic.php';

// Spustenie optimalizovaného procesu
$dynamicUpdates = new RegularDataUpdatesDynamic();
$dynamicUpdates->run();

// STEP 2: Estimates Consensus Updates (5-minute updates)
echo "\n=== STEP 2: ESTIMATES CONSENSUS UPDATES (5-MIN) ===\n";
$consensusStart = microtime(true);

$output = [];
$returnCode = 0;
exec('php cron/6_estimates_consensus_updates.php 2>&1', $output, $returnCode);

$consensusTime = round(microtime(true) - $consensusStart, 2);
echo implode("\n", $output) . "\n";
if ($returnCode === 0) {
    echo "✅ Estimates consensus updates completed in {$consensusTime}s\n";
} else {
    echo "❌ Estimates consensus updates failed\n";
}
?>
