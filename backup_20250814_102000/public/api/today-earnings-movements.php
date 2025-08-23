<?php
/**
 * TodayEarningsMovements API Endpoint
 * Returns all tickers with market movements, sorted by market cap DESC
 */

require_once __DIR__ . '/../../config.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get current date in US Eastern Time
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    // Fetch data with LEFT JOIN to include all earnings tickers
    $stmt = $pdo->prepare("
        SELECT 
            e.ticker,
            COALESCE(m.company_name, e.ticker) as company_name,
            COALESCE(m.current_price, 0) as current_price,
            COALESCE(m.previous_close, 0) as previous_close,
            COALESCE(m.market_cap, 0) as market_cap,
            COALESCE(m.size, 'Unknown') as size,
            COALESCE(m.market_cap_diff, 0) as market_cap_diff,
            COALESCE(m.market_cap_diff_billions, 0) as market_cap_diff_billions,
            COALESCE(m.price_change_percent, 0) as price_change_percent,
            COALESCE(m.shares_outstanding, 0) as shares_outstanding,
            COALESCE(m.updated_at, e.report_date) as updated_at,
            e.report_time,
            e.eps_actual,
            e.eps_estimate,
            e.revenue_actual,
            e.revenue_estimate
        FROM EarningsTickersToday e
        LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
        WHERE e.report_date = ?
        ORDER BY m.market_cap DESC, e.ticker ASC
    ");
    
    $stmt->execute([$date]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add metadata
    $response = [
        'status' => 'success',
        'count' => count($data),
        'timestamp' => date('Y-m-d H:i:s'),
        'date' => $date,
        'data' => $data
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 