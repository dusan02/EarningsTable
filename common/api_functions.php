<?php
/**
 * 🛠️ API FUNCTIONS - Common PHP functions for API operations
 * Extracted from cron files to eliminate code duplication
 */

require_once __DIR__ . '/error_handler.php';

/**
 * Get current price from Polygon data with robust fallback logic
 * @param array $polygonData Polygon API response data
 * @param bool $includeExtended Whether to include extended hours data
 * @return array|null Array with 'price' and 'source' or null if not available
 */
function getCurrentPrice($polygonData, $includeExtended = true) {
    if (!is_array($polygonData)) return null;
    
    // Helper: check if trade is fresh
    $nowMs = (int) (microtime(true) * 1000);
    $freshWindowMs = $includeExtended ? 5 * 60 * 1000 : 60 * 1000; // 5 min vs 60 s
    
    // 1) Last trade (fresh)
    if (isset($polygonData['lastTrade']['p'], $polygonData['lastTrade']['t'])) {
        $tradeTime = (int)$polygonData['lastTrade']['t'];
        // Polygon timestamps are in nanoseconds, convert to milliseconds
        $tradeTimeMs = $tradeTime / 1000000;
        
        if ($nowMs - $tradeTimeMs <= $freshWindowMs && $polygonData['lastTrade']['p'] > 0) {
            return ['price' => (float)$polygonData['lastTrade']['p'], 'source' => 'lastTrade'];
        }
    }
    
    // 2) Quote mid (if quote is available and reasonable)
    if (isset($polygonData['lastQuote']['bp'], $polygonData['lastQuote']['ap'])) {
        $bp = (float)$polygonData['lastQuote']['bp'];
        $ap = (float)$polygonData['lastQuote']['ap'];
        if ($bp > 0 && $ap > 0 && $ap >= $bp) {
            $mid = ($bp + $ap) / 2.0;
            return ['price' => $mid, 'source' => 'quoteMid'];
        }
    }
    
    // 3) Current minute close (not minimum of the day!)
    if (isset($polygonData['min']['c']) && $polygonData['min']['c'] > 0) {
        return ['price' => (float)$polygonData['min']['c'], 'source' => 'minuteClose'];
    }
    
    // 4) Today's session close-so-far (if available)
    if (isset($polygonData['day']['c']) && $polygonData['day']['c'] > 0) {
        return ['price' => (float)$polygonData['day']['c'], 'source' => 'dayCloseSoFar'];
    }
    
    // 5) DEVELOPMENT MODE: Generate mock current prices when markets are closed
    if (defined('ENABLE_MOCK_PRICE_CHANGES')) {
        if (isset($polygonData['prevDay']['c']) && $polygonData['prevDay']['c'] > 0) {
            $prevClose = (float)$polygonData['prevDay']['c'];
            $ticker = $polygonData['ticker'] ?? 'UNKNOWN';
            
            // Use ticker hash for consistent but pseudo-random changes
            $seed = crc32($ticker . date('Y-m-d'));
            srand($seed);
            
            if (rand(0, 100) < 60) {
                // 60% chance: smaller moves (-2% to +2%)
                $percent = (rand(-200, 200) / 100.0);
            } else {
                // 40% chance: larger moves (-5% to +5%)
                $percent = (rand(-500, 500) / 100.0);
            }
            
            // Restore random seed
            srand();
            
            $mockPrice = $prevClose * (1 + $percent / 100);
            return ['price' => round($mockPrice, 2), 'source' => 'mock_testing'];
        }
    }
    
    // 6) Previous day close (final fallback)
    if (isset($polygonData['prevDay']['c']) && $polygonData['prevDay']['c'] > 0) {
        return ['price' => (float)$polygonData['prevDay']['c'], 'source' => 'prevClose'];
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
            'timeout' => 60, // Increased from 30s to 60s for better stability
            'header' => [
                'User-Agent: EarningsTable/1.0',
                'Accept: application/json'
            ]
        ]
    ]);
    
    // Retry logic for better reliability
    $maxAttempts = 3;
    $attempt = 1;
    
    while ($attempt <= $maxAttempts) {
        echo "  🔄 Attempt {$attempt}/{$maxAttempts} for Polygon batch API...\n";
        
        $response = file_get_contents($url, false, $context);
        $endTime = microtime(true);
        
        if ($response !== false) {
            break; // Success, exit retry loop
        }
        
        echo "  ❌ Attempt {$attempt} failed\n";
        
        if ($attempt < $maxAttempts) {
            $delay = $attempt * 2; // Progressive delay: 2s, 4s, 6s
            echo "  ⏳ Waiting {$delay}s before retry...\n";
            sleep($delay);
        }
        
        $attempt++;
    }
    
    if ($response === false) {
        logApiError('Polygon', $url, 'Failed to fetch response after ' . $maxAttempts . ' attempts', [
            'tickers' => $tickers,
            'attempts' => $maxAttempts
        ]);
        return false;
    }
    
    // Log API call performance
    $responseSize = strlen($response);
    $timeToFirstByte = round(($endTime - $startTime) * 1000, 2);
    echo "🔍 POLYGON BATCH API:\n";
    echo "  📊 Response size: " . number_format($responseSize) . " bytes (" . round($responseSize / 1024 / 1024, 2) . " MB)\n";
    echo "  ⏱️  Time: {$timeToFirstByte}ms\n";
    echo "  ✅ Attempts: {$attempt}\n";
    
    $data = json_decode($response, true);
    
    if (!isset($data['tickers'])) {
        echo "❌ No 'tickers' key in response\n";
        if (isset($data['error'])) {
            echo "  📋 Error: " . $data['error'] . "\n";
        }
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
 * Get last trade from Polygon V3 Trades API
 * @param string $ticker Ticker symbol
 * @return array|false Last trade data or false on error
 */
function getPolygonLastTrade($ticker) {
    $url = POLYGON_BASE_URL . '/v3/trades/' . urlencode($ticker);
    $url .= '?limit=1&apikey=' . POLYGON_API_KEY;
    
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
    
    if (!isset($data['results']) || empty($data['results'])) {
        return false;
    }
    
    return $data['results'][0]; // Return first (most recent) trade
}

/**
 * Get batch last trades from Polygon V3 Trades API
 * @param array $tickers Array of ticker symbols
 * @return array|false Last trades data or false on error
 */
function getPolygonBatchLastTrades($tickers) {
    if (empty($tickers)) return false;
    
    $results = [];
    $apiCalls = 0;
    
    foreach ($tickers as $ticker) {
        $lastTrade = getPolygonLastTrade($ticker);
        
        if ($lastTrade) {
            $results[$ticker] = $lastTrade;
        }
        
        $apiCalls++;
        
        // Rate limiting - pause every 5 calls
        if ($apiCalls % 5 == 0) {
            usleep(500000); // 0.5 second pause
        }
    }
    
    return $results;
}

/**
 * Get historical daily close data from Polygon V2 Aggregates API
 * @param string $ticker Ticker symbol
 * @param string $date Date in Y-m-d format
 * @return float|false Previous close price or false on error
 */
function getPolygonHistoricalClose($ticker, $date) {
    // Convert date to timestamp for Polygon API
    $timestamp = strtotime($date);
    if ($timestamp === false) return false;
    
    // Polygon expects milliseconds timestamp
    $from = ($timestamp - (24 * 60 * 60)) * 1000; // Previous day
    $to = $timestamp * 1000; // Current day
    
    $url = POLYGON_BASE_URL . "/v2/aggs/ticker/{$ticker}/range/1/day/{$from}/{$to}";
    $url .= "?adjusted=true&sort=desc&limit=2&apikey=" . POLYGON_API_KEY;
    
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
    
    if (!isset($data['results']) || empty($data['results'])) {
        return false;
    }
    
    // Return previous day's close (second result, since we're sorting desc)
    if (count($data['results']) >= 2) {
        return (float)$data['results'][1]['c']; // Close price
    }
    
    return false;
}

/**
 * Get batch ticker details from Polygon V3 Reference API
 * @param array $tickers Array of ticker symbols
 * @return array|false Polygon V3 reference data or false on error
 */
function getPolygonBatchTickerDetails($tickers) {
    if (empty($tickers)) return false;
    
    $results = [];
    $apiCalls = 0;
    
    foreach ($tickers as $ticker) {
        $details = getPolygonTickerDetails($ticker);
        
        if ($details) {
            $results[$ticker] = [
                'market_cap' => $details['market_cap'] ?? null,
                'company_name' => $details['name'] ?? $ticker,
                'shares_outstanding' => $details['weighted_shares_outstanding'] ?? null,
                'type' => $details['type'] ?? null,
                'primary_exchange' => $details['primary_exchange'] ?? null
            ];
        }
        
        $apiCalls++;
        
        // Rate limiting - pause every 5 calls
        if ($apiCalls % 5 == 0) {
            usleep(500000); // 0.5 second pause
        }
    }
    
    return $results;
}

/**
 * Compute robust percent change with hierarchy of data sources
 * @param array $snapshot Polygon snapshot data
 * @param array|null $lastTradeV3 Last trade from V3 API
 * @param float $prevClose Previous close price
 * @return array Array with 'percent' and 'source'
 */
function computePercentChange($snapshot, $lastTradeV3, $prevClose) {
    // 1) Prefer last trade from V3 (includes extended hours)
    if ($lastTradeV3 && isset($lastTradeV3['p'])) {
        $price = (float)$lastTradeV3['p'];
        if ($price > 0 && $prevClose > 0) {
            $percent = (($price - $prevClose) / $prevClose) * 100;
            return ['percent' => $percent, 'source' => 'v3_trade'];
        }
    }
    
    // 2) If snapshot has nonzero todaysChangePerc, use it
    if (isset($snapshot['todaysChangePerc']) && $snapshot['todaysChangePerc'] != 0) {
        return ['percent' => (float)$snapshot['todaysChangePerc'], 'source' => 'snapshot_change'];
    }
    
    // 3) Try lastQuote midpoint (extended hours quote)
    if (isset($snapshot['lastQuote']['bp'], $snapshot['lastQuote']['ap'])) {
        $bp = (float)$snapshot['lastQuote']['bp'];
        $ap = (float)$snapshot['lastQuote']['ap'];
        if ($bp > 0 && $ap > 0 && $ap >= $bp && $prevClose > 0) {
            $mid = ($bp + $ap) / 2.0;
            $percent = (($mid - $prevClose) / $prevClose) * 100;
            return ['percent' => $percent, 'source' => 'quote_mid'];
        }
    }
    
    // 4) Try last minute close
    if (isset($snapshot['min']['c']) && $snapshot['min']['c'] > 0 && $prevClose > 0) {
        $price = (float)$snapshot['min']['c'];
        $percent = (($price - $prevClose) / $prevClose) * 100;
        return ['percent' => $percent, 'source' => 'minute_close'];
    }
    
    // 5) DEVELOPMENT MODE: Calculate from getCurrentPrice result if using mock data
    if (defined('ENABLE_MOCK_PRICE_CHANGES') && $prevClose > 0) {
        $priceData = getCurrentPrice($snapshot);
        if ($priceData && $priceData['source'] === 'mock_testing') {
            $currentPrice = $priceData['price'];
            $percent = (($currentPrice - $prevClose) / $prevClose) * 100;
            return ['percent' => $percent, 'source' => 'mock_testing'];
        }
    }
    
    // 6) Fallback: no change
    return ['percent' => 0.0, 'source' => 'prev_close'];
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

/**
 * Execute multiple HTTP requests in parallel using curl_multi
 * @param array $urls Array of URLs with ticker as key
 * @param int $maxConcurrent Maximum concurrent requests (default: 5)
 * @return array Results with ticker as key and success/response/error as values
 */
function executeParallelRequests($urls, $maxConcurrent = 5) {
    if (empty($urls)) {
        return [];
    }
    
    $results = [];
    $multiHandle = curl_multi_init();
    $handles = [];
    $active = 0;
    
    // Initialize curl handles
    foreach ($urls as $ticker => $url) {
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60, // Increased from 30s to 60s for better stability
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'EarningsTable/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_VERBOSE => false
        ]);
        
        $handles[$ticker] = $handle;
        curl_multi_add_handle($multiHandle, $handle);
        $active++;
        
        // Limit concurrent requests
        if ($active >= $maxConcurrent) {
            // Wait for some requests to complete
            do {
                $status = curl_multi_exec($multiHandle, $active);
                if ($active) {
                    curl_multi_select($multiHandle, 0.1); // 100ms timeout
                }
            } while ($active >= $maxConcurrent && $status == CURLM_OK);
        }
    }
    
    // Wait for all requests to complete
    do {
        $status = curl_multi_exec($multiHandle, $active);
        if ($active) {
            curl_multi_select($multiHandle, 0.1); // 100ms timeout
        }
    } while ($active && $status == CURLM_OK);
    
    // Process results
    foreach ($handles as $ticker => $handle) {
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $response = curl_multi_getcontent($handle);
        $error = curl_error($handle);
        $info = curl_getinfo($handle);
        
        if ($error) {
            $results[$ticker] = [
                'success' => false,
                'error' => $error,
                'response' => null
            ];
        } elseif ($httpCode >= 200 && $httpCode < 300 && $response) {
            $results[$ticker] = [
                'success' => true,
                'error' => null,
                'response' => $response
            ];
        } else {
            $errorMsg = "HTTP {$httpCode}";
            if ($info['total_time'] > 0) {
                $errorMsg .= " (time: " . round($info['total_time'] * 1000, 2) . "ms)";
            }
            if ($response) {
                $errorMsg .= ": " . substr($response, 0, 100);
            } else {
                $errorMsg .= ": Empty response";
            }
            
            $results[$ticker] = [
                'success' => false,
                'error' => $errorMsg,
                'response' => $response
            ];
        }
        
        curl_multi_remove_handle($multiHandle, $handle);
        curl_close($handle);
    }
    
    curl_multi_close($multiHandle);
    
    return $results;
}
