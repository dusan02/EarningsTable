<?php
/**
 * Run Cron Jobs with API Keys
 * Spustí cron joby v správnom poradí s nastavenými API kľúčmi
 */

// Set API keys directly
define('POLYGON_API_KEY', 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX');
define('FINNHUB_API_KEY', 'your_finnhub_api_key_here');

// Load configuration
require_once __DIR__ . '/config.php';

echo "🚀 RUNNING CRON JOBS IN CORRECT ORDER\n";
echo "====================================\n\n";

// Step 1: Clear old data
echo "=== STEP 1: CLEAR OLD DATA ===\n";
$output = shell_exec("D:\\xampp\\php\\php.exe cron\\2_clear_old_data.php --force 2>&1");
echo $output;
echo "\n" . str_repeat("-", 50) . "\n\n";

// Step 2: Add test data
echo "=== STEP 2: ADD TEST DATA ===\n";
$output = shell_exec("D:\\xampp\\php\\php.exe add_test_data.php 2>&1");
echo $output;
echo "\n" . str_repeat("-", 50) . "\n\n";

// Step 3: Fetch market data
echo "=== STEP 3: FETCH MARKET DATA ===\n";
$output = shell_exec("D:\\xampp\\php\\php.exe cron\\fetch_market_data_complete.php 2>&1");
echo $output;
echo "\n" . str_repeat("-", 50) . "\n\n";

// Step 4: Run 5-minute updates
echo "=== STEP 4: RUN 5-MINUTE UPDATES ===\n";
$output = shell_exec("D:\\xampp\\php\\php.exe cron\\run_5min_updates.php 2>&1");
echo $output;
echo "\n" . str_repeat("-", 50) . "\n\n";

echo "✅ ALL CRON JOBS COMPLETED!\n";
?>
