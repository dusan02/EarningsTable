<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/common/Finnhub.php';
require_once dirname(__DIR__) . '/common/YahooFinance.php';
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

// Step 2: Get tickers from Yahoo Finance (secondary source)
echo "\n=== STEP 2: YAHOO FINANCE (SECONDARY SOURCE) ===\n";
$yahooTickers = [];
$yahooData = [];
try {
    $yahoo = new YahooFinance();
    $result = $yahoo->getEarningsCalendar($date);
    
    if (isset($result['earnings'])) {
        foreach ($result['earnings'] as $earning) {
            $symbol = $earning['symbol'] ?? '';
            if (!empty($symbol)) {
                $yahooData[$symbol] = [
                    'eps_estimate' => $earning['eps_estimate'] ?? null,
                    'revenue_estimate' => $earning['revenue_estimate'] ?? null,
                    'report_time' => $earning['report_time'] ?? 'TNS',
                    'source' => 'Yahoo Finance'
                ];
            }
        }
        echo "✅ Yahoo Finance: " . count($yahooData) . " tickers\n";
    } else {
        echo "❌ Yahoo Finance: No data or error\n";
    }
} catch (Exception $e) {
    echo "❌ Yahoo Finance error: " . $e->getMessage() . "\n";
}

// Step 3: Find missing tickers (in Yahoo but not in Finnhub)
echo "\n=== STEP 3: FINDING MISSING TICKERS ===\n";
$missingTickers = [];
foreach ($yahooData as $ticker => $data) {
    if (!isset($finnhubData[$ticker])) {
        $missingTickers[$ticker] = $data;
        echo "✅ Found missing ticker: {$ticker} (EPS: {$data['eps_estimate']}, Revenue: {$data['revenue_estimate']})\n";
    }
}

echo "Total missing tickers: " . count($missingTickers) . "\n";

// Step 4: Combine all tickers
echo "\n=== STEP 4: COMBINING ALL TICKERS ===\n";
$allTickers = array_merge($finnhubData, $missingTickers);
echo "Total unique tickers: " . count($allTickers) . "\n";

// Step 5: Get EPS/Revenue data for missing tickers from their respective sources
echo "\n=== STEP 5: GETTING EPS/REVENUE DATA FOR MISSING TICKERS ===\n";
foreach ($missingTickers as $ticker => $data) {
    echo "\n--- Processing missing ticker: {$ticker} ---\n";
    
    // Try to get enhanced EPS/Revenue data from Yahoo Finance
    $enhancedData = getEnhancedEarningsData($ticker, $date);
    
    if ($enhancedData) {
        $allTickers[$ticker] = array_merge($data, $enhancedData);
        echo "✅ Enhanced data: EPS: {$enhancedData['eps_estimate']}, Revenue: {$enhancedData['revenue_estimate']}\n";
    } else {
        echo "⚠️  Using basic data from Yahoo Finance\n";
    }
}

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
        
        $sourcePriority = ($data['source'] === 'Finnhub') ? 1 : 2;
        $dataSource = ($data['source'] === 'Finnhub') ? 'finnhub' : 'yahoo_finance';
        
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
echo "This system uses Finnhub as primary source and Yahoo Finance as secondary source.\n";

// Helper functions
function getEnhancedEarningsData($ticker, $date) {
    // This function is kept for future use but currently returns null
    // Manual data was removed as it's not needed - Finnhub provides all necessary data
    return null;
}

?>
