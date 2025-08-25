<?php
/**
 * System Status Script
 * Shows comprehensive status of Earnings Table Module
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/error_handler.php';
require_once __DIR__ . '/../utils/api.php';
require_once __DIR__ . '/../utils/polygon_api.php';

echo "📊 EARNINGS TABLE MODULE - SYSTEM STATUS\n";
echo "========================================\n\n";

// 1. Database Connection
echo "🔌 DATABASE STATUS:\n";
echo "==================\n";
try {
    $pdo->query("SELECT 1");
    echo "✅ Database connection: OK\n";
} catch (PDOException $e) {
    logDatabaseError('connection_test', 'SELECT 1', [], $e->getMessage());
    displayError("Database connection: FAILED - " . $e->getMessage());
    exit(1);
}

// 2. Table Status
echo "\n📋 TABLE STATUS:\n";
echo "===============\n";

// EarningsTickersToday
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM EarningsTickersToday");
    $earningsCount = $stmt->fetch()['count'];
    echo "✅ EarningsTickersToday: {$earningsCount} records\n";
} catch (PDOException $e) {
    echo "❌ EarningsTickersToday: TABLE NOT FOUND\n";
}

// TodayEarningsMovements
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM TodayEarningsMovements");
    $movementsCount = $stmt->fetch()['count'];
    echo "✅ TodayEarningsMovements: {$movementsCount} records\n";
} catch (PDOException $e) {
    echo "❌ TodayEarningsMovements: TABLE NOT FOUND\n";
}

// 3. API Health Check
echo "\n🌐 API HEALTH CHECK:\n";
echo "==================\n";

// Finnhub API
echo "Testing Finnhub API...\n";
$finnhubUrl = FINNHUB_BASE_URL . "/calendar/earnings?from=" . date('Y-m-d') . "&to=" . date('Y-m-d') . "&token=" . FINNHUB_API_KEY;
$context = stream_context_create(['http' => ['timeout' => 10]]);
$response = @file_get_contents($finnhubUrl, false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data['earningsCalendar'])) {
        echo "✅ Finnhub API: OK (" . count($data['earningsCalendar']) . " earnings today)\n";
    } else {
        logApiError('Finnhub', $finnhubUrl, 'Invalid response format', ['response' => $response]);
        displayWarning("Finnhub API: RESPONSE ERROR");
    }
} else {
    echo "❌ Finnhub API: CONNECTION FAILED\n";
}

// Polygon API
echo "Testing Polygon API...\n";
$polygonUrl = POLYGON_BASE_URL . "/v2/snapshot/locale/us/markets/stocks/tickers/AAPL?apiKey=" . POLYGON_API_KEY;
$response = @file_get_contents($polygonUrl, false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data['ticker'])) {
        echo "✅ Polygon API: OK (AAPL price: $" . $data['ticker']['lastTrade']['p'] . ")\n";
    } else {
        logApiError('Polygon', $polygonUrl, 'Invalid response format', ['response' => $response]);
        displayWarning("Polygon API: RESPONSE ERROR");
    }
} else {
    echo "❌ Polygon API: CONNECTION FAILED\n";
}

// 4. Recent Data
echo "\n📈 RECENT DATA:\n";
echo "==============\n";

// Latest earnings
if ($earningsCount > 0) {
    $stmt = $pdo->query("SELECT * FROM EarningsTickersToday ORDER BY report_date DESC LIMIT 5");
    echo "Latest Earnings:\n";
    while ($row = $stmt->fetch()) {
        echo "- {$row['ticker']}: {$row['report_time']} ({$row['report_date']})\n";
    }
}

// Latest movements
if ($movementsCount > 0) {
    $stmt = $pdo->query("SELECT * FROM TodayEarningsMovements ORDER BY updated_at DESC LIMIT 5");
    echo "\nLatest Movements:\n";
    while ($row = $stmt->fetch()) {
        echo "- {$row['ticker']}: \${$row['current_price']} ({$row['size']}, {$row['price_change_percent']}%)\n";
    }
}

// 5. System Recommendations
echo "\n💡 SYSTEM RECOMMENDATIONS:\n";
echo "==========================\n";

if ($earningsCount == 0) {
    echo "⚠️  Run: php cron/fetch_earnings.php (no earnings data)\n";
}

if ($movementsCount == 0) {
    echo "⚠️  Run: php cron/update_movements.php (no movements data)\n";
}

if ($movementsCount > 0) {
    $stmt = $pdo->query("SELECT updated_at FROM TodayEarningsMovements ORDER BY updated_at DESC LIMIT 1");
    $lastUpdate = $stmt->fetch()['updated_at'];
    $timeDiff = time() - strtotime($lastUpdate);
    
    if ($timeDiff > 600) { // 10 minutes
        echo "⚠️  Movements data is " . round($timeDiff/60) . " minutes old\n";
        echo "   Run: php cron/update_movements.php\n";
    } else {
        echo "✅ Movements data is fresh (" . round($timeDiff/60) . " minutes old)\n";
    }
}

echo "\n🎯 SYSTEM STATUS: " . ($earningsCount > 0 && $movementsCount > 0 ? "OPERATIONAL" : "NEEDS ATTENTION") . "\n";
echo "========================================\n\n";
?> 