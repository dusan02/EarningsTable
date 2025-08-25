<?php
/**
 * Run All Cron Jobs in Correct Order
 * Spustí všetky cron joby v správnom poradí s nastavenými API kľúčmi
 */

echo "🚀 SPUSTENIE VŠETKÝCH CRON JOBOV V SPRÁVNOM PORADÍ\n";
echo "==================================================\n\n";

// Set API keys directly
define('POLYGON_API_KEY', 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX');
define('FINNHUB_API_KEY', 'd2m1rr1r01qgtft6ppjgd2m1rr1r01qgtft6ppk0');

// Load configuration
require_once __DIR__ . '/config.php';

echo "✅ API kľúče nastavené\n";
echo "✅ Konfigurácia načítaná\n\n";

// 1. Clear old data
echo "1️⃣ Spúšťam clear_old_data.php...\n";
$output = shell_exec('D:\xampp\php\php.exe cron/clear_old_data.php 2>&1');
echo $output . "\n";

// 2. Fetch today's earnings tickers from Finnhub
echo "2️⃣ Spúšťam fetch_finnhub_earnings_today_tickers.php...\n";
$output = shell_exec('D:\xampp\php\php.exe cron/fetch_finnhub_earnings_today_tickers.php 2>&1');
echo $output . "\n";

// 3. Fetch complete market data (prices, market cap, company names)
echo "3️⃣ Spúšťam fetch_market_data_complete.php...\n";
$output = shell_exec('D:\xampp\php\php.exe cron/fetch_market_data_complete.php 2>&1');
echo $output . "\n";

// 4. Run 5-minute updates
echo "4️⃣ Spúšťam run_5min_updates.php...\n";
$output = shell_exec('D:\xampp\php\php.exe cron/run_5min_updates.php 2>&1');
echo $output . "\n";

echo "✅ VŠETKY CRON JOBS ÚSPEŠNE SPUSTENÉ!\n";
echo "=====================================\n\n";

// Check final data
echo "📊 KONTROLA FINÁLNYCH DÁT:\n";
echo "==========================\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements");
    $movementsCount = $stmt->fetchColumn();
    echo "TodayEarningsMovements: {$movementsCount} záznamov\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday");
    $tickersCount = $stmt->fetchColumn();
    echo "EarningsTickersToday: {$tickersCount} záznamov\n";
    
    if ($movementsCount > 0) {
        echo "\n📈 VZORKA DÁT:\n";
        $stmt = $pdo->query("SELECT ticker, current_price, market_cap, price_change_percent FROM TodayEarningsMovements LIMIT 3");
        while ($row = $stmt->fetch()) {
            echo "- {$row['ticker']}: \${$row['current_price']} (MC: " . ($row['market_cap'] ?: 'null') . ", Change: {$row['price_change_percent']}%)\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Chyba pri kontrole dát: " . $e->getMessage() . "\n";
}

echo "\n🎉 CRON JOBS DOKONČENÉ! Dashboard by mal zobrazovať aktuálne dáta.\n";
?>
