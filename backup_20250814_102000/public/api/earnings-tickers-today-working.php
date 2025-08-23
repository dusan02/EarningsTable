<?php
/**
 * Earnings Tickers API Endpoint - Working Version
 * Returns JSON data for today's earnings tickers with market cap information
 */

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Database configuration
    $host = 'localhost';
    $dbname = 'earnings_db';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Use US Eastern Time to match the cron jobs
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    // Get today's tickers from EarningsTickersToday
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $todayTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($todayTickers)) {
        echo json_encode([
            'date' => $date,
            'total' => 0,
            'data' => []
        ]);
        exit;
    }
    
    // Get market cap data for today's tickers from TodayEarningsMovements with EPS/Revenue from EarningsTickersToday
    $placeholders = str_repeat('?,', count($todayTickers) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT 
            m.ticker,
            m.company_name,
            m.current_price,
            m.previous_close,
            m.market_cap,
            m.size,
            m.market_cap_diff,
            m.market_cap_diff_billions,
            m.price_change_percent,
            m.shares_outstanding,
            e.eps_estimate,
            e.eps_actual,
            e.revenue_estimate,
            e.revenue_actual,
            m.eps_surprise_percent,
            m.revenue_surprise_percent,
            m.updated_at
        FROM TodayEarningsMovements m
        LEFT JOIN EarningsTickersToday e ON m.ticker = e.ticker AND e.report_date = ?
        WHERE m.ticker IN ($placeholders)
        ORDER BY m.market_cap_diff_billions DESC, m.ticker
    ");
    $stmt->execute(array_merge([$date], $todayTickers));
    $earnings = $stmt->fetchAll();
    
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
