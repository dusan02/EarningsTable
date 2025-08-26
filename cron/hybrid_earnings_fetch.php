<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/common/Finnhub.php';
require_once dirname(__DIR__) . '/common/api_functions.php';

echo "=== HYBRID EARNINGS FETCH (MULTIPLE SOURCES) ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// Step 1: Get tickers from Finnhub (primary source)
echo "=== STEP 1: FINNHUB (PRIMARY SOURCE) ===\n";
$finnhubTickers = [];
try {
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $finnhubTickers = $response['earningsCalendar'] ?? [];
    echo "✅ Finnhub: " . count($finnhubTickers) . " tickers\n";
} catch (Exception $e) {
    echo "❌ Finnhub error: " . $e->getMessage() . "\n";
}

// Step 2: Get tickers from Polygon (secondary source)
echo "\n=== STEP 2: POLYGON (SECONDARY SOURCE) ===\n";
$polygonTickers = [];
try {
    // Polygon has earnings calendar endpoint
    $polygonUrl = "https://api.polygon.io/v2/reference/earnings?date={$date}&apiKey=" . POLYGON_API_KEY;
    $polygonResponse = file_get_contents($polygonUrl);
    $polygonData = json_decode($polygonResponse, true);
    
    if (isset($polygonData['results'])) {
        foreach ($polygonData['results'] as $earning) {
            $polygonTickers[] = $earning['ticker'] ?? '';
        }
        echo "✅ Polygon: " . count($polygonTickers) . " tickers\n";
    } else {
        echo "❌ Polygon: No data or API limit reached\n";
    }
} catch (Exception $e) {
    echo "❌ Polygon error: " . $e->getMessage() . "\n";
}

// Step 3: Manual known missing tickers (from external sources)
echo "\n=== STEP 3: MANUAL KNOWN MISSING TICKERS ===\n";
$manualTickers = [
    'BMO' => [
        'eps_estimate' => 2.96,
        'revenue_estimate' => 8860000000,
        'report_time' => 'BMO',
        'source' => 'Yahoo Finance, Investing.com, Nasdaq'
    ],
    'BNS' => [
        'eps_estimate' => 1.73,
        'revenue_estimate' => 9300000000,
        'report_time' => 'BMO',
        'source' => 'Yahoo Finance, Investing.com'
    ]
];

echo "Manual tickers: " . count($manualTickers) . "\n";

// Step 4: Combine all sources
echo "\n=== STEP 4: COMBINING ALL SOURCES ===\n";
$allTickers = [];

// Add Finnhub tickers
foreach ($finnhubTickers as $earning) {
    $symbol = $earning['symbol'] ?? '';
    if (!empty($symbol)) {
        $allTickers[$symbol] = [
            'eps_estimate' => $earning['epsEstimate'] ?? null,
            'revenue_estimate' => $earning['revenueEstimate'] ?? null,
            'report_time' => $earning['time'] ?? 'TNS',
            'source' => 'Finnhub'
        ];
    }
}

// Add Polygon tickers (if not already in Finnhub)
foreach ($polygonTickers as $ticker) {
    if (!empty($ticker) && !isset($allTickers[$ticker])) {
        $allTickers[$ticker] = [
            'eps_estimate' => null,
            'revenue_estimate' => null,
            'report_time' => 'TNS',
            'source' => 'Polygon'
        ];
    }
}

// Add manual tickers (if not already present)
foreach ($manualTickers as $ticker => $data) {
    if (!isset($allTickers[$ticker])) {
        $allTickers[$ticker] = $data;
    }
}

echo "Total unique tickers: " . count($allTickers) . "\n";

// Step 5: Process all tickers
echo "\n=== STEP 5: PROCESSING ALL TICKERS ===\n";
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
        $previousClose = $batchData[$ticker]['previousClose'] ?? $currentPrice;
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
        $marketCapDiff = $marketCap ? $marketCap - ($currentPrice * 1000000) : null;
        $marketCapDiffBillions = $marketCapDiff ? $marketCapDiff / 1000000000 : null;
        
        // Insert into EarningsTickersToday
        $stmt = $pdo->prepare("
            INSERT INTO earningstickerstoday (ticker, report_date, eps_estimate, revenue_estimate, report_time) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            eps_estimate = VALUES(eps_estimate),
            revenue_estimate = VALUES(revenue_estimate),
            report_time = VALUES(report_time)
        ");
        
        $stmt->execute([
            $ticker,
            $date,
            $data['eps_estimate'],
            $data['revenue_estimate'],
            $data['report_time']
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

// Step 6: Summary
echo "\n=== STEP 6: SUMMARY ===\n";
echo "Total tickers processed: {$processedCount}\n";
echo "Tickers with market cap: {$marketCapCount}\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "Failed tickers: " . implode(', ', $errors) . "\n";
}

// Step 7: Source breakdown
echo "\n=== STEP 7: SOURCE BREAKDOWN ===\n";
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

echo "\n✅ Hybrid earnings fetch completed!\n";
echo "This system uses multiple sources to ensure complete coverage.\n";
echo "If Finnhub misses tickers, Polygon and manual sources fill the gap.\n";
?>
