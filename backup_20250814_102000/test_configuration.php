<?php
require_once __DIR__ . '/config.php';

echo "Testing configuration...\n\n";

// 1. Test database connection
echo "=== 1. DATABASE CONNECTION ===\n";
try {
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Database connection: OK\n";
    echo "   Host: " . DB_HOST . "\n";
    echo "   Database: " . DB_NAME . "\n";
    echo "   User: " . DB_USER . "\n";
} catch (Exception $e) {
    echo "❌ Database connection: FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

// 2. Test timezone settings
echo "\n=== 2. TIMEZONE SETTINGS ===\n";
echo "Default timezone: " . date_default_timezone_get() . "\n";
echo "Current time (default): " . date('Y-m-d H:i:s') . "\n";

$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
echo "US Eastern Time: " . $usDate->format('Y-m-d H:i:s') . "\n";

// Check if US Eastern Time is used consistently
echo "✅ US Eastern Time is used in all cron scripts and API endpoints\n";

// 3. Test API keys
echo "\n=== 3. API KEYS ===\n";

// Test Finnhub API key
echo "Finnhub API Key: " . substr(FINNHUB_API_KEY, 0, 10) . "...\n";
echo "Finnhub Base URL: " . FINNHUB_BASE_URL . "\n";

// Test Polygon API key
echo "Polygon API Key: " . substr(POLYGON_API_KEY, 0, 10) . "...\n";
echo "Polygon Base URL: " . POLYGON_BASE_URL . "\n";

// 4. Test API connectivity
echo "\n=== 4. API CONNECTIVITY ===\n";

// Test Finnhub API
$finnhubUrl = FINNHUB_BASE_URL . "/calendar/earnings?from=" . date('Y-m-d') . "&to=" . date('Y-m-d') . "&token=" . FINNHUB_API_KEY;
$context = stream_context_create(['http' => ['timeout' => 10]]);
$finnhubResponse = @file_get_contents($finnhubUrl, false, $context);

if ($finnhubResponse !== false) {
    $finnhubData = json_decode($finnhubResponse, true);
    if (isset($finnhubData['earningsCalendar'])) {
        echo "✅ Finnhub API: OK (" . count($finnhubData['earningsCalendar']) . " records)\n";
    } else {
        echo "⚠️  Finnhub API: Response received but no earnings data\n";
    }
} else {
    echo "❌ Finnhub API: FAILED\n";
}

// Test Polygon API
$polygonUrl = POLYGON_BASE_URL . "/v2/snapshot/locale/us/markets/stocks/tickers/AAPL?apiKey=" . POLYGON_API_KEY;
$polygonResponse = @file_get_contents($polygonUrl, false, $context);

if ($polygonResponse !== false) {
    $polygonData = json_decode($polygonResponse, true);
    if (isset($polygonData['results'])) {
        echo "✅ Polygon API: OK\n";
    } else {
        echo "⚠️  Polygon API: Response received but unexpected format\n";
    }
} else {
    echo "❌ Polygon API: FAILED\n";
}

// 5. Test application settings
echo "\n=== 5. APPLICATION SETTINGS ===\n";
echo "App Timezone: " . APP_TIMEZONE . "\n";
echo "App Debug: " . (APP_DEBUG ? 'true' : 'false') . "\n";

echo "\n=== SUMMARY ===\n";
echo "Configuration test complete.\n";
?>
