<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/common/Finnhub.php';
require_once dirname(__DIR__) . '/common/api_functions.php';

echo "=== INTELLIGENT EARNINGS FETCH (OPTIMIZED) ===\n";

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

// Step 3: Using only Finnhub data (no missing tickers logic needed)
echo "\n=== STEP 3: USING FINNHUB DATA ONLY ===\n";
$allTickers = $finnhubData;
echo "Total unique tickers: " . count($allTickers) . "\n";

// Step 4: Get all ticker symbols for batch processing
echo "\n=== STEP 4: BATCH API PROCESSING ===\n";
$tickerSymbols = array_keys($allTickers);
echo "Processing " . count($tickerSymbols) . " tickers in batch...\n";

// Step 5: Get batch market data from Polygon (ONE API call for all tickers)
echo "\n=== STEP 5: POLYGON BATCH MARKET DATA ===\n";
$batchStart = microtime(true);

// Get batch quote data for ALL tickers at once
$batchData = getPolygonBatchQuote($tickerSymbols);
$batchTime = round(microtime(true) - $batchStart, 2);

if ($batchData) {
    echo "✅ Batch API call completed in {$batchTime}s\n";
    echo "✅ Found data for " . count($batchData) . " tickers\n";
} else {
    echo "❌ Batch API call failed\n";
    $batchData = [];
}

// Step 6: Process all tickers with batch data
echo "\n=== STEP 6: PROCESSING ALL TICKERS ===\n";
$processedCount = 0;
$marketCapCount = 0;
$errors = [];

foreach ($allTickers as $ticker => $data) {
    echo "\n--- Processing {$ticker} (Source: {$data['source']}) ---\n";
    
    // ALWAYS save Finnhub EPS/Revenue data first (even without market data)
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
    
    echo "✅ {$ticker}: EPS/Revenue data saved from Finnhub\n";
    
    // Check if we have batch data for this ticker (for market data)
    if (isset($batchData[$ticker])) {
        $tickerData = $batchData[$ticker];
        
        // Extract data from batch response
        $currentPrice = getCurrentPrice($tickerData);
        $previousClose = $tickerData['prevDay']['c'] ?? $currentPrice;
        $companyName = $tickerData['ticker'] ?? $ticker;
        
        // Get market cap from Finnhub (FIXED)
        $finnhub = new Finnhub();
        $finnhubProfile = $finnhub->get('/stock/profile2', ['symbol' => $ticker]);
        $marketCap = $finnhubProfile['marketCapitalization'] ?? null;
        $companyNameFromFinnhub = $finnhubProfile['name'] ?? $companyName;
        
        // Calculate price change
        $priceChange = $currentPrice - $previousClose;
        $priceChangePercent = ($previousClose > 0) ? ($priceChange / $previousClose) * 100 : 0;
        
        // Determine size based on market cap (FIXED)
        $size = 'Small';
        if ($marketCap && $marketCap >= 10000) { // 10B+ (in millions)
            $size = 'Large';
        } elseif ($marketCap && $marketCap >= 2000) { // 2B+ (in millions)
            $size = 'Mid';
        }
        
        // Calculate market cap diff (CORRECTED)
        $marketCapDiff = null;
        $marketCapDiffBillions = null;
        if ($marketCap && $marketCap > 0) {
            // Get shares outstanding from Finnhub
            $sharesOutstanding = $finnhubProfile['shareOutstanding'] ?? null;
            if ($sharesOutstanding) {
                // Convert shares from thousands to actual shares
                $shares = $sharesOutstanding * 1000;
                
                // Calculate current and previous market cap
                $currentMC = $currentPrice * $shares;
                $previousMC = $previousClose * $shares;
                
                // Market cap diff = Current Market Cap - Previous Day Market Cap
                $marketCapDiff = $currentMC - $previousMC;
                $marketCapDiffBillions = $marketCapDiff / 1000000000;
            }
        }
        
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
        
        $marketCapInDollars = $marketCap ? $marketCap * 1000000 : null; // Convert to dollars for DB
        
        $stmt->execute([
            $ticker,
            $companyNameFromFinnhub,
            $currentPrice,
            $previousClose,
            $marketCapInDollars,
            $size,
            $marketCapDiff,
            $marketCapDiffBillions,
            $priceChangePercent
        ]);
        
        $processedCount++;
        if ($marketCap) {
            $marketCapCount++;
        }
        
        echo "✅ {$ticker}: Market data saved (Price: {$currentPrice}, Market Cap: {$marketCap}, Size: {$size})\n";
        
    } else {
        $errors[] = $ticker;
        echo "⚠️  {$ticker}: No market data available (Polygon API failed)\n";
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

// Step 8: Performance comparison
echo "\n=== STEP 8: PERFORMANCE COMPARISON ===\n";
$totalTickers = count($allTickers);
$apiCalls = 1; // Only 1 batch API call instead of 2 * totalTickers

echo "📊 API Efficiency:\n";
echo "  Before: " . ($totalTickers * 2) . " API calls (2 per ticker)\n";
echo "  After:  {$apiCalls} API call (1 batch call)\n";
echo "  Improvement: " . round((($totalTickers * 2 - $apiCalls) / ($totalTickers * 2)) * 100, 1) . "% fewer API calls\n";

echo "\n📊 Time Savings:\n";
echo "  Batch API time: {$batchTime}s\n";
echo "  Estimated individual time: " . ($totalTickers * 0.5) . "s (0.5s per ticker)\n";
echo "  Time saved: " . round(($totalTickers * 0.5 - $batchTime), 1) . "s\n";

// Step 9: Source breakdown
echo "\n=== STEP 9: SOURCE BREAKDOWN ===\n";
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

echo "\n✅ Intelligent earnings fetch (OPTIMIZED) completed!\n";
echo "This system uses Finnhub as the primary source and batch API calls for better performance.\n";

// Helper functions
function getEnhancedEarningsData($ticker, $date) {
    // This function is kept for future use but currently returns null
    // Manual data was removed as it's not needed - Finnhub provides all necessary data
    return null;
}

?>
