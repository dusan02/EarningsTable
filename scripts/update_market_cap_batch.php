<?php
/**
 * Update Market Cap Data - BATCH VERSION
 * Aktualizuje market cap údaje pre všetky tickers v batch móde
 */

require_once 'config.php';

echo "=== UPDATE MARKET CAP DATA - BATCH VERSION ===\n\n";

// Get all tickers with prices but no market cap
$stmt = $pdo->prepare("
    SELECT ticker, current_price, previous_close, price_change_percent 
    FROM TodayEarningsMovements 
    WHERE current_price > 0 AND (market_cap IS NULL OR market_cap = 0)
");
$stmt->execute();
$tickersToUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($tickersToUpdate) . " tickers to update\n\n";

if (empty($tickersToUpdate)) {
    echo "✅ No tickers need market cap update\n";
    exit(0);
}

// Split tickers into batches of 10 (concurrent requests)
$tickerList = array_column($tickersToUpdate, 'ticker');
$chunks = array_chunk($tickerList, 10);

$updated = 0;
$failed = 0;
$noMarketCap = 0;

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
            
            if ($tickerData && $detailsData) {
                $currentPrice = $tickerData['current_price'];
                $previousClose = $tickerData['previous_close'];
                $priceChangePercent = $tickerData['price_change_percent'];
                
                $marketCap = $detailsData['marketCap'] ?? null;
                $companyName = $detailsData['companyName'] ?? $ticker;
                $sharesOutstanding = $detailsData['sharesOutstanding'] ?? 0;
                
                // Calculate market cap diff
                $marketCapDiff = null;
                $marketCapDiffBillions = null;
                if ($priceChangePercent !== null && $marketCap) {
                    $marketCapDiff = ($priceChangePercent / 100) * $marketCap;
                    $marketCapDiffBillions = $marketCapDiff / 1000000000;
                }
                
                // Determine size
                $size = 'Unknown';
                if ($marketCap) {
                    if ($marketCap >= 100000000000) { // 100+ billion
                        $size = 'Large';
                    } elseif ($marketCap >= 10000000000) { // 10+ billion
                        $size = 'Mid';
                    } else { // < 10 billion
                        $size = 'Small';
                    }
                }
                
                // Update database - ALWAYS update, even if no market cap
                $stmt = $pdo->prepare("
                    UPDATE TodayEarningsMovements 
                    SET market_cap = ?, 
                        company_name = ?, 
                        market_cap_diff = ?,
                        market_cap_diff_billions = ?,
                        size = ?,
                        updated_at = NOW()
                    WHERE ticker = ?
                ");
                $result = $stmt->execute([
                    $marketCap, 
                    $companyName, 
                    $marketCapDiff,
                    $marketCapDiffBillions,
                    $size,
                    $ticker
                ]);
                
                if ($result) {
                    if ($marketCap && $marketCap > 0) {
                        $marketCapBillions = $marketCap / 1000000000;
                        echo "  ✅ {$ticker}: \${$marketCapBillions}B ({$companyName})\n";
                        $updated++;
                    } else {
                        echo "  ⚠️  {$ticker}: No market cap data ({$companyName})\n";
                        $noMarketCap++;
                    }
                } else {
                    echo "  ❌ Database update failed: {$ticker}\n";
                    $failed++;
                }
            }
        }
    }
    
    // Rate limiting between batches
    if ($index < count($chunks) - 1) {
        echo "Sleeping 1 second...\n";
        sleep(1);
    }
}

echo "\n=== SUMMARY ===\n";
echo "Updated with market cap: {$updated}\n";
echo "Updated without market cap: {$noMarketCap}\n";
echo "Failed: {$failed}\n";
echo "Total: " . count($tickersToUpdate) . "\n";

echo "\n✅ Market cap batch update completed\n";

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
        $apiKey = 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX';
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
                
                // Always include the result, even if no market cap
                $results[$ticker] = [
                    'marketCap' => $marketCap,
                    'companyName' => $companyName,
                    'sharesOutstanding' => $sharesOutstanding
                ];
            }
        }
        
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    
    curl_multi_close($mh);
    
    return $results;
}
?>
