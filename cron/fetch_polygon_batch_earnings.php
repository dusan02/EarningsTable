<?php
/**
 * Polygon Batch Earnings Cron
 * Fetch market data for earnings tickers from Polygon with specific logic
 * - company_name (short name)
 * - market cap (current calculation)
 * - market cap diff (percentage change * previous market cap)
 * - size classification (Large/Mid/Small)
 * - price change (last trade > last price > previous close)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Lock mechanism
$lock = new Lock('polygon_batch_earnings');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 POLYGON BATCH EARNINGS CRON STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n\n";
    
    // STEP 1: Get earnings tickers from database
    echo "=== STEP 1: GETTING EARNINGS TICKERS ===\n";
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $earningsTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($earningsTickers) . " earnings tickers to fetch from Polygon\n";
    
    if (empty($earningsTickers)) {
        echo "❌ No earnings tickers found\n";
        exit(1);
    }
    
    // STEP 2: Fetch data from Polygon in batch
    echo "=== STEP 2: POLYGON BATCH FETCH ===\n";
    
    // Split tickers into chunks of 100 (Polygon batch limit)
    $chunks = array_chunk($earningsTickers, 100);
    $totalFetched = 0;
    $totalWithMarketCap = 0;
    
    foreach ($chunks as $index => $tickerChunk) {
        echo "Processing chunk " . ($index + 1) . "/" . count($chunks) . " (" . count($tickerChunk) . " tickers)...\n";
        
        $polygonData = getPolygonBatchQuote($tickerChunk);
        
        if ($polygonData && isset($polygonData['tickers'])) {
            foreach ($polygonData['tickers'] as $result) {
                $ticker = $result['ticker'];
                
                // Get price data with fallback logic
                $currentPrice = getCurrentPrice($result);
                $previousClose = $result['prevDay']['c'] ?? 0;
                $marketCap = null; // Polygon snapshot doesn't provide market cap
                
                if ($currentPrice > 0) {
                    // Calculate price change percentage
                    $priceChangePercent = null;
                    if ($currentPrice > 0 && $previousClose > 0) {
                        $priceChangePercent = (($currentPrice - $previousClose) / $previousClose) * 100;
                    }
                    
                    // Calculate market cap diff (percentage change * previous market cap)
                    $marketCapDiff = null;
                    $marketCapDiffBillions = null;
                    if ($marketCap && $priceChangePercent !== null) {
                        $marketCapDiff = ($priceChangePercent / 100) * $marketCap;
                        $marketCapDiffBillions = $marketCapDiff / 1000000000;
                    }
                    
                    // Determine size based on market cap
                    $size = 'Unknown';
                    if ($marketCap) {
                        if ($marketCap >= 100000000000) { // 100+ billion
                            $size = 'Large';
                        } elseif ($marketCap >= 10000000000) { // 10+ billion
                            $size = 'Mid';
                        } else { // < 10 billion
                            $size = 'Small';
                        }
                        $totalWithMarketCap++;
                    }
                    
                    // Get short company name (use ticker for now)
                    $companyName = $ticker;
                    
                    // Insert/Update database
                    $stmt = $pdo->prepare("
                        INSERT INTO TodayEarningsMovements (
                            ticker, company_name, current_price, previous_close,
                            market_cap, size, market_cap_diff, market_cap_diff_billions,
                            price_change_percent, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE
                            company_name = VALUES(company_name),
                            current_price = VALUES(current_price),
                            previous_close = VALUES(previous_close),
                            market_cap = VALUES(market_cap),
                            size = VALUES(size),
                            market_cap_diff = VALUES(market_cap_diff),
                            market_cap_diff_billions = VALUES(market_cap_diff_billions),
                            price_change_percent = VALUES(price_change_percent),
                            updated_at = NOW()
                    ");
                    
                    $stmt->execute([
                        $ticker,
                        $companyName,
                        $currentPrice,
                        $previousClose,
                        $marketCap,
                        $size,
                        $marketCapDiff,
                        $marketCapDiffBillions,
                        $priceChangePercent
                    ]);
                    
                    $totalFetched++;
                    echo "  ✅ {$ticker}: $" . number_format($currentPrice, 2);
                    if ($marketCap) {
                        echo " (MC: $" . number_format($marketCap / 1000000000, 1) . "B, {$size})";
                    }
                    if ($priceChangePercent !== null) {
                        echo " (" . number_format($priceChangePercent, 2) . "%)";
                    }
                    echo "\n";
                }
            }
        }
        
        // Small delay between chunks
        if ($index < count($chunks) - 1) {
            echo "Sleeping 1 second...\n";
            sleep(1);
        }
    }
    
    echo "✅ Polygon batch completed\n\n";
    
    // FINAL SUMMARY
    echo "=== FINAL SUMMARY ===\n";
    
    // Count records
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements");
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE market_cap > 0");
    $stmt->execute();
    $withMarketCap = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE price_change_percent IS NOT NULL");
    $stmt->execute();
    $withPriceChange = $stmt->fetchColumn();
    
    echo "📊 Total records in TodayEarningsMovements: {$totalRecords}\n";
    echo "📊 Records with market cap: {$withMarketCap}\n";
    echo "📊 Records with price change: {$withPriceChange}\n";
    echo "📊 Tickers fetched from Polygon: {$totalFetched}\n";
    echo "📊 Tickers with market cap: {$totalWithMarketCap}\n";
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "⏱️  Total time: {$executionTime}s\n";
    echo "✅ POLYGON BATCH EARNINGS CRON COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Get current price with fallback logic:
 * 1. Last trade price
 * 2. Last price from market
 * 3. Previous close price
 */
function getCurrentPrice($result) {
    // Try last trade price first
    if (isset($result['lastTrade']['p']) && $result['lastTrade']['p'] > 0) {
        return $result['lastTrade']['p'];
    }
    
    // Try last price from market
    if (isset($result['last']['p']) && $result['last']['p'] > 0) {
        return $result['last']['p'];
    }
    
    // Fallback to previous close
    if (isset($result['prevDay']['c']) && $result['prevDay']['c'] > 0) {
        return $result['prevDay']['c'];
    }
    
    return 0;
}

/**
 * Get short company name from Polygon data
 */
function getShortCompanyName($result) {
    // Try to get company name from Polygon data
    if (isset($result['name']) && !empty($result['name'])) {
        $name = $result['name'];
        
        // Remove common suffixes
        $name = preg_replace('/\s+(Inc\.|Corp\.|Corporation|Company|Co\.|Ltd\.|Limited|LLC|L\.L\.C\.)$/i', '', $name);
        
        // Limit length
        if (strlen($name) > 30) {
            $name = substr($name, 0, 27) . '...';
        }
        
        return $name;
    }
    
    // Fallback to ticker
    return $result['ticker'];
}

/**
 * Get batch quote from Polygon API
 */
function getPolygonBatchQuote($tickers) {
    $apiKey = POLYGON_API_KEY;
    $tickerList = implode(',', $tickers);
    
    $url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers?tickers={$tickerList}&apiKey={$apiKey}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => [
                'User-Agent: EarningsTable/1.0',
                'Accept: application/json'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['tickers'])) {
        return null;
    }
    
    return $data;
}
?>
