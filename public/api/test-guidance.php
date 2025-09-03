<?php
/**
 * Test Guidance API Endpoint
 * Returns JSON data for all tickers with guidance data
 */

require_once __DIR__ . '/../../config.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // Get US date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    // Get ALL tickers with guidance data, then LEFT JOIN earnings
    $stmt = $pdo->prepare("
        SELECT 
            g.ticker,
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
            t.change_source,
            t.shares_outstanding,
            t.eps_actual,
            t.revenue_actual,
            t.updated_at,
            -- Guidance data
            g.estimated_eps_guidance as eps_guide,
            g.eps_guide_vs_consensus_pct as eps_guide_surprise_consensus,
            g.estimated_revenue_guidance as revenue_guide,
            g.revenue_guide_vs_consensus_pct as revenue_guide_surprise_consensus,
            g.previous_min_eps_guidance,
            g.previous_max_eps_guidance,
            g.previous_min_revenue_guidance,
            g.previous_max_revenue_guidance,
            g.fiscal_period as guidance_fiscal_period,
            g.fiscal_year as guidance_fiscal_year,
            g.eps_method as guidance_eps_method,
            g.revenue_method as guidance_revenue_method,
            g.currency as guidance_currency,
            g.notes as guidance_notes
        FROM (
            SELECT 
                ticker COLLATE utf8mb4_general_ci as ticker,
                estimated_eps_guidance,
                eps_guide_vs_consensus_pct,
                estimated_revenue_guidance,
                revenue_guide_vs_consensus_pct,
                previous_min_eps_guidance,
                previous_max_eps_guidance,
                previous_min_revenue_guidance,
                previous_max_revenue_guidance,
                fiscal_period,
                fiscal_year,
                eps_method,
                revenue_method,
                currency,
                notes,
                ROW_NUMBER() OVER (PARTITION BY ticker ORDER BY 
                    CASE WHEN release_type = 'final' THEN 1 ELSE 2 END,
                    last_updated DESC
                ) as rn
            FROM benzinga_guidance
            WHERE fiscal_period IN ('Q1','Q2','Q3','Q4','FY','2H','3Q','1H','4Q')
            AND fiscal_year IN (2024, 2025, 2026)
        ) g
        LEFT JOIN EarningsTickersToday e ON g.ticker = e.ticker AND e.report_date = ?
        LEFT JOIN TodayEarningsMovements t ON g.ticker = t.ticker
        WHERE g.rn = 1
        ORDER BY g.ticker
    ");
    $stmt->execute([$date]);
    $results = $stmt->fetchAll();
    
    // Filter for specific tickers if needed
    $filteredResults = array_filter($results, function($item) {
        // Include all guidance data, regardless of earnings
        return true;
    });
    
    // Process guidance surprise values (simplified)
    foreach ($filteredResults as &$item) {
        // EPS Guide Surprise
        if ($item['eps_guide_surprise_consensus'] !== null) {
            $item['eps_guide_surprise'] = $item['eps_guide_surprise_consensus'];
            $item['eps_guide_basis'] = 'consensus';
        } elseif (
            $item['eps_guide'] !== null && 
            $item['eps_estimate'] !== null && 
            $item['eps_estimate'] != 0
        ) {
            $item['eps_guide_surprise'] = (($item['eps_guide'] - $item['eps_estimate']) / $item['eps_estimate']) * 100;
            $item['eps_guide_basis'] = 'estimate';
        } else {
            $item['eps_guide_surprise'] = null;
            $item['eps_guide_basis'] = null;
        }
        
        // Revenue Guide Surprise
        if ($item['revenue_guide_surprise_consensus'] !== null) {
            $item['revenue_guide_surprise'] = $item['revenue_guide_surprise_consensus'];
            $item['revenue_guide_basis'] = 'consensus';
        } elseif (
            $item['revenue_guide'] !== null && 
            $item['revenue_estimate'] !== null && 
            $item['revenue_estimate'] != 0
        ) {
            $item['revenue_guide_surprise'] = (($item['revenue_guide'] - $item['revenue_estimate']) / $item['revenue_estimate']) * 100;
            $item['revenue_guide_basis'] = 'estimate';
        } else {
            $item['revenue_guide_surprise'] = null;
            $item['revenue_guide_basis'] = null;
        }
        
        $item['eps_guide_extreme'] = false;
        $item['revenue_guide_extreme'] = false;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Test guidance data loaded successfully',
        'count' => count($filteredResults),
        'data' => array_values($filteredResults)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading test guidance data: ' . $e->getMessage(),
        'data' => []
    ], JSON_PRETTY_PRINT);
}
