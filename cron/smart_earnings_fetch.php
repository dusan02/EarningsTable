<?php
require_once '../config.php';
require_once '../common/Finnhub.php';
require_once '../common/api_functions.php';

echo "=== SMART EARNINGS FETCH ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// Step 1: Get tickers from Finnhub
echo "=== STEP 1: GETTING FINNHUB TICKERS ===\n";
try {
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $finnhubTickers = $response['earningsCalendar'] ?? [];
    
    $finnhubSymbols = [];
    foreach ($finnhubTickers as $earning) {
        $symbol = $earning['symbol'] ?? '';
        if (!empty($symbol)) {
            $finnhubSymbols[] = $symbol;
        }
    }
    
    echo "Finnhub tickers: " . count($finnhubSymbols) . "\n";
    echo "Sample: " . implode(', ', array_slice($finnhubSymbols, 0, 10)) . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Finnhub error: " . $e->getMessage() . "\n";
    $finnhubSymbols = [];
}

// Step 2: Define known missing tickers (from external sources)
echo "=== STEP 2: CHECKING KNOWN MISSING TICKERS ===\n";
$knownMissingTickers = [
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

$missingTickers = [];
foreach ($knownMissingTickers as $ticker => $data) {
    if (!in_array($ticker, $finnhubSymbols)) {
        $missingTickers[$ticker] = $data;
        echo "✅ {$ticker} missing from Finnhub (found in: {$data['source']})\n";
    } else {
        echo "✅ {$ticker} found in Finnhub\n";
    }
}

// Step 3: Process all tickers (Finnhub + missing)
echo "\n=== STEP 3: PROCESSING ALL TICKERS ===\n";
$allTickers = array_merge($finnhubSymbols, array_keys($missingTickers));
echo "Total tickers to process: " . count($allTickers) . "\n";

$processedCount = 0;
$marketCapCount = 0;
$errors = [];

foreach ($allTickers as $ticker) {
    echo "\n--- Processing {$ticker} ---\n";
    
    // Get earnings data
    $epsEstimate = null;
    $revenueEstimate = null;
    $reportTime = 'TNS';
    
    // Check if it's from Finnhub
    foreach ($finnhubTickers as $earning) {
        if (($earning['symbol'] ?? '') === $ticker) {
            $epsEstimate = $earning['epsEstimate'] ?? null;
            $revenueEstimate = $earning['revenueEstimate'] ?? null;
            $reportTime = $earning['time'] ?? 'TNS';
            break;
        }
    }
    
    // Check if it's from missing tickers
    if (isset($missingTickers[$ticker])) {
        $epsEstimate = $missingTickers[$ticker]['eps_estimate'];
        $revenueEstimate = $missingTickers[$ticker]['revenue_estimate'];
        $reportTime = $missingTickers[$ticker]['report_time'];
    }
    
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
            $epsEstimate,
            $revenueEstimate,
            $reportTime
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

// Step 4: Summary
echo "\n=== STEP 4: SUMMARY ===\n";
echo "Total tickers processed: {$processedCount}\n";
echo "Tickers with market cap: {$marketCapCount}\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "Failed tickers: " . implode(', ', $errors) . "\n";
}

// Step 5: Final verification
echo "\n=== STEP 5: FINAL VERIFICATION ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM earningstickerstoday WHERE report_date = ?");
$stmt->execute([$date]);
$totalInDB = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM todayearningsmovements");
$totalMovements = $stmt->fetchColumn();

echo "Total in EarningsTickersToday: {$totalInDB}\n";
echo "Total in TodayEarningsMovements: {$totalMovements}\n";

// Check for BMO and BNS specifically
$stmt = $pdo->prepare("SELECT ticker FROM earningstickerstoday WHERE ticker IN ('BMO', 'BNS') AND report_date = ?");
$stmt->execute([$date]);
$bmoBnsInDB = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "BMO and BNS in database: " . implode(', ', $bmoBnsInDB) . "\n";

echo "\n✅ Smart earnings fetch completed!\n";
?>
