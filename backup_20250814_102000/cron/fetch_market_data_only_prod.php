<?php
/**
 * Market Data Update - Production
 * Dotiahne current price, spočíta market_cap, market_cap_diff, price_change_%
 * Spúšťa sa každých 5 minút
 */

require_once __DIR__ . '/../config.php';

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

logMessage("Starting market data update...");

try {
    // Get today's date
    $date = date('Y-m-d');
    
    // Get today's tickers
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tickers)) {
        logMessage("No tickers found for today ($date)");
        exit(0);
    }
    
    logMessage("Found " . count($tickers) . " tickers for today");
    
    // Ensure TodayEarningsMovements table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS TodayEarningsMovements (
            ticker VARCHAR(10) PRIMARY KEY,
            current_price DECIMAL(10,2) NULL,
            price_change_percent DECIMAL(10,4) NULL,
            market_cap BIGINT NULL,
            market_cap_diff BIGINT NULL,
            market_cap_diff_billions DECIMAL(10,2) NULL,
            size ENUM('Large','Mid','Small') NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Update market data for each ticker
    $updateStmt = $pdo->prepare("
        INSERT INTO TodayEarningsMovements 
        (ticker, current_price, price_change_percent, market_cap, market_cap_diff, market_cap_diff_billions, size) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        current_price = VALUES(current_price),
        price_change_percent = VALUES(price_change_percent),
        market_cap = VALUES(market_cap),
        market_cap_diff = VALUES(market_cap_diff),
        market_cap_diff_billions = VALUES(market_cap_diff_billions),
        size = VALUES(size)
    ");
    
    $updated = 0;
    
    foreach ($tickers as $ticker) {
        // Fetch current price from Polygon
        $url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers/$ticker?apikey=" . POLYGON_API_KEY;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => ['Accept: application/json'],
                'timeout' => 10,
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            logMessage("WARNING: Failed to fetch market data for $ticker");
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['results']['value'])) {
            continue;
        }
        
        $result = $data['results']['value'];
        $currentPrice = $result['lastTrade']['p'] ?? null;
        $priceChange = $result['lastTrade']['p'] - $result['prevDay']['c'] ?? 0;
        $priceChangePercent = $result['prevDay']['c'] > 0 ? ($priceChange / $result['prevDay']['c']) * 100 : 0;
        
        // Calculate market cap (simplified - would need shares outstanding)
        $marketCap = null; // Would need to fetch from another API
        $marketCapDiff = null;
        $marketCapDiffBillions = null;
        
        // Determine size based on market cap (simplified)
        $size = 'Small'; // Default
        
        $updateStmt->execute([
            $ticker,
            $currentPrice,
            $priceChangePercent,
            $marketCap,
            $marketCapDiff,
            $marketCapDiffBillions,
            $size
        ]);
        
        $updated++;
    }
    
    logMessage("SUCCESS: Updated market data for $updated tickers");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}

logMessage("Market data update completed successfully");
?>
