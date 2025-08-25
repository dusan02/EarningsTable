<?php
/**
 * 🚀 OPTIMIZED EARNINGS FETCH - BATCH PROCESSING
 * 
 * Optimalizovaný cron job pre hromadné získavanie earnings dát
 * - Jeden Finnhub API call pre všetky earnings
 * - Hromadné Polygon API calls pre ceny a market cap
 * - Hromadné databázové operácie
 * - Minimalizácia API volaní a optimalizácia výkonu
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';
require_once __DIR__ . '/../common/Finnhub.php';

// Lock mechanism
$lock = new Lock('optimized_earnings_fetch');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 OPTIMIZED EARNINGS FETCH STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n";
    echo "⏰ Time: " . $usDate->format('H:i:s') . " NY\n\n";
    
    // STEP 1: Get earnings calendar from Finnhub (ONE API CALL)
    echo "=== STEP 1: FINNHUB EARNINGS CALENDAR ===\n";
    
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $earningsData = $response['earningsCalendar'] ?? [];
    
    if (empty($earningsData)) {
        echo "❌ No earnings data found for today\n";
        exit(1);
    }
    
    echo "✅ Found " . count($earningsData) . " earnings reports today\n";
    
    // STEP 2: Process Finnhub data and prepare for batch operations
    echo "\n=== STEP 2: PROCESSING FINNHUB DATA ===\n";
    
    $earningsTickers = [];
    $tickersForPolygon = [];
    $processedCount = 0;
    
    foreach ($earningsData as $earning) {
        $ticker = $earning['symbol'] ?? '';
        if (empty($ticker)) continue;
        
        // Extract data from Finnhub response
        $epsEstimate = $earning['epsEstimate'] ?? null;
        $epsActual = $earning['epsActual'] ?? null;
        $revenueEstimate = $earning['revenueEstimate'] ?? null;
        $revenueActual = $earning['revenueActual'] ?? null;
        $quarter = $earning['quarter'] ?? null;
        $year = $earning['year'] ?? null;
        
        // Prepare data for database
        $earningsTickers[] = [
            'ticker' => $ticker,
            'company_name' => $ticker, // Will be updated with Polygon data
            'eps_estimate' => $epsEstimate,
            'eps_actual' => $epsActual,
            'revenue_estimate' => $revenueEstimate,
            'revenue_actual' => $revenueActual,
            'report_time' => 'TNS',
            'quarter' => $quarter,
            'year' => $year,
            'report_date' => $date
        ];
        
        // Add to Polygon fetch list
        $tickersForPolygon[] = $ticker;
        $processedCount++;
    }
    
    echo "✅ Processed {$processedCount} tickers from Finnhub\n";
    
    // STEP 3: Batch fetch market data from Polygon
    echo "\n=== STEP 3: POLYGON BATCH MARKET DATA ===\n";
    
    $polygonData = [];
    $chunks = array_chunk($tickersForPolygon, 100); // Polygon batch limit
    $totalPolygonCalls = 0;
    
    foreach ($chunks as $index => $tickerChunk) {
        echo "Processing Polygon chunk " . ($index + 1) . "/" . count($chunks) . " (" . count($tickerChunk) . " tickers)...\n";
        
        $batchData = getPolygonBatchQuote($tickerChunk);
        if ($batchData && isset($batchData['tickers'])) {
            foreach ($batchData['tickers'] as $result) {
                $ticker = $result['ticker'];
                $polygonData[$ticker] = $result;
            }
        }
        $totalPolygonCalls++;
        
        // Rate limiting - sleep between chunks
        if ($index < count($chunks) - 1) {
            sleep(1);
        }
    }
    
    echo "✅ Fetched market data for " . count($polygonData) . " tickers in {$totalPolygonCalls} API calls\n";
    
    // STEP 4: Batch fetch market cap data from Polygon
    echo "\n=== STEP 4: POLYGON BATCH MARKET CAP ===\n";
    
    $marketCapData = [];
    $tickersForMarketCap = array_keys($polygonData);
    $marketCapChunks = array_chunk($tickersForMarketCap, 10); // Smaller chunks for market cap
    $totalMarketCapCalls = 0;
    
    foreach ($marketCapChunks as $index => $tickerChunk) {
        echo "Processing market cap chunk " . ($index + 1) . "/" . count($marketCapChunks) . " (" . count($tickerChunk) . " tickers)...\n";
        
        foreach ($tickerChunk as $ticker) {
            $tickerDetails = getPolygonTickerDetails($ticker);
            if ($tickerDetails) {
                $marketCapData[$ticker] = $tickerDetails;
            }
        }
        $totalMarketCapCalls += count($tickerChunk);
        
        // Rate limiting
        if ($index < count($marketCapChunks) - 1) {
            sleep(1);
        }
    }
    
    echo "✅ Fetched market cap data for " . count($marketCapData) . " tickers in {$totalMarketCapCalls} API calls\n";
    
    // STEP 5: Merge all data and prepare for batch database operations
    echo "\n=== STEP 5: MERGING DATA ===\n";
    
    $finalData = [];
    $mergedCount = 0;
    
    foreach ($earningsTickers as $earning) {
        $ticker = $earning['ticker'];
        $polygonInfo = $polygonData[$ticker] ?? null;
        $marketCapInfo = $marketCapData[$ticker] ?? null;
        
        // Get price data
        $currentPrice = 0;
        $previousClose = 0;
        $priceChangePercent = null;
        
        if ($polygonInfo) {
            $currentPrice = getCurrentPrice($polygonInfo);
            $previousClose = $polygonInfo['prevDay']['c'] ?? 0;
            
            if ($currentPrice > 0 && $previousClose > 0) {
                $priceChangePercent = (($currentPrice - $previousClose) / $previousClose) * 100;
            }
        }
        
        // Get market cap data
        $marketCap = null;
        $size = 'Unknown';
        
        if ($marketCapInfo && isset($marketCapInfo['results'])) {
            $marketCap = $marketCapInfo['results']['market_cap'] ?? null;
            
            if ($marketCap) {
                if ($marketCap >= 100000000000) {
                    $size = 'Large';
                } elseif ($marketCap >= 10000000000) {
                    $size = 'Mid';
                } else {
                    $size = 'Small';
                }
            }
        }
        
        // Calculate market cap diff
        $marketCapDiff = null;
        $marketCapDiffBillions = null;
        if ($priceChangePercent !== null && $marketCap && $marketCap > 0) {
            $marketCapDiff = ($priceChangePercent / 100) * $marketCap;
            $marketCapDiffBillions = $marketCapDiff / 1000000000;
        }
        
        // Get company name from Polygon data
        $companyName = $ticker; // Default to ticker
        if ($marketCapInfo && isset($marketCapInfo['results'])) {
            $companyName = $marketCapInfo['results']['name'] ?? $ticker;
        }
        
        // Prepare final data
        $finalData[] = [
            'ticker' => $ticker,
            'company_name' => $companyName,
            'current_price' => $currentPrice,
            'previous_close' => $previousClose,
            'price_change_percent' => $priceChangePercent,
            'market_cap' => $marketCap,
            'size' => $size,
            'market_cap_diff' => $marketCapDiff,
            'market_cap_diff_billions' => $marketCapDiffBillions,
            'eps_estimate' => $earning['eps_estimate'],
            'eps_actual' => $earning['eps_actual'],
            'revenue_estimate' => $earning['revenue_estimate'],
            'revenue_actual' => $earning['revenue_actual'],
            'report_time' => $earning['report_time'],
            'quarter' => $earning['quarter'],
            'year' => $earning['year'],
            'report_date' => $date
        ];
        
        $mergedCount++;
    }
    
    echo "✅ Merged data for {$mergedCount} tickers\n";
    
    // STEP 6: Batch database operations
    echo "\n=== STEP 6: BATCH DATABASE OPERATIONS ===\n";
    
    if (!empty($finalData)) {
        // Batch insert into EarningsTickersToday
        $earningsStmt = $pdo->prepare("
            INSERT INTO EarningsTickersToday (
                ticker, eps_estimate, revenue_estimate, report_date, report_time
            ) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                eps_estimate = VALUES(eps_estimate),
                revenue_estimate = VALUES(revenue_estimate),
                report_time = VALUES(report_time)
        ");
        
        $earningsInserted = 0;
        foreach ($finalData as $data) {
            $earningsStmt->execute([
                $data['ticker'],
                $data['eps_estimate'],
                $data['revenue_estimate'],
                $data['report_date'],
                $data['report_time']
            ]);
            $earningsInserted++;
        }
        echo "✅ Inserted {$earningsInserted} records into EarningsTickersToday\n";
        
        // Batch insert into TodayEarningsMovements
        $movementsStmt = $pdo->prepare("
            INSERT INTO TodayEarningsMovements (
                ticker, company_name, current_price, previous_close, price_change_percent,
                market_cap, size, market_cap_diff, market_cap_diff_billions,
                eps_estimate, eps_actual, revenue_estimate, revenue_actual,
                report_time, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                current_price = VALUES(current_price),
                previous_close = VALUES(previous_close),
                price_change_percent = VALUES(price_change_percent),
                market_cap = VALUES(market_cap),
                size = VALUES(size),
                market_cap_diff = VALUES(market_cap_diff),
                market_cap_diff_billions = VALUES(market_cap_diff_billions),
                eps_estimate = VALUES(eps_estimate),
                eps_actual = VALUES(eps_actual),
                revenue_estimate = VALUES(revenue_estimate),
                revenue_actual = VALUES(revenue_actual),
                report_time = VALUES(report_time),
                updated_at = NOW()
        ");
        
        $movementsInserted = 0;
        foreach ($finalData as $data) {
            $movementsStmt->execute([
                $data['ticker'],
                $data['company_name'],
                $data['current_price'],
                $data['previous_close'],
                $data['price_change_percent'],
                $data['market_cap'],
                $data['size'],
                $data['market_cap_diff'],
                $data['market_cap_diff_billions'],
                $data['eps_estimate'],
                $data['eps_actual'],
                $data['revenue_estimate'],
                $data['revenue_actual'],
                $data['report_time']
            ]);
            $movementsInserted++;
        }
        echo "✅ Inserted {$movementsInserted} records into TodayEarningsMovements\n";
    }
    
    // STEP 7: Final summary
    echo "\n=== FINAL SUMMARY ===\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements");
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE current_price > 0");
    $stmt->execute();
    $withPrices = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE market_cap > 0");
    $stmt->execute();
    $withMarketCap = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL");
    $stmt->execute();
    $withEpsActual = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE revenue_actual IS NOT NULL");
    $stmt->execute();
    $withRevenueActual = $stmt->fetchColumn();
    
    echo "📊 Total records: {$totalRecords}\n";
    echo "📊 Records with prices: {$withPrices}\n";
    echo "📊 Records with market cap: {$withMarketCap}\n";
    echo "📊 Records with EPS actual: {$withEpsActual}\n";
    echo "📊 Records with Revenue actual: {$withRevenueActual}\n";
    echo "🚀 Total API calls: " . (1 + $totalPolygonCalls + $totalMarketCapCalls) . "\n";
    echo "   - Finnhub: 1\n";
    echo "   - Polygon quotes: {$totalPolygonCalls}\n";
    echo "   - Polygon market cap: {$totalMarketCapCalls}\n";
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "⏱️  Total execution time: {$executionTime}s\n";
    echo "✅ OPTIMIZED EARNINGS FETCH COMPLETED\n";
    
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

/**
 * Get Polygon ticker details for market cap
 */
function getPolygonTickerDetails($ticker) {
    $apiKey = POLYGON_API_KEY;
    $url = "https://api.polygon.io/v3/reference/tickers/{$ticker}?apikey={$apiKey}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
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
