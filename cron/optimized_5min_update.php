<?php
/**
 * 🚀 OPTIMIZED 5-MINUTE UPDATE - BATCH PROCESSING
 * 
 * Optimalizovaný 5-minútový update pre actual hodnoty a ceny
 * - Hromadné získavanie actual hodnôt z Finnhub
 * - Hromadné aktualizácie cien z Polygon
 * - Minimalizácia API volaní
 * - Optimalizované databázové operácie
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';
require_once __DIR__ . '/../common/Finnhub.php';

// Lock mechanism
$lock = new Lock('optimized_5min_update');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 OPTIMIZED 5-MINUTE UPDATE STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n";
    echo "⏰ Time: " . $usDate->format('H:i:s') . " NY\n\n";
    
    // STEP 1: Get existing tickers from database
    echo "=== STEP 1: GETTING EXISTING TICKERS ===\n";
    
    $stmt = $pdo->prepare("SELECT ticker FROM TodayEarningsMovements");
    $stmt->execute();
    $existingTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($existingTickers)) {
        echo "❌ No tickers found for today\n";
        exit(1);
    }
    
    echo "✅ Found " . count($existingTickers) . " existing tickers\n";
    
    // STEP 2: Batch fetch actual values from Finnhub
    echo "\n=== STEP 2: FINNHUB ACTUAL VALUES UPDATE ===\n";
    
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $earningsData = $response['earningsCalendar'] ?? [];
    
    $actualUpdates = [];
    $epsActualCount = 0;
    $revenueActualCount = 0;
    
    foreach ($earningsData as $earning) {
        $ticker = $earning['symbol'] ?? '';
        if (empty($ticker) || !in_array($ticker, $existingTickers)) continue;
        
        $epsActual = $earning['epsActual'] ?? null;
        $revenueActual = $earning['revenueActual'] ?? null;
        
        // Only update if we have actual values
        if ($epsActual !== null || $revenueActual !== null) {
            $actualUpdates[$ticker] = [
                'eps_actual' => $epsActual,
                'revenue_actual' => $revenueActual
            ];
            
            if ($epsActual !== null) $epsActualCount++;
            if ($revenueActual !== null) $revenueActualCount++;
        }
    }
    
    echo "✅ Found actual values for " . count($actualUpdates) . " tickers\n";
    echo "   - EPS actual: {$epsActualCount}\n";
    echo "   - Revenue actual: {$revenueActualCount}\n";
    
    // STEP 3: Batch fetch price updates from Polygon
    echo "\n=== STEP 3: POLYGON PRICE UPDATES ===\n";
    
    $priceUpdates = [];
    $chunks = array_chunk($existingTickers, 100); // Polygon batch limit
    $totalPolygonCalls = 0;
    
    foreach ($chunks as $index => $tickerChunk) {
        echo "Processing price chunk " . ($index + 1) . "/" . count($chunks) . " (" . count($tickerChunk) . " tickers)...\n";
        
        $batchData = getPolygonBatchQuote($tickerChunk);
        if ($batchData && isset($batchData['tickers'])) {
            foreach ($batchData['tickers'] as $result) {
                $ticker = $result['ticker'];
                $currentPrice = getCurrentPrice($result);
                $previousClose = $result['prevDay']['c'] ?? 0;
                
                if ($currentPrice > 0) {
                    $priceChangePercent = null;
                    if ($currentPrice > 0 && $previousClose > 0) {
                        $priceChangePercent = (($currentPrice - $previousClose) / $previousClose) * 100;
                    }
                    
                    $priceUpdates[$ticker] = [
                        'current_price' => $currentPrice,
                        'previous_close' => $previousClose,
                        'price_change_percent' => $priceChangePercent
                    ];
                }
            }
        }
        $totalPolygonCalls++;
        
        // Rate limiting
        if ($index < count($chunks) - 1) {
            sleep(1);
        }
    }
    
    echo "✅ Updated prices for " . count($priceUpdates) . " tickers in {$totalPolygonCalls} API calls\n";
    
    // STEP 4: Batch database updates
    echo "\n=== STEP 4: BATCH DATABASE UPDATES ===\n";
    
    $totalUpdates = 0;
    
    // Prepare batch update statement
    $updateStmt = $pdo->prepare("
        UPDATE TodayEarningsMovements 
        SET 
            current_price = ?,
            previous_close = ?,
            price_change_percent = ?,
            eps_actual = ?,
            revenue_actual = ?,
            updated_at = NOW()
        WHERE ticker = ?
    ");
    
    foreach ($existingTickers as $ticker) {
        $actualData = $actualUpdates[$ticker] ?? null;
        $priceData = $priceUpdates[$ticker] ?? null;
        
        // Get current values from database
        $currentStmt = $pdo->prepare("
            SELECT eps_actual, revenue_actual, current_price, previous_close, price_change_percent 
            FROM TodayEarningsMovements 
            WHERE ticker = ?
        ");
        $currentStmt->execute([$ticker]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current) {
            // Merge updates with existing data
            $newEpsActual = $actualData['eps_actual'] ?? $current['eps_actual'];
            $newRevenueActual = $actualData['revenue_actual'] ?? $current['revenue_actual'];
            $newCurrentPrice = $priceData['current_price'] ?? $current['current_price'];
            $newPreviousClose = $priceData['previous_close'] ?? $current['previous_close'];
            $newPriceChangePercent = $priceData['price_change_percent'] ?? $current['price_change_percent'];
            
            // Only update if there are changes
            if ($newEpsActual !== $current['eps_actual'] ||
                $newRevenueActual !== $current['revenue_actual'] ||
                $newCurrentPrice !== $current['current_price'] ||
                $newPreviousClose !== $current['previous_close'] ||
                $newPriceChangePercent !== $current['price_change_percent']) {
                
                $updateStmt->execute([
                    $newCurrentPrice,
                    $newPreviousClose,
                    $newPriceChangePercent,
                    $newEpsActual,
                    $newRevenueActual,
                    $ticker
                ]);
                
                $totalUpdates++;
            }
        }
    }
    
    echo "✅ Updated {$totalUpdates} records in database\n";
    
    // STEP 5: Final summary
    echo "\n=== FINAL SUMMARY ===\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements");
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL");
    $stmt->execute();
    $withEpsActual = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE revenue_actual IS NOT NULL");
    $stmt->execute();
    $withRevenueActual = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE current_price > 0");
    $stmt->execute();
    $withPrices = $stmt->fetchColumn();
    
    echo "📊 Total records: {$totalRecords}\n";
    echo "📊 Records with EPS actual: {$withEpsActual}\n";
    echo "📊 Records with Revenue actual: {$withRevenueActual}\n";
    echo "📊 Records with prices: {$withPrices}\n";
    echo "🚀 Total API calls: " . (1 + $totalPolygonCalls) . "\n";
    echo "   - Finnhub: 1\n";
    echo "   - Polygon: {$totalPolygonCalls}\n";
    echo "📈 Records updated this run: {$totalUpdates}\n";
    
    // Show recent actual values
    echo "\n=== RECENT ACTUAL VALUES ===\n";
    $stmt = $pdo->prepare("
        SELECT ticker, eps_actual, revenue_actual, updated_at
        FROM TodayEarningsMovements 
        WHERE (eps_actual IS NOT NULL OR revenue_actual IS NOT NULL)
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentActuals = $stmt->fetchAll();
    
    foreach ($recentActuals as $actual) {
        $epsActual = $actual['eps_actual'] ?? 'N/A';
        $revenueActual = $actual['revenue_actual'] ?? 'N/A';
        $updatedTime = date('H:i:s', strtotime($actual['updated_at']));
        
        if ($revenueActual !== 'N/A') {
            $revenueActual = '$' . number_format($revenueActual / 1000000, 1) . 'M';
        }
        
        echo sprintf("%-6s | EPS: %-8s | Revenue: %-10s | %s\n",
            $actual['ticker'],
            $epsActual,
            $revenueActual,
            $updatedTime
        );
    }
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "\n⏱️  Update time: {$executionTime}s\n";
    echo "✅ OPTIMIZED 5-MINUTE UPDATE COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Get current price from Polygon data with fallback logic
 */
function getCurrentPrice($polygonData) {
    // Priority: last trade > last price > previous close
    if (isset($polygonData['lastTrade']['p']) && $polygonData['lastTrade']['p'] > 0) {
        return $polygonData['lastTrade']['p'];
    }
    
    if (isset($polygonData['last']['p']) && $polygonData['last']['p'] > 0) {
        return $polygonData['last']['p'];
    }
    
    if (isset($polygonData['prevDay']['c']) && $polygonData['prevDay']['c'] > 0) {
        return $polygonData['prevDay']['c'];
    }
    
    return 0;
}

/**
 * Get Polygon batch quote data
 */
function getPolygonBatchQuote($tickers) {
    $apiKey = POLYGON_API_KEY;
    $tickerString = implode(',', $tickers);
    $url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers?tickers={$tickerString}&apikey={$apiKey}";
    
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
    
    $json = file_get_contents($url, false, $context);
    
    if ($json === false) {
        return null;
    }
    
    return json_decode($json, true);
}
?>
