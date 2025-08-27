<?php
require_once 'config.php';
require_once 'common/api_functions.php';

echo "=== ADDING MISSING US TICKERS BMO AND BNS ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// Missing US tickers that should be added
$missingTickers = [
    'BMO' => [
        'eps_estimate' => 2.96,
        'revenue_estimate' => 8860000000, // 8.86B
        'report_time' => 'BMO' // Before Market Open
    ],
    'BNS' => [
        'eps_estimate' => 1.73,
        'revenue_estimate' => 9300000000, // 9.3B
        'report_time' => 'BMO' // Before Market Open
    ]
];

foreach ($missingTickers as $ticker => $data) {
    echo "=== PROCESSING {$ticker} ===\n";
    
    // Get market data from Polygon
    echo "Getting market data from Polygon...\n";
    $marketData = getPolygonTickerDetails($ticker);
    $batchData = getPolygonBatchQuote([$ticker]);
    
    if ($marketData && $batchData && isset($batchData[$ticker])) {
        $priceData = getCurrentPrice($batchData[$ticker]);
        $currentPrice = $priceData ? $priceData['price'] : null;
        $previousClose = $batchData[$ticker]['previousClose'] ?? $currentPrice;
        $marketCap = $marketData['market_cap'] ?? null;
        $companyName = $marketData['name'] ?? $ticker;
        
        if ($currentPrice === null) {
            echo "❌ No valid current price found for {$ticker}\n\n";
            continue;
        }
        
        echo "✅ Market data found:\n";
        echo "  Current Price: {$currentPrice}\n";
        echo "  Previous Close: {$previousClose}\n";
        echo "  Market Cap: {$marketCap}\n";
        echo "  Company Name: {$companyName}\n";
        
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
        echo "Inserting into EarningsTickersToday...\n";
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
        echo "Inserting into TodayEarningsMovements...\n";
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
        
        echo "✅ {$ticker} successfully added to database!\n\n";
        
    } else {
        echo "❌ Failed to get market data for {$ticker}\n\n";
    }
}

echo "=== VERIFICATION ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM earningstickerstoday WHERE ticker IN ('BMO', 'BNS') AND report_date = ?");
$stmt->execute([$date]);
$count = $stmt->fetchColumn();
echo "BMO and BNS in EarningsTickersToday: {$count}\n";

$stmt = $pdo->prepare("SELECT COUNT(*) FROM todayearningsmovements WHERE ticker IN ('BMO', 'BNS')");
$stmt->execute();
$count = $stmt->fetchColumn();
echo "BMO and BNS in TodayEarningsMovements: {$count}\n";

echo "\n=== FINAL CHECK ===\n";
$stmt = $pdo->prepare("SELECT ticker, current_price, market_cap, size FROM todayearningsmovements WHERE ticker IN ('BMO', 'BNS')");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo "{$row['ticker']}: Price: {$row['current_price']}, Market Cap: {$row['market_cap']}, Size: {$row['size']}\n";
}
?>
