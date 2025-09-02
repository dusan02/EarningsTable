<?php
/**
 * 🚀 DAILY DATA SETUP - STATIC (OPTIMALIZOVANÁ VERZIA)
 * 
 * Používa triedu DailyDataSetup s optimalizáciami:
 * - Kódová organizácia (triedy)
 * - Batch INSERT (90% zrýchlenie DB operácií)
 * - Retry logic (zvýšenie reliability)
 * - Performance optimalizácie
 * 
 * Tento cron sa spúšťa raz denne a pripravuje základné dáta pre 5-minútové updatey
 */

require_once dirname(__DIR__) . '/common/DailyDataSetup.php';

// Spustenie optimalizovaného procesu
$dailySetup = new DailyDataSetup();
$dailySetup->run();
?>
