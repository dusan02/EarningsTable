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
    
    // Get today's tickers from EarningsTickersToday (MAIN SOURCE)
    $stmt = $pdo->prepare("
        SELECT 
            e.ticker,
            e.eps_estimate,
            e.revenue_estimate,
            e.report_time,
            e.data_source,
            e.source_priority,
            t.company_name,
            t.current_price,
            t.previous_close,
            t.market_cap,
            t.size,
            t.market_cap_diff,
            t.market_cap_diff_billions,
            t.price_change_percent,
            t.shares_outstanding,
            t.eps_actual,
            t.revenue_actual,
            t.updated_at
        FROM EarningsTickersToday e
        LEFT JOIN TodayEarningsMovements t ON e.ticker = t.ticker
        WHERE e.report_date = ?
        ORDER BY e.ticker
    ");
    $stmt->execute([$date]);
    $earnings = $stmt->fetchAll();
    
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