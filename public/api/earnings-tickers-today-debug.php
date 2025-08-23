<?php
/**
 * Earnings Tickers API Endpoint - Debug Version
 * Returns JSON data for today's earnings tickers with market cap information
 */

require_once __DIR__ . '/../config.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Use US Eastern Time to match the cron jobs
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "=== API DEBUG ===\n";
    echo "Date: $date\n";
    
    // Get today's tickers from EarningsTickersToday
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $todayTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Today's tickers count: " . count($todayTickers) . "\n";
    
    if (empty($todayTickers)) {
        echo "No tickers found for today\n";
        echo json_encode([
            'date' => $date,
            'total' => 0,
            'data' => []
        ]);
        exit;
    }
    
    // Check if HYPR and BABA are in today's tickers
    $hyprInToday = in_array('HYPR', $todayTickers);
    $babaInToday = in_array('BABA', $todayTickers);
    
    echo "HYPR in today's tickers: " . ($hyprInToday ? 'YES' : 'NO') . "\n";
    echo "BABA in today's tickers: " . ($babaInToday ? 'YES' : 'NO') . "\n";
    
    // Get market cap data for today's tickers from TodayEarningsMovements
    $placeholders = str_repeat('?,', count($todayTickers) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT 
            ticker,
            company_name,
            current_price,
            previous_close,
            market_cap,
            size,
            market_cap_diff,
            market_cap_diff_billions,
            price_change_percent,
            shares_outstanding,
            eps_estimate,
            eps_actual,
            revenue_estimate,
            revenue_actual,
            eps_surprise_percent,
            revenue_surprise_percent,
            updated_at
        FROM TodayEarningsMovements 
        WHERE ticker IN ($placeholders)
        ORDER BY market_cap_diff_billions DESC, ticker
    ");
    $stmt->execute($todayTickers);
    $earnings = $stmt->fetchAll();
    
    echo "Market data count: " . count($earnings) . "\n";
    
    // Check HYPR and BABA in market data
    $hyprData = null;
    $babaData = null;
    
    foreach ($earnings as $row) {
        if ($row['ticker'] === 'HYPR') {
            $hyprData = $row;
        }
        if ($row['ticker'] === 'BABA') {
            $babaData = $row;
        }
    }
    
    echo "HYPR in market data: " . ($hyprData ? 'YES' : 'NO') . "\n";
    if ($hyprData) {
        echo "HYPR market_cap_diff: " . ($hyprData['market_cap_diff'] ?? 'NULL') . "\n";
        echo "HYPR market_cap_diff_billions: " . ($hyprData['market_cap_diff_billions'] ?? 'NULL') . "\n";
    }
    
    echo "BABA in market data: " . ($babaData ? 'YES' : 'NO') . "\n";
    if ($babaData) {
        echo "BABA market_cap_diff: " . ($babaData['market_cap_diff'] ?? 'NULL') . "\n";
        echo "BABA market_cap_diff_billions: " . ($babaData['market_cap_diff_billions'] ?? 'NULL') . "\n";
    }
    
    // Count non-null market_cap_diff
    $withMarketCapDiff = 0;
    foreach ($earnings as $row) {
        if ($row['market_cap_diff'] !== null) {
            $withMarketCapDiff++;
        }
    }
    
    echo "Records with market_cap_diff: $withMarketCapDiff\n";
    
    $response = [
        'date' => $date,
        'total' => count($earnings),
        'data' => $earnings
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>
