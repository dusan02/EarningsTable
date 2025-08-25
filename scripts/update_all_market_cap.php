<?php
/**
 * Update All Market Cap Data
 * Aktualizuje market cap údaje pre všetky tickers bez market cap
 */

require_once 'config.php';
require_once __DIR__ . '/../common/error_handler.php';

echo "=== UPDATE ALL MARKET CAP DATA ===\n\n";

// Get all tickers with prices but no market cap
$stmt = $pdo->prepare("
    SELECT ticker, current_price, previous_close, price_change_percent 
    FROM TodayEarningsMovements 
    WHERE current_price > 0 AND (market_cap IS NULL OR market_cap = 0)
");
$stmt->execute();
$tickersToUpdate = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($tickersToUpdate) . " tickers to update\n\n";

$updated = 0;
$failed = 0;

foreach ($tickersToUpdate as $tickerData) {
    $ticker = $tickerData['ticker'];
    echo "Processing: {$ticker}\n";
    
    $apiKey = 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX';
    $url = "https://api.polygon.io/v3/reference/tickers/{$ticker}?apiKey={$apiKey}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EarningsTable/1.0');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response !== false && $httpCode === 200) {
        $data = json_decode($response, true);
        
        if ($data && isset($data['results'])) {
            $result = $data['results'];
            $marketCap = $result['market_cap'] ?? null;
            $companyName = $result['name'] ?? null;
            
            if ($marketCap && $marketCap > 0) {
                // Calculate market cap diff
                $priceChangePercent = $tickerData['price_change_percent'];
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
                    $marketCapBillions = $marketCap / 1000000000;
                    echo "✅ Updated: {$ticker} - \${$marketCapBillions}B ({$companyName})\n";
                    $updated++;
                } else {
                    echo "❌ Database update failed: {$ticker}\n";
                    $failed++;
                }
            } else {
                echo "❌ No market cap data: {$ticker}\n";
                $failed++;
            }
        } else {
            echo "❌ Invalid response: {$ticker}\n";
            $failed++;
        }
    } else {
        logApiError('Polygon', $url, "HTTP {$httpCode} error", [
            'ticker' => $ticker,
            'http_code' => $httpCode
        ]);
        displayWarning("API error ({$httpCode}): {$ticker}");
        $failed++;
    }
    
    // Rate limiting
    sleep(1);
}

echo "\n=== SUMMARY ===\n";
echo "Updated: {$updated}\n";
echo "Failed: {$failed}\n";
echo "Total: " . count($tickersToUpdate) . "\n";

echo "\n✅ Market cap update completed\n";
?>
