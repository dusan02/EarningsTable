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
// DEBUG: Kill cache completely for testing
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // DEBUG: Check which database API is connected to
    try {
        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        error_log("API DB: " . $dbName);
    } catch(Throwable $e) {
        error_log("API DB err: " . $e->getMessage());
    }
    
    // Use US Eastern Time to match the cron jobs
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    // Get ALL tickers with earnings data, then LEFT JOIN with guidance (FIXED: prioritize earnings)
    // --- DEBUG: vytiahni posledný guidance pre CRM tak, ako ho potrebuješ ---
    $sqlDbg = "
        SELECT
            ticker,
            fiscal_period,
            fiscal_year,
            CAST(estimated_eps_guidance AS DECIMAL(18,4))  AS eps_guide,
            CAST(estimated_revenue_guidance AS DECIMAL(18,2)) AS revenue_guide
        FROM benzinga_guidance
        WHERE ticker = 'CRM'
        AND fiscal_period IN ('Q1','Q2','Q3','Q4','FY','2H','3Q','1H','4Q')
        AND fiscal_year IN (2024, 2025, 2026)
        ORDER BY
            CASE WHEN release_type='final' THEN 1 ELSE 2 END,
            last_updated DESC
        LIMIT 1
    ";
    $dbg = $pdo->query($sqlDbg)->fetch(PDO::FETCH_ASSOC);
    error_log('CRM DEBUG GUIDANCE: ' . json_encode($dbg));
    
    $stmt = $pdo->prepare("
        SELECT 
            e.ticker,
            e.eps_estimate,
            e.revenue_estimate,
            e.fiscal_period,
            e.fiscal_year,
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
            -- Latest guidance data per ticker (LEFT JOIN from earnings)
            g.estimated_eps_guidance as eps_guide,
            g.eps_guide_vs_consensus_pct as eps_guide_surprise_consensus,
            g.estimated_revenue_guidance as revenue_guide,
            g.revenue_guide_vs_consensus_pct as revenue_guide_surprise_consensus,
            g.previous_min_eps_guidance,
            g.previous_max_eps_guidance,
            g.previous_min_revenue_guidance,
            g.previous_max_revenue_guidance,
            g.g_period as guidance_fiscal_period,
            g.g_year as guidance_fiscal_year,
            g.eps_method as guidance_eps_method,
            g.revenue_method as guidance_revenue_method,
            g.currency as guidance_currency,
            g.notes as guidance_notes
        FROM EarningsTickersToday e
        LEFT JOIN TodayEarningsMovements t ON e.ticker = t.ticker
        LEFT JOIN (
            SELECT 
                g1.ticker COLLATE utf8mb4_unicode_ci AS g_ticker,
                g1.fiscal_period COLLATE utf8mb4_unicode_ci AS g_period,
                g1.fiscal_year AS g_year,
                g1.estimated_eps_guidance,
                g1.eps_guide_vs_consensus_pct,
                g1.estimated_revenue_guidance,
                g1.revenue_guide_vs_consensus_pct,
                g1.previous_min_eps_guidance,
                g1.previous_max_eps_guidance,
                g1.previous_min_revenue_guidance,
                g1.previous_max_revenue_guidance,
                g1.eps_method,
                g1.revenue_method,
                g1.currency,
                g1.notes,
                ROW_NUMBER() OVER (PARTITION BY g1.ticker, g1.fiscal_period, g1.fiscal_year ORDER BY 
                    CASE WHEN g1.release_type = 'final' THEN 1 ELSE 2 END,
                    g1.last_updated DESC
                ) AS rn
            FROM benzinga_guidance g1
            WHERE g1.fiscal_period IN ('Q1','Q2','Q3','Q4','FY','H1','H2')
            AND g1.fiscal_year BETWEEN YEAR(CURDATE())-1 AND YEAR(CURDATE())+3
            -- ✅ Menej prísny filter: vyžaduje len akékoľvek guidance, nie nutne EPS AJ Revenue
            AND (
                (g1.estimated_eps_guidance != '' AND g1.estimated_eps_guidance IS NOT NULL)
                OR 
                (g1.estimated_revenue_guidance != '' AND g1.estimated_revenue_guidance IS NOT NULL)
            )
        ) g ON e.ticker COLLATE utf8mb4_unicode_ci = g.g_ticker
            AND e.fiscal_period COLLATE utf8mb4_unicode_ci = g.g_period
            AND e.fiscal_year = g.g_year
            AND g.rn = 1
        WHERE e.report_date = ?
        ORDER BY e.ticker
    ");
    $stmt->execute([$date]);
    $earnings = $stmt->fetchAll();
    
    // Helper functions for validation
    function periodsMatch($guidance, $item) {
        // Now we have fiscal_period and fiscal_year in both tables - strict matching required
        if (empty($guidance['fiscal_period']) || empty($guidance['fiscal_year'])) {
            return false; // No guidance period info
        }
        if (empty($item['fiscal_period']) || empty($item['fiscal_year'])) {
            return false; // No estimate period info
        }
        
        // Normalize period formats for comparison
        $guidancePeriod = normalizePeriod($guidance['fiscal_period']);
        $estimatePeriod = normalizePeriod($item['fiscal_period']);
        
        return $guidancePeriod === $estimatePeriod && 
               $guidance['fiscal_year'] == $item['fiscal_year'];
    }
    
    function normalizePeriod($period) {
        // Normalize different period formats to standard Q1-Q4, H1/H2, FY
        $period = strtoupper(trim($period));
        switch ($period) {
            case '1H': case 'H1': return 'H1';
            case '2H': case 'H2': return 'H2';
            case '3Q': case 'Q3': return 'Q3';
            case '4Q': case 'Q4': return 'Q4';
            case 'Q1': case 'Q2': return $period;
            case 'FY': return 'FY';
            default: return $period;
        }
    }
    
    function methodOk($guidanceMethod, $estimateMethod) {
        // Allow calculation if either method is null (unknown)
        // Only block if both are known and different
        if ($guidanceMethod === null || $estimateMethod === null) return true;
        return $guidanceMethod === $estimateMethod;
    }
    
    function canCompare($guide, $est, $guidanceMethod, $estimateMethod) {
        if (!$guide || !$est) return false;
        if ($est == 0) return false;
        if (!periodsMatch($guide, $est)) return false;
        if (!methodOk($guidanceMethod, $estimateMethod)) return false;
        return true;
    }
    
    function isExtremeValue($value) {
        return abs($value) > 300; // Flag values above 300% as potentially extreme
    }
    
    // Apply fallback logic for guidance surprise values with enhanced validation
    foreach ($earnings as &$item) {
        // EPS Guide Surprise Fallback with strict validation
        if ($item['eps_guide_surprise_consensus'] !== null) {
            // 1. PRIORITA: Use vendor consensus if available
            $item['eps_guide_surprise'] = $item['eps_guide_surprise_consensus'];
            $item['eps_guide_basis'] = 'vendor_consensus';
            $item['eps_guide_extreme'] = isExtremeValue($item['eps_guide_surprise']);
        } elseif (canCompare(
            ['fiscal_period' => $item['guidance_fiscal_period'], 'fiscal_year' => $item['guidance_fiscal_year']], 
            ['fiscal_period' => $item['fiscal_period'], 'fiscal_year' => $item['fiscal_year']], 
            $item['guidance_eps_method'] ?? null, 
            null
        ) && $item['eps_guide'] !== null && $item['eps_estimate'] !== null && $item['eps_estimate'] != 0) {
            // 2. FALLBACK: Guidance vs estimate (with strict period/method matching)
            $item['eps_guide_surprise'] = (($item['eps_guide'] - $item['eps_estimate']) / $item['eps_estimate']) * 100;
            $item['eps_guide_basis'] = 'estimate';
            $item['eps_guide_extreme'] = isExtremeValue($item['eps_guide_surprise']);
            
            // Log for monitoring
            if ($item['eps_guide_extreme']) {
                error_log("EXTREME EPS: {$item['ticker']} = {$item['eps_guide_surprise']}% (guidance: {$item['eps_guide']}, estimate: {$item['eps_estimate']}) - periods: {$item['guidance_fiscal_period']}/{$item['guidance_fiscal_year']} vs {$item['fiscal_period']}/{$item['fiscal_year']}");
            }
        } elseif (
            $item['eps_guide'] !== null && 
            $item['previous_min_eps_guidance'] !== null && 
            $item['previous_max_eps_guidance'] !== null &&
            $item['previous_min_eps_guidance'] != 0 && 
            $item['previous_max_eps_guidance'] != 0
        ) {
            // 3. FALLBACK: guidance vs previous guidance midpoint (only if both min/max exist)
            $midpoint = ($item['previous_min_eps_guidance'] + $item['previous_max_eps_guidance']) / 2;
            if ($midpoint != 0) {
                $item['eps_guide_surprise'] = (($item['eps_guide'] - $midpoint) / $midpoint) * 100;
                $item['eps_guide_basis'] = 'previous_mid';
                $item['eps_guide_extreme'] = isExtremeValue($item['eps_guide_surprise']);
            } else {
                $item['eps_guide_surprise'] = null;
                $item['eps_guide_basis'] = null;
                $item['eps_guide_extreme'] = false;
            }
        } else {
            $item['eps_guide_surprise'] = null;
            $item['eps_guide_basis'] = null;
            $item['eps_guide_extreme'] = false;
        }
        
        // Revenue Guide Surprise Fallback with strict validation
        if ($item['revenue_guide_surprise_consensus'] !== null) {
            // 1. PRIORITA: Use vendor consensus if available
            $item['revenue_guide_surprise'] = $item['revenue_guide_surprise_consensus'];
            $item['revenue_guide_basis'] = 'vendor_consensus';
            $item['revenue_guide_extreme'] = isExtremeValue($item['revenue_guide_surprise']);
        } elseif (canCompare(
            ['fiscal_period' => $item['guidance_fiscal_period'], 'fiscal_year' => $item['guidance_fiscal_year']], 
            ['fiscal_period' => $item['fiscal_period'], 'fiscal_year' => $item['fiscal_year']], 
            $item['guidance_revenue_method'] ?? null, 
            null
        ) && $item['revenue_guide'] !== null && $item['revenue_estimate'] !== null && $item['revenue_estimate'] != 0) {
            // 2. FALLBACK: Guidance vs estimate (with strict period/method matching)
            $item['revenue_guide_surprise'] = (($item['revenue_guide'] - $item['revenue_estimate']) / $item['revenue_estimate']) * 100;
            $item['revenue_guide_basis'] = 'estimate';
            $item['revenue_guide_extreme'] = isExtremeValue($item['revenue_guide_surprise']);
            
            // Log for monitoring
            if ($item['revenue_guide_extreme']) {
                error_log("EXTREME REVENUE: {$item['ticker']} = {$item['revenue_guide_surprise']}% (guidance: {$item['revenue_guide']}, estimate: {$item['revenue_estimate']}) - periods: {$item['guidance_fiscal_period']}/{$item['guidance_fiscal_year']} vs {$item['fiscal_period']}/{$item['fiscal_year']}");
            }
        } elseif (
            $item['revenue_guide'] !== null && 
            $item['previous_min_revenue_guidance'] !== null && 
            $item['previous_max_revenue_guidance'] !== null &&
            $item['previous_min_revenue_guidance'] != 0 && 
            $item['previous_max_revenue_guidance'] != 0
        ) {
            // 3. FALLBACK: guidance vs previous guidance midpoint (only if both min/max exist)
            $midpoint = ($item['previous_min_revenue_guidance'] + $item['previous_max_revenue_guidance']) / 2;
            if ($midpoint != 0) {
                $item['revenue_guide_surprise'] = (($item['revenue_guide'] - $midpoint) / $midpoint) * 100;
                $item['revenue_guide_basis'] = 'previous_mid';
                $item['revenue_guide_extreme'] = isExtremeValue($item['revenue_guide_surprise']);
            } else {
                $item['revenue_guide_surprise'] = null;
                $item['revenue_guide_basis'] = null;
                $item['revenue_guide_extreme'] = false;
            }
        } else {
            $item['revenue_guide_surprise'] = null;
            $item['revenue_guide_basis'] = null;
            $item['revenue_guide_extreme'] = false;
        }
        
        // Clean up temporary fields
        unset($item['eps_guide_surprise_consensus']);
        unset($item['revenue_guide_surprise_consensus']);
        unset($item['previous_min_eps_guidance']);
        unset($item['previous_max_eps_guidance']);
        unset($item['previous_min_revenue_guidance']);
        unset($item['previous_max_revenue_guidance']);
        // Keep fiscal period and year for dashboard display
        // unset($item['guidance_fiscal_period']);
        // unset($item['guidance_fiscal_year']);
        unset($item['guidance_eps_method']);
        unset($item['guidance_revenue_method']);
        unset($item['guidance_currency']);
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