<?php
/**
 * Path Test for Cron Jobs
 * This file helps determine the correct path for cron jobs on mydreams.cz
 */

echo "=== PATH TEST FOR EARNINGSTABLE.COM ===\n\n";

// Current directory
echo "Current directory (__DIR__): " . __DIR__ . "\n";

// Document root
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not available') . "\n";

// Script path
echo "Script path: " . $_SERVER['SCRIPT_NAME'] . "\n";

// Full path
echo "Full path: " . $_SERVER['SCRIPT_FILENAME'] . "\n";

// Working directory
echo "Working directory: " . getcwd() . "\n";

echo "\n=== CRON JOB PATHS ===\n\n";

// Test if cron files exist
$cronFiles = [
    'cron/clear_old_data.php',
    'cron/fetch_finnhub_earnings_today_tickers.php',
    'cron/fetch_missing_tickers_yahoo.php',
    'cron/fetch_market_data_complete.php',
    'cron/run_5min_updates.php'
];

foreach ($cronFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ $file: EXISTS at $fullPath\n";
    } else {
        echo "❌ $file: MISSING at $fullPath\n";
    }
}

echo "\n=== RECOMMENDED CRON COMMANDS ===\n\n";

$basePath = __DIR__;
echo "# For earningstable.com on mydreams.cz:\n";
echo "# Daily cleanup (08:00 CET)\n";
echo "0 8 * * * /usr/bin/php $basePath/cron/clear_old_data.php\n\n";

echo "# Fetch earnings (08:30 CET)\n";
echo "30 8 * * * /usr/bin/php $basePath/cron/fetch_finnhub_earnings_today_tickers.php\n\n";

echo "# Fetch missing tickers (08:40 CET)\n";
echo "40 8 * * * /usr/bin/php $basePath/cron/fetch_missing_tickers_yahoo.php\n\n";

echo "# Fetch market data (09:00 CET)\n";
echo "0 9 * * * /usr/bin/php $basePath/cron/fetch_market_data_complete.php\n\n";

echo "# 5-min updates\n";
echo "*/5 * * * * /usr/bin/php $basePath/cron/run_5min_updates.php\n\n";

echo "=== ALTERNATIVE (RELATIVE PATHS) ===\n\n";

echo "# Alternative using relative paths:\n";
echo "0 8 * * * cd $basePath && /usr/bin/php cron/clear_old_data.php\n";
echo "30 8 * * * cd $basePath && /usr/bin/php cron/fetch_finnhub_earnings_today_tickers.php\n";
echo "40 8 * * * cd $basePath && /usr/bin/php cron/fetch_missing_tickers_yahoo.php\n";
echo "0 9 * * * cd $basePath && /usr/bin/php cron/fetch_market_data_complete.php\n";
echo "*/5 * * * * cd $basePath && /usr/bin/php cron/run_5min_updates.php\n";

echo "\n=== NEXT STEPS ===\n";
echo "1. Copy the recommended cron commands above\n";
echo "2. Paste them into mydreams.cz cron job settings\n";
echo "3. Test one cron job manually first\n";
echo "4. Check logs in logs/ directory for errors\n";
?>
