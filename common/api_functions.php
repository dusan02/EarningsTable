<?php
/**
 * 🛠️ API FUNCTIONS - Common PHP functions for API operations
 * Extracted from cron files to eliminate code duplication
 */

require_once __DIR__ . '/error_handler.php';

/**
 * Get current price from Polygon data
 * @param array $polygonData Polygon API response data
 * @return float|null Current price or null if not available
 */
function getCurrentPrice($polygonData) {
    if (!is_array($polygonData)) return null;
    
    // Try last trade price first
    if (isset($polygonData['lastTrade']['p']) && $polygonData['lastTrade']['p'] > 0) {
        return (float)$polygonData['lastTrade']['p'];
    }
    
    // Fallback to previous close
    if (isset($polygonData['prevDay']['c']) && $polygonData['prevDay']['c'] > 0) {
        return (float)$polygonData['prevDay']['c'];
    }
    
    return null;
}

/**
 * Get batch quote data from Polygon API
 * @param array $tickers Array of ticker symbols
 * @return array|false Polygon response data or false on error
 */
function getPolygonBatchQuote($tickers) {
    if (empty($tickers)) return false;
    
    // Polygon API requires tickers in URL parameter
    $tickerList = implode(',', $tickers);
    $url = POLYGON_BASE_URL . '/v2/snapshot/locale/us/markets/stocks/tickers';
    $url .= '?tickers=' . urlencode($tickerList);
    $url .= '&apikey=' . POLYGON_API_KEY;
    
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
        logApiError('Polygon', $url, 'Failed to fetch response', [
            'tickers' => $tickers
        ]);
        return false;
    }
    
    // Log API call performance
    $responseSize = strlen($response);
    $timeToFirstByte = round(($endTime - $startTime) * 1000, 2);
    echo "🔍 POLYGON BATCH API:\n";
    echo "  📊 Response size: " . number_format($responseSize) . " bytes (" . round($responseSize / 1024 / 1024, 2) . " MB)\n";
    echo "  ⏱️  Time: {$timeToFirstByte}ms\n";
    
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
 * Get ticker details from Polygon API
 * @param string $ticker Ticker symbol
 * @return array|false Ticker details or false on error
 */
function getPolygonTickerDetails($ticker) {
    $url = POLYGON_BASE_URL . '/v3/reference/tickers/' . urlencode($ticker);
    $url .= '?apikey=' . POLYGON_API_KEY;
    
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
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['results'])) {
        return false;
    }
    
    return $data['results'];
}

/**
 * Calculate market cap from price and shares outstanding
 * @param float $price Current stock price
 * @param float $sharesOutstanding Number of shares outstanding
 * @return float|null Market cap or null if invalid data
 */
function calculateMarketCap($price, $sharesOutstanding) {
    if (!$price || !$sharesOutstanding || $price <= 0 || $sharesOutstanding <= 0) {
        return null;
    }
    
    return $price * $sharesOutstanding;
}

/**
 * Determine company size based on market cap
 * @param float $marketCap Market capitalization
 * @return string Size classification (Large, Mid, Small)
 */
function getCompanySize($marketCap) {
    if (!$marketCap || $marketCap <= 0) return 'Small';
    
    if ($marketCap >= 10e9) return 'Large';      // $10B+
    if ($marketCap >= 2e9) return 'Mid';         // $2B - $10B
    return 'Small';                              // < $2B
}

/**
 * Format market cap for display
 * @param float $marketCap Market capitalization
 * @return string Formatted market cap string
 */
function formatMarketCap($marketCap) {
    if (!$marketCap || $marketCap <= 0) return 'N/A';
    
    if ($marketCap >= 1e12) return '$' . round($marketCap / 1e12, 1) . 'T';
    if ($marketCap >= 1e9) return '$' . round($marketCap / 1e9, 1) . 'B';
    if ($marketCap >= 1e6) return '$' . round($marketCap / 1e6, 1) . 'M';
    if ($marketCap >= 1e3) return '$' . round($marketCap / 1e3, 1) . 'K';
    
    return '$' . number_format($marketCap, 0);
}

/**
 * Calculate price change percentage
 * @param float $currentPrice Current price
 * @param float $previousPrice Previous close price
 * @return float|null Price change percentage or null if invalid
 */
function calculatePriceChange($currentPrice, $previousPrice) {
    if (!$currentPrice || !$previousPrice || $previousPrice <= 0) {
        return null;
    }
    
    return (($currentPrice - $previousPrice) / $previousPrice) * 100;
}

/**
 * Validate ticker symbol
 * @param string $ticker Ticker symbol to validate
 * @return bool True if valid ticker
 */
function isValidTicker($ticker) {
    if (empty($ticker)) return false;
    
    // Basic validation: 1-5 characters, alphanumeric
    return preg_match('/^[A-Z]{1,5}$/', $ticker);
}

/**
 * Sanitize company name
 * @param string $companyName Company name to sanitize
 * @return string Sanitized company name
 */
function sanitizeCompanyName($companyName) {
    if (empty($companyName)) return '';
    
    // Remove common suffixes and clean up
    $cleanName = preg_replace('/\s+(Inc\.?|Corp\.?|Corporation|Company|Co\.?|Ltd\.?|Limited|Group|Holdings?|International|Technologies|Technology|Tech|Systems|Solutions|Services|Enterprises|Industries|Partners|Management|Capital|Acquisition|American Depositary.*|Common Stock|Class [A-Z].*|each.*)/i', '', $companyName);
    $cleanName = preg_replace('/\s*,.*$/', '', $cleanName);
    $cleanName = preg_replace('/\s+/', ' ', $cleanName);
    
    return trim($cleanName);
}
