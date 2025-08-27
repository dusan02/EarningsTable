<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/common/Finnhub.php';
require_once dirname(__DIR__) . '/common/api_functions.php';

echo "=== INTELLIGENT EARNINGS FETCH ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// Step 1: Get tickers from Finnhub (primary source)
echo "=== STEP 1: FINNHUB (PRIMARY SOURCE) ===\n";
$finnhubTickers = [];
$finnhubData = [];
try {
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $finnhubTickers = $response['earningsCalendar'] ?? [];
    
    // Store Finnhub data with EPS/Revenue estimates
    foreach ($finnhubTickers as $earning) {
        $symbol = $earning['symbol'] ?? '';
        if (!empty($symbol)) {
            $finnhubData[$symbol] = [
                'eps_estimate' => $earning['epsEstimate'] ?? null,
                'revenue_estimate' => $earning['revenueEstimate'] ?? null,
                'report_time' => $earning['time'] ?? 'TNS',
                'source' => 'Finnhub'
            ];
        }
    }
    
    echo "✅ Finnhub: " . count($finnhubTickers) . " tickers with EPS/Revenue data\n";
} catch (Exception $e) {
    echo "❌ Finnhub error: " . $e->getMessage() . "\n";
}

// Step 2: Yahoo Finance removed - using only Finnhub as primary source
echo "\n=== STEP 2: YAHOO FINANCE REMOVED ===\n";
echo "✅ Using only Finnhub as primary source for better stability\n";
$yahooTickers = [];
$yahooData = [];

// Step 3: Using only Finnhub data (no missing tickers logic needed)
echo "\n=== STEP 3: USING FINNHUB DATA ONLY ===\n";
$allTickers = $finnhubData;
echo "Total unique tickers: " . count($allTickers) . "\n";

// Step 6: Process all tickers with market data
echo "\n=== STEP 6: PROCESSING ALL TICKERS ===\n";
$processedCount = 0;
$marketCapCount = 0;
$errors = [];

foreach ($allTickers as $ticker => $data) {
    echo "\n--- Processing {$ticker} (Source: {$data['source']}) ---\n";
    
    // Get market data from Polygon
    $marketData = getPolygonTickerDetails($ticker);
    $batchData = getPolygonBatchQuote([$ticker]);
    
    if ($marketData && $batchData && isset($batchData[$ticker])) {
        $currentPrice = getCurrentPrice($batchData[$ticker]);
        $previousClose = $batchData[$ticker]['prevDay']['c'] ?? $currentPrice;
        $marketCap = $marketData['market_cap'] ?? null;
        $companyName = $marketData['name'] ?? $ticker;
        
        // Calculate price change
        $priceChange = $currentPrice - $previousClose;
        $priceChangePercent = ($previousClose > 0) ? ($priceChange / $previousClose) * 100 : 0;
        
        // Determine size based on market cap
        $size = 'Small';
        if ($marketCap >= 10000000000) { // 10B+
            $size = 'Large';
        } elseif ($marketCap >= 2000000000) { // 2B+
            $size = 'Mid';
        }
        
        // Calculate market cap diff
        $marketCapDiff = null;
        $marketCapDiffBillions = null;
        if ($priceChangePercent !== null && $marketCap && $marketCap > 0) {
            $marketCapDiff = ($priceChangePercent / 100) * $marketCap;
            $marketCapDiffBillions = $marketCapDiff / 1000000000;
        }
        
        // Insert into EarningsTickersToday with data source
        $stmt = $pdo->prepare("
            INSERT INTO earningstickerstoday (ticker, report_date, eps_estimate, revenue_estimate, report_time, data_source, source_priority) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            eps_estimate = VALUES(eps_estimate),
            revenue_estimate = VALUES(revenue_estimate),
            report_time = VALUES(report_time),
            data_source = VALUES(data_source),
            source_priority = VALUES(source_priority)
        ");
        
        $sourcePriority = 1; // Only Finnhub now
        $dataSource = 'finnhub';
        
        $stmt->execute([
            $ticker,
            $date,
            $data['eps_estimate'],
            $data['revenue_estimate'],
            $data['report_time'],
            $dataSource,
            $sourcePriority
        ]);
        
        // Insert into TodayEarningsMovements
        $stmt = $pdo->prepare("
            INSERT INTO todayearningsmovements (
                ticker, company_name, current_price, previous_close, market_cap, size,
                market_cap_diff, market_cap_diff_billions, price_change_percent, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
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
        
        $processedCount++;
        if ($marketCap) {
            $marketCapCount++;
        }
        
        echo "✅ {$ticker}: Price: {$currentPrice}, Market Cap: {$marketCap}, Size: {$size}\n";
        
    } else {
        $errors[] = $ticker;
        echo "❌ {$ticker}: Failed to get market data\n";
    }
}

// Step 7: Summary
echo "\n=== STEP 7: SUMMARY ===\n";
echo "Total tickers processed: {$processedCount}\n";
echo "Tickers with market cap: {$marketCapCount}\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "Failed tickers: " . implode(', ', $errors) . "\n";
}

// Step 8: Source breakdown
echo "\n=== STEP 8: SOURCE BREAKDOWN ===\n";
$sourceCount = [];
foreach ($allTickers as $ticker => $data) {
    $source = $data['source'];
    if (!isset($sourceCount[$source])) {
        $sourceCount[$source] = 0;
    }
    $sourceCount[$source]++;
}

foreach ($sourceCount as $source => $count) {
    echo "{$source}: {$count} tickers\n";
}

echo "\n✅ Intelligent earnings fetch completed!\n";
echo "This system uses Finnhub as the primary and only source for better stability.\n";

// Helper functions
function getEnhancedEarningsData($ticker, $date) {
    // This function is kept for future use but currently returns null
    // Manual data was removed as it's not needed - Finnhub provides all necessary data
    return null;
}

?>
