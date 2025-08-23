<?php
/**
 * Polygon Ticker Details Market Cap Batch Fetch
 * Fetch market cap for tickers using Polygon Ticker Details endpoint with concurrent requests
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Lock mechanism
$lock = new Lock('polygon_details_batch');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 POLYGON TICKER DETAILS BATCH MARKET CAP FETCH STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n\n";
    
    // STEP 1: Get tickers that have prices but no market cap
    echo "=== STEP 1: GETTING TICKERS WITH PRICES BUT NO MARKET CAP ===\n";
    $stmt = $pdo->prepare("
        SELECT ticker, current_price, previous_close, price_change_percent 
        FROM TodayEarningsMovements 
        WHERE current_price > 0 AND (market_cap IS NULL OR market_cap = 0)
    ");
    $stmt->execute();
    $tickersToUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($tickersToUpdate) . " tickers to fetch market cap for\n";
    
    if (empty($tickersToUpdate)) {
        echo "❌ No tickers need market cap update\n";
        exit(0);
    }
    
    // STEP 2: Fetch market cap from Polygon Ticker Details in batches
    echo "=== STEP 2: FETCHING MARKET CAP FROM POLYGON TICKER DETAILS (BATCH) ===\n";
    
    // Split tickers into chunks of 10 (concurrent requests)
    $tickerList = array_column($tickersToUpdate, 'ticker');
    $chunks = array_chunk($tickerList, 10);
    
    $totalUpdated = 0;
    $totalWithMarketCap = 0;
    
    foreach ($chunks as $index => $tickerChunk) {
        echo "Processing batch " . ($index + 1) . "/" . count($chunks) . " (" . count($tickerChunk) . " tickers)...\n";
        
        $batchData = getPolygonBatchTickerDetails($tickerChunk);
        
        if ($batchData) {
            foreach ($batchData as $ticker => $detailsData) {
                // Find corresponding ticker data
                $tickerData = null;
                foreach ($tickersToUpdate as $data) {
                    if ($data['ticker'] === $ticker) {
                        $tickerData = $data;
                        break;
                    }
                }
                
                if ($tickerData && $detailsData && $detailsData['marketCap'] > 0) {
                    $currentPrice = $tickerData['current_price'];
                    $previousClose = $tickerData['previous_close'];
                    $priceChangePercent = $tickerData['price_change_percent'];
                    
                    $marketCap = $detailsData['marketCap'];
                    $companyName = $detailsData['companyName'] ?? $ticker;
                    $sharesOutstanding = $detailsData['sharesOutstanding'] ?? 0;
                    
                    // Calculate market cap diff
                    $marketCapDiff = null;
                    $marketCapDiffBillions = null;
                    if ($priceChangePercent !== null) {
                        $marketCapDiff = ($priceChangePercent / 100) * $marketCap;
                        $marketCapDiffBillions = $marketCapDiff / 1000000000;
                    }
                    
                    // Determine size
                    $size = 'Unknown';
                    if ($marketCap >= 100000000000) { // 100+ billion
                        $size = 'Large';
                    } elseif ($marketCap >= 10000000000) { // 10+ billion
                        $size = 'Mid';
                    } else { // < 10 billion
                        $size = 'Small';
                    }
                    
                    // Update database
                    $stmt = $pdo->prepare("
                        UPDATE TodayEarningsMovements 
                        SET market_cap = ?, 
                            market_cap_diff = ?, 
                            market_cap_diff_billions = ?,
                            size = ?,
                            shares_outstanding = ?,
                            company_name = ?,
                            updated_at = NOW()
                        WHERE ticker = ?
                    ");
                    
                    $stmt->execute([
                        $marketCap,
                        $marketCapDiff,
                        $marketCapDiffBillions,
                        $size,
                        $sharesOutstanding,
                        $companyName,
                        $ticker
                    ]);
                    
                    $totalUpdated++;
                    $totalWithMarketCap++;
                    
                    echo "  ✅ {$ticker}: MC $" . number_format($marketCap / 1000000000, 1) . "B ({$size}) - " . substr($companyName, 0, 30) . "\n";
                }
            }
        }
        
        // Small delay between batches
        if ($index < count($chunks) - 1) {
            echo "Sleeping 1 second...\n";
            sleep(1);
        }
    }
    
    echo "✅ Polygon Ticker Details batch market cap fetch completed\n\n";
    
    // FINAL SUMMARY
    echo "=== FINAL SUMMARY ===\n";
    
    // Count records
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE market_cap > 0");
    $stmt->execute();
    $withMarketCap = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE current_price > 0");
    $stmt->execute();
    $withPrice = $stmt->fetchColumn();
    
    echo "📊 Tickers with prices: {$withPrice}\n";
    echo "📊 Tickers with market cap: {$withMarketCap}\n";
    echo "📊 Market cap updates: {$totalUpdated}\n";
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "⏱️  Total time: {$executionTime}s\n";
    echo "✅ POLYGON TICKER DETAILS BATCH MARKET CAP FETCH COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Get ticker details for multiple tickers using concurrent requests
 */
function getPolygonBatchTickerDetails($tickers) {
    $results = [];
    $processes = [];
    
    // Create concurrent requests using curl_multi
    $mh = curl_multi_init();
    $curlHandles = [];
    
    foreach ($tickers as $ticker) {
        $apiKey = POLYGON_API_KEY;
        $url = "https://api.polygon.io/v3/reference/tickers/{$ticker}?apiKey={$apiKey}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EarningsTable/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        curl_multi_add_handle($mh, $ch);
        $curlHandles[$ticker] = $ch;
    }
    
    // Execute concurrent requests
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);
    
    // Process results
    foreach ($tickers as $ticker) {
        $ch = $curlHandles[$ticker];
        $response = curl_multi_getcontent($ch);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data && isset($data['results'])) {
                $result = $data['results'];
                
                $marketCap = $result['market_cap'] ?? null;
                $companyName = $result['name'] ?? null;
                $sharesOutstanding = $result['shares_outstanding'] ?? null;
                
                if ($marketCap && $marketCap > 0) {
                    $results[$ticker] = [
                        'marketCap' => $marketCap,
                        'companyName' => $companyName,
                        'sharesOutstanding' => $sharesOutstanding
                    ];
                }
            }
        }
        
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    
    curl_multi_close($mh);
    
    return $results;
}
?>
