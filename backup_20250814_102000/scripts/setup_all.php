<?php
/**
 * Complete Setup Script for Earnings Table Module
 * Creates both tables and shows system status
 */

require_once __DIR__ . '/../config.php';

echo "🚀 COMPLETE SETUP FOR EARNINGS TABLE MODULE\n";
echo "==========================================\n\n";

// 1. Create EarningsTickersToday table
echo "📊 Creating EarningsTickersToday table...\n";
$sql = file_get_contents(__DIR__ . '/../sql/setup_all_tables.sql');

try {
    $pdo->exec($sql);
    echo "✅ Both tables created successfully!\n\n";
} catch (PDOException $e) {
    echo "❌ Error creating tables: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 2. Show table structure
echo "📋 TABLE STRUCTURES:\n";
echo "===================\n\n";

// EarningsTickersToday
$stmt = $pdo->query("DESCRIBE EarningsTickersToday");
echo "EarningsTickersToday (Finnhub API):\n";
echo "-----------------------------------\n";
while ($row = $stmt->fetch()) {
    echo "- {$row['Field']}: {$row['Type']}\n";
}
echo "\n";

// TodayEarningsMovements
$stmt = $pdo->query("DESCRIBE TodayEarningsMovements");
echo "TodayEarningsMovements (Polygon API):\n";
echo "------------------------------------\n";
while ($row = $stmt->fetch()) {
    echo "- {$row['Field']}: {$row['Type']}\n";
}
echo "\n";

// 3. Show current data status
echo "📈 CURRENT DATA STATUS:\n";
echo "======================\n\n";

// EarningsTickersToday count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM EarningsTickersToday");
$earningsCount = $stmt->fetch()['count'];
echo "EarningsTickersToday: {$earningsCount} records\n";

// TodayEarningsMovements count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM TodayEarningsMovements");
$movementsCount = $stmt->fetch()['count'];
echo "TodayEarningsMovements: {$movementsCount} records\n\n";

// 4. Show sample data
if ($earningsCount > 0) {
    echo "📊 SAMPLE EARNINGS DATA:\n";
    echo "=======================\n";
    $stmt = $pdo->query("SELECT * FROM EarningsTickersToday ORDER BY report_date DESC LIMIT 3");
    while ($row = $stmt->fetch()) {
        echo "- {$row['ticker']}: {$row['report_time']} (EPS: {$row['eps_actual']})\n";
    }
    echo "\n";
}

if ($movementsCount > 0) {
    echo "📈 SAMPLE MOVEMENTS DATA:\n";
    echo "=========================\n";
    $stmt = $pdo->query("SELECT * FROM TodayEarningsMovements ORDER BY market_cap DESC LIMIT 3");
    while ($row = $stmt->fetch()) {
        echo "- {$row['ticker']}: \${$row['current_price']} (MC: \${$row['market_cap']}, Size: {$row['size']})\n";
    }
    echo "\n";
}

echo "🎯 SETUP COMPLETE!\n";
echo "=================\n\n";

echo "Next steps:\n";
echo "1. Run: php cron/fetch_earnings.php (Finnhub data)\n";
echo "2. Run: php cron/update_movements.php (Polygon data)\n";
echo "3. Check: http://localhost/earnings-table/public/today-movements-table.html\n\n";
?> 