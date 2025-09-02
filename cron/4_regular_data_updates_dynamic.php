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
?>
