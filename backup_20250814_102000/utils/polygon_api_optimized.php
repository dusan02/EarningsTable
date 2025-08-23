<?php
/**
 * Clean Polygon API - Essential Functions Only
 * Optimized for minimal code footprint
 */

/**
 * Get batch snapshot for all tickers
 * Single API call instead of individual calls
 */
function getBatchSnapshot($tickers) {
    $url = POLYGON_BASE_URL . '/v2/snapshot/locale/us/markets/stocks/tickers';
    $url .= '?apikey=' . POLYGON_API_KEY;
    
    $startTime = microtime(true);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => [
                'User-Agent: EarningsTable/1.0',
                'Accept: application/json'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $endTime = microtime(true);
    
    if ($response === false) {
        error_log("Failed to fetch batch snapshot from Polygon API");
        return false;
    }
    
    // LOGGING: HTTP hit verification
    $responseSize = strlen($response);
    $timeToFirstByte = round(($endTime - $startTime) * 1000, 2);
    echo "🔍 HTTP HIT VERIFICATION:\n";
    echo "  📡 URL: " . POLYGON_BASE_URL . '/v2/snapshot/locale/us/markets/stocks/tickers' . "\n";
    echo "  📊 Response size: " . number_format($responseSize) . " bytes (" . round($responseSize / 1024 / 1024, 2) . " MB)\n";
    echo "  ⏱️  Time to first byte: {$timeToFirstByte}ms\n";
    echo "  🎯 Expected: 1 hit, ~8-12MB, 400-800ms\n\n";
    
    $data = json_decode($response, true);
    
    if (!isset($data['tickers'])) {
        echo "❌ No 'tickers' key in response\n";
        return false;
    }
    
    // Filter only our earnings tickers
    $tickerMap = array_flip($tickers);
    $filteredResults = [];
    
    foreach ($data['tickers'] as $result) {
        $ticker = $result['ticker'];
        if (isset($tickerMap[$ticker])) {
            $filteredResults[$ticker] = $result;
        }
    }
    
    echo "✅ BATCH API SUCCESS: " . count($filteredResults) . " tickers found from " . count($data['tickers']) . " total\n\n";
    
    return $filteredResults;
}

/**
 * Get accurate market cap and shares outstanding from Polygon V3 Reference
 */
function getAccurateMarketCap($ticker) {
    $url = POLYGON_BASE_URL . '/v3/reference/tickers/' . $ticker;
    $url .= '?apiKey=' . POLYGON_API_KEY;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => [
                'User-Agent: EarningsTable/1.0',
                'Accept: application/json'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['results'])) {
        return null;
    }
    
    $results = $data['results'];
    
    return [
        'market_cap' => $results['market_cap'] ?? null,
        'shares_outstanding' => $results['weighted_shares_outstanding'] ?? null,
        'company_name' => $results['name'] ?? $ticker
    ];
}

/**
 * Get accurate market cap data for multiple tickers using batch approach
 * Uses enhanced shares outstanding with multiple fallbacks
 */
function getAccurateMarketCapBatch($tickers) {
    $results = [];
    $apiCalls = 0;
    $maxCalls = 100; // Increased limit for better coverage
    
    // Priority tickers that should always get accurate data
    $priorityTickers = ['LLY', 'MSFT', 'AAPL', 'GOOGL', 'AMZN', 'NVDA', 'META', 'TSLA', 'MSI', 'FLUT', 'LNG', 'VST'];
    
    // First, get accurate data for priority tickers
    foreach ($priorityTickers as $ticker) {
        if (in_array($ticker, $tickers)) {
            $sharesOutstanding = getSharesOutstandingEnhanced($ticker);
            if ($sharesOutstanding !== null) {
                $results[$ticker] = [
                    'shares_outstanding' => $sharesOutstanding,
                    'company_name' => $ticker // Will be updated if Polygon V3 provides it
                ];
            }
            $apiCalls++;
            
            // Rate limiting - pause every 5 calls
            if ($apiCalls % 5 == 0) {
                usleep(500000); // 0.5 second pause
            }
        }
    }
    
    // Then get accurate data for remaining tickers (up to maxCalls)
    foreach ($tickers as $ticker) {
        if (in_array($ticker, $priorityTickers)) {
            continue; // Already processed
        }
        
        if ($apiCalls >= $maxCalls) {
            break;
        }
        
        $sharesOutstanding = getSharesOutstandingEnhanced($ticker);
        if ($sharesOutstanding !== null) {
            $results[$ticker] = [
                'shares_outstanding' => $sharesOutstanding,
                'company_name' => $ticker
            ];
        }
        $apiCalls++;
        
        // Rate limiting - pause every 5 calls
        if ($apiCalls % 5 == 0) {
            usleep(500000); // 0.5 second pause
        }
    }
    
    echo "📈 ENHANCED DATA FETCHED: " . count($results) . " tickers with shares outstanding (limited to {$maxCalls})\n";
    
    return $results;
}

/**
 * Get shares outstanding from IEX Cloud (alternative to Finnhub)
 */
function getSharesOutstandingIEX($ticker) {
    // Note: IEX Cloud requires API key, but provides 50,000 free calls/month
    // For now, return null - would need IEX API key to implement
    return null;
}

/**
 * Get shares outstanding from Financial Model Prep (alternative to Finnhub)
 */
function getSharesOutstandingFMP($ticker) {
    // Note: FMP provides 250 free calls/day
    // For now, return null - would need FMP API key to implement
    return null;
}

/**
 * Enhanced function to get shares outstanding with multiple fallbacks
 */
function getSharesOutstandingEnhanced($ticker) {
    // Try Polygon V3 first (most accurate)
    $polygonData = getAccurateMarketCap($ticker);
    if ($polygonData && isset($polygonData['shares_outstanding']) && $polygonData['shares_outstanding'] > 0) {
        return $polygonData['shares_outstanding'];
    }
    
    // Try Finnhub (already implemented)
    $finnhubShares = Finnhub::getSharesOutstanding($ticker);
    if ($finnhubShares !== null && $finnhubShares > 0) {
        return $finnhubShares * 1000000; // Convert from millions
    }
    
    // Try IEX Cloud (if API key available)
    $iexShares = getSharesOutstandingIEX($ticker);
    if ($iexShares !== null && $iexShares > 0) {
        return $iexShares;
    }
    
    // Try FMP (if API key available)
    $fmpShares = getSharesOutstandingFMP($ticker);
    if ($fmpShares !== null && $fmpShares > 0) {
        return $fmpShares;
    }
    
    // No shares outstanding data available
    return null;
}

if (!function_exists('processTickerDataWithAccurateMC')) {
    /**
     * Normalizuje snapshot pre jeden ticker a vráti, čo vieme zapísať.
     * - cena: lastTrade.p alebo fallback na prevDay.c
     * - % zmeny: iba ak máme lastTrade aj prevClose (inak NULL, aby nepadalo -100%)
     * - mc: len ak máme shares_outstanding > 0
     */
    function processTickerDataWithAccurateMC(array $snapshot, string $ticker, ?array $accurate = null): array
    {
        $last = isset($snapshot['lastTrade']['p']) ? (float)$snapshot['lastTrade']['p'] : 0.0;
        $prev = isset($snapshot['prevDay']['c'])   ? (float)$snapshot['prevDay']['c']   : 0.0;

        // Zobrazovaná cena: last ak existuje, inak prevClose; ak oboje 0 -> nič nezapisujeme
        $price = $last > 0 ? $last : ($prev > 0 ? $prev : 0.0);
        $havePrice = $price > 0;

        // Percento zmeny iba v prípade last & prev (inak necháme NULL, aby nebol -100%)
        $changePct = ($last > 0 && $prev > 0) ? (($last - $prev) / $prev * 100.0) : null;

        // Shares outstanding (na market cap)
        $so = null;
        if (is_array($accurate) && isset($accurate['shares_outstanding']) && $accurate['shares_outstanding'] > 0) {
            $so = (float)$accurate['shares_outstanding'];
        }

        return [
            'have_price'  => $havePrice,
            'price'       => $price,
            'prev'        => $prev,
            'change_pct'  => $changePct,
            'have_so'     => $so !== null,
            'so'          => $so,
            // voliteľné: názov firmy, ak ho máš v $accurate
            'company_name'=> isset($accurate['company_name']) ? (string)$accurate['company_name'] : null,
        ];
    }
}
?> 