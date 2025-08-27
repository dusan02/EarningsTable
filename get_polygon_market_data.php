<?php
require_once 'config.php';
require_once 'common/Finnhub.php';
require_once 'common/api_functions.php';

echo "=== POLYGON MARKET DATA FETCH ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// STEP 1: Get tickers from Finnhub (same as before)
echo "=== STEP 1: GET EARNINGS TICKERS ===\n";
$finnhubTickers = [];
$finnhubData = [];
try {
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $finnhubTickers = $response['earningsCalendar'] ?? [];
    
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
    
    echo "✅ Found " . count($finnhubTickers) . " tickers with earnings today\n";
} catch (Exception $e) {
    echo "❌ Finnhub error: " . $e->getMessage() . "\n";
    exit(1);
}

// STEP 2: Get ticker symbols for Polygon API
echo "\n=== STEP 2: PREPARE FOR POLYGON API ===\n";
$tickerSymbols = array_keys($finnhubData);
echo "Processing " . count($tickerSymbols) . " tickers for market data...\n";

// STEP 3: Get batch market data from Polygon
echo "\n=== STEP 3: POLYGON BATCH MARKET DATA ===\n";
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

// STEP 4: Process and save market data
echo "\n=== STEP 4: PROCESSING MARKET DATA ===\n";
$processedCount = 0;
$marketCapCount = 0;
$errors = [];

foreach ($finnhubData as $ticker => $data) {
    echo "\n--- Processing {$ticker} ---\n";
    
    // Check if we have batch data for this ticker
    if (isset($batchData[$ticker])) {
        $tickerData = $batchData[$ticker];
        
        // Extract data from batch response
        $currentPrice = getCurrentPrice($tickerData);
        $previousClose = $tickerData['prevDay']['c'] ?? $currentPrice;
        $marketCap = $tickerData['market']['market_cap'] ?? null;
        $companyName = $tickerData['ticker'] ?? $ticker;
        
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
        
        echo "✅ {$ticker}: Market data saved (Price: {$currentPrice}, Market Cap: {$marketCap}, Size: {$size})\n";
        
    } else {
        $errors[] = $ticker;
        echo "⚠️  {$ticker}: No market data available (Polygon API failed)\n";
    }
}

// STEP 5: Summary
echo "\n=== STEP 5: SUMMARY ===\n";
echo "Total tickers processed: {$processedCount}\n";
echo "Tickers with market cap: {$marketCapCount}\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "Failed tickers: " . implode(', ', $errors) . "\n";
}

echo "\n=== PERFORMANCE ===\n";
$totalTickers = count($finnhubData);
$apiCalls = 1; // Only 1 batch API call

echo "📊 API Efficiency:\n";
echo "  Before: " . ($totalTickers * 2) . " API calls (2 per ticker)\n";
echo "  After:  {$apiCalls} API call (1 batch call)\n";
echo "  Improvement: " . round((($totalTickers * 2 - $apiCalls) / ($totalTickers * 2)) * 100, 1) . "% fewer API calls\n";

echo "\n📊 Time Savings:\n";
echo "  Batch API time: {$batchTime}s\n";
echo "  Estimated individual time: " . ($totalTickers * 0.5) . "s (0.5s per ticker)\n";
echo "  Time saved: " . round(($totalTickers * 0.5 - $batchTime), 1) . "s\n";

echo "\n✅ Polygon market data fetch completed!\n";
echo "📊 Market data saved to todayearningsmovements table\n";
?>
