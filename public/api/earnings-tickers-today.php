<?php
/**
 * Earnings Tickers API Endpoint - Refactored
 * Returns JSON data for today's earnings tickers with market cap information
 */

require_once __DIR__ . '/../../config.php';

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
    
    // Get today's tickers from EarningsTickersToday
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $todayTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($todayTickers)) {
        $earnings = [];
    } else {
        // Get market cap data for today's tickers from TodayEarningsMovements
        $placeholders = str_repeat('?,', count($todayTickers) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT 
                t.ticker,
                t.company_name,
                t.current_price,
                t.previous_close,
                t.market_cap,
                t.size,
                t.market_cap_diff,
                t.market_cap_diff_billions,
                t.price_change_percent,
                t.shares_outstanding,
                e.eps_estimate,
                t.eps_actual,
                e.revenue_estimate,
                t.revenue_actual,
                t.updated_at,
                e.report_time
            FROM TodayEarningsMovements t
            LEFT JOIN EarningsTickersToday e ON t.ticker = e.ticker AND e.report_date = ?
            WHERE t.ticker IN ($placeholders)
            ORDER BY t.market_cap_diff_billions DESC, t.ticker
        ");
        $stmt->execute(array_merge([$date], $todayTickers));
        $earnings = $stmt->fetchAll();
    }
    
    $response = [
        'success' => true,
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