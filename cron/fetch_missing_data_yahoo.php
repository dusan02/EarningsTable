<?php
/**
 * Fetch Missing Data from Yahoo Finance
 * For tickers that weren't found in Polygon API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Lock mechanism
$lock = new Lock('yahoo_missing_data');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 YAHOO FINANCE MISSING DATA FETCH STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n\n";
    
    // STEP 1: Get tickers with NULL prices or market cap
    echo "=== STEP 1: GETTING TICKERS WITH NULL DATA ===\n";
    
    $stmt = $pdo->prepare("
        SELECT ticker 
        FROM TodayEarningsMovements 
        WHERE current_price IS NULL OR market_cap IS NULL
    ");
    $stmt->execute();
    $missingTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($missingTickers) . " tickers with missing data\n";
    
    if (empty($missingTickers)) {
        echo "✅ No missing data found\n";
        exit(0);
    }
    
    // STEP 2: Fetch data from Yahoo Finance
    echo "\n=== STEP 2: FETCHING DATA FROM YAHOO FINANCE ===\n";
    
    $updatedCount = 0;
    $notFoundCount = 0;
    
    foreach ($missingTickers as $ticker) {
        echo "Processing {$ticker}... ";
        
        $yahooData = getYahooFinanceData($ticker);
        
        if ($yahooData) {
            // Update the database
            $stmt = $pdo->prepare("
                UPDATE TodayEarningsMovements 
                SET 
                    current_price = ?,
                    previous_close = ?,
                    market_cap = ?,
                    company_name = ?,
                    price_change_percent = ?,
                    size = ?,
                    updated_at = NOW()
                WHERE ticker = ?
            ");
            
            $stmt->execute([
                $yahooData['current_price'],
                $yahooData['previous_close'],
                $yahooData['market_cap'],
                $yahooData['company_name'],
                $yahooData['price_change_percent'],
                $yahooData['size'],
                $ticker
            ]);
            
            echo "✅ Updated\n";
            $updatedCount++;
        } else {
            echo "❌ Not found\n";
            $notFoundCount++;
        }
        
        // Small delay to avoid rate limits
        usleep(200000); // 0.2 second
    }
    
    // STEP 3: Final summary
    echo "\n=== STEP 3: FINAL SUMMARY ===\n";
    echo "📊 Tickers processed: " . count($missingTickers) . "\n";
    echo "📊 Successfully updated: {$updatedCount}\n";
    echo "📊 Not found: {$notFoundCount}\n";
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "⏱️  Total time: {$executionTime}s\n";
    echo "✅ YAHOO FINANCE MISSING DATA FETCH COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Get data from Yahoo Finance API
 */
function getYahooFinanceData($ticker) {
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?interval=1d&range=1d";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept: application/json',
                'Accept-Language: en-US,en;q=0.9'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['chart']['result'][0])) {
        return null;
    }
    
    $result = $data['chart']['result'][0];
    $meta = $result['meta'];
    
    $currentPrice = $meta['regularMarketPrice'] ?? 0;
    $previousClose = $meta['previousClose'] ?? 0;
    $marketCap = $meta['marketCap'] ?? null;
    $companyName = $meta['shortName'] ?? $ticker;
    
    // Calculate price change
    $priceChangePercent = null;
    if ($currentPrice > 0 && $previousClose > 0) {
        $priceChangePercent = (($currentPrice - $previousClose) / $previousClose) * 100;
    }
    
    // Determine size based on market cap
    $size = 'Unknown';
    if ($marketCap) {
        if ($marketCap > 10000000000) {
            $size = 'Large';
        } elseif ($marketCap > 1000000000) {
            $size = 'Mid';
        } else {
            $size = 'Small';
        }
    }
    
    return [
        'current_price' => $currentPrice,
        'previous_close' => $previousClose,
        'market_cap' => $marketCap,
        'company_name' => $companyName,
        'price_change_percent' => $priceChangePercent,
        'size' => $size
    ];
}
?>
