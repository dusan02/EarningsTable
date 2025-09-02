<?php
/**
 * Earnings Tickers API Endpoint - Enhanced with Guidance Data
 * Returns JSON data for today's earnings tickers with market cap information and guidance data
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
    
    // Get today's tickers with guidance data (using subquery to get latest guidance per ticker)
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
            t.change_source,
            t.shares_outstanding,
            t.eps_actual,
            t.revenue_actual,
            t.updated_at,
            -- Latest guidance data per ticker
            g.estimated_eps_guidance as eps_guide,
            g.eps_guide_vs_consensus_pct as eps_guide_surprise_consensus,
            g.estimated_revenue_guidance as revenue_guide,
            g.revenue_guide_vs_consensus_pct as revenue_guide_surprise_consensus,
            g.previous_min_eps_guidance,
            g.previous_max_eps_guidance,
            g.previous_min_revenue_guidance,
            g.previous_max_revenue_guidance,
            g.notes as guidance_notes
        FROM EarningsTickersToday e
        LEFT JOIN TodayEarningsMovements t ON e.ticker = t.ticker
        LEFT JOIN (
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
                notes,
                ROW_NUMBER() OVER (PARTITION BY ticker ORDER BY 
                    CASE WHEN release_type = 'final' THEN 1 ELSE 2 END,
                    last_updated DESC
                ) as rn
            FROM benzinga_guidance
            WHERE fiscal_period IN ('Q1','Q2','Q3','Q4','FY')
            AND fiscal_year IN (2024, 2025)
        ) g ON g.ticker = e.ticker AND g.rn = 1
        WHERE e.report_date = ?
        GROUP BY e.ticker
        ORDER BY e.ticker
    ");
    $stmt->execute([$date]);
    $earnings = $stmt->fetchAll();
    
    // Apply fallback logic for guidance surprise values
    foreach ($earnings as &$item) {
        // EPS Guide Surprise Fallback
        if ($item['eps_guide_surprise_consensus'] !== null) {
            // Use consensus if available
            $item['eps_guide_surprise'] = $item['eps_guide_surprise_consensus'];
            $item['eps_guide_basis'] = 'consensus';
        } elseif ($item['eps_guide'] !== null && $item['eps_estimate'] !== null && $item['eps_estimate'] != 0) {
            // Fallback: guidance vs estimate
            $item['eps_guide_surprise'] = (($item['eps_guide'] - $item['eps_estimate']) / $item['eps_estimate']) * 100;
            $item['eps_guide_basis'] = 'estimate';
        } elseif ($item['eps_guide'] !== null && 
                  ($item['previous_min_eps_guidance'] !== null || $item['previous_max_eps_guidance'] !== null)) {
            // Fallback: guidance vs previous guidance midpoint
            $min = $item['previous_min_eps_guidance'] ?? $item['eps_guide'];
            $max = $item['previous_max_eps_guidance'] ?? $item['eps_guide'];
            $midpoint = ($min + $max) / 2;
            if ($midpoint != 0) {
                $item['eps_guide_surprise'] = (($item['eps_guide'] - $midpoint) / $midpoint) * 100;
                $item['eps_guide_basis'] = 'previous_mid';
            } else {
                $item['eps_guide_surprise'] = null;
                $item['eps_guide_basis'] = null;
            }
        } else {
            $item['eps_guide_surprise'] = null;
            $item['eps_guide_basis'] = null;
        }
        
        // Revenue Guide Surprise Fallback
        if ($item['revenue_guide_surprise_consensus'] !== null) {
            // Use consensus if available
            $item['revenue_guide_surprise'] = $item['revenue_guide_surprise_consensus'];
            $item['revenue_guide_basis'] = 'consensus';
        } elseif ($item['revenue_guide'] !== null && $item['revenue_estimate'] !== null && $item['revenue_estimate'] != 0) {
            // Fallback: guidance vs estimate
            $item['revenue_guide_surprise'] = (($item['revenue_guide'] - $item['revenue_estimate']) / $item['revenue_estimate']) * 100;
            $item['revenue_guide_basis'] = 'estimate';
        } elseif ($item['revenue_guide'] !== null && 
                  ($item['previous_min_revenue_guidance'] !== null || $item['previous_max_revenue_guidance'] !== null)) {
            // Fallback: guidance vs previous guidance midpoint
            $min = $item['previous_min_revenue_guidance'] ?? $item['revenue_guide'];
            $max = $item['previous_max_revenue_guidance'] ?? $item['revenue_guide'];
            $midpoint = ($min + $max) / 2;
            if ($midpoint != 0) {
                $item['revenue_guide_surprise'] = (($item['revenue_guide'] - $midpoint) / $midpoint) * 100;
                $item['revenue_guide_basis'] = 'previous_mid';
            } else {
                $item['revenue_guide_surprise'] = null;
                $item['revenue_guide_basis'] = null;
            }
        } else {
            $item['revenue_guide_surprise'] = null;
            $item['revenue_guide_basis'] = null;
        }
        
        // Clean up temporary fields
        unset($item['eps_guide_surprise_consensus']);
        unset($item['revenue_guide_surprise_consensus']);
        unset($item['previous_min_eps_guidance']);
        unset($item['previous_max_eps_guidance']);
        unset($item['previous_min_revenue_guidance']);
        unset($item['previous_max_revenue_guidance']);
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