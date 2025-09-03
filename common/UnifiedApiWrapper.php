<?php
/**
 * 🚀 UNIFIED API WRAPPER
 * 
 * Konsoliduje všetky API funkcie do jednej triedy:
 * - Eliminuje duplicitný kód
 * - Centralizuje API logiku
 * - Zjednodušuje údržbu
 * - Poskytuje konzistentné rozhranie
 */

require_once __DIR__ . '/../config.php';

class UnifiedApiWrapper {
    private $pdo;
    private $finnhubApiKey;
    private $polygonApiKey;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->finnhubApiKey = FINNHUB_API_KEY ?? null;
        $this->polygonApiKey = POLYGON_API_KEY ?? null;
    }
    
    // ========================================
    // PRICE CALCULATIONS - UNIFIED
    // ========================================
    
    /**
     * Získa aktuálnu cenu z Polygon dát s robustným fallback
     */
    public function getCurrentPrice($polygonData, $includeExtended = true) {
        if (!is_array($polygonData)) return null;
        
        // Helper: check if trade is fresh
        $nowMs = (int) (microtime(true) * 1000);
        $freshWindowMs = $includeExtended ? 5 * 60 * 1000 : 60 * 1000; // 5 min vs 60 s
        
        // 1) Last trade (fresh)
        if (isset($polygonData['lastTrade']['p'], $polygonData['lastTrade']['t'])) {
            $tradeTime = (int)$polygonData['lastTrade']['t'];
            $tradeTimeMs = $tradeTime / 1000000; // Convert nanoseconds to milliseconds
            
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
        
        // 3) Current minute close
        if (isset($polygonData['min']['c']) && $polygonData['min']['c'] > 0) {
            return ['price' => (float)$polygonData['min']['c'], 'source' => 'minuteClose'];
        }
        
        // 4) Today's session close-so-far
        if (isset($polygonData['session']['c']) && $polygonData['session']['c'] > 0) {
            return ['price' => (float)$polygonData['session']['c'], 'source' => 'sessionClose'];
        }
        
        // 5) Previous day close (fallback)
        if (isset($polygonData['prevDay']['c']) && $polygonData['prevDay']['c'] > 0) {
            return ['price' => (float)$polygonData['prevDay']['c'], 'source' => 'prevDayClose'];
        }
        
        return null;
    }
    
    /**
     * Vypočíta percentuálnu zmenu ceny - UNIFIED LOGIC
     */
    public function computePercentChange($snapshot, $lastTradeV3 = null, $prevClose = null) {
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
            $currentPriceData = $this->getCurrentPrice($snapshot, false);
            if ($currentPriceData && $currentPriceData['price'] > 0) {
                $percent = (($currentPriceData['price'] - $prevClose) / $prevClose) * 100;
                return ['percent' => $percent, 'source' => 'mock_calculation'];
            }
        }
        
        // 6) Fallback: try to calculate from last trade if available
        if (isset($snapshot['lastTrade']['p']) && $snapshot['lastTrade']['p'] > 0 && $prevClose > 0) {
            $price = (float)$snapshot['lastTrade']['p'];
            $percent = (($price - $prevClose) / $prevClose) * 100;
            return ['percent' => $percent, 'source' => 'last_trade_fallback'];
        }
        
        return ['percent' => 0, 'source' => 'no_change'];
    }
    
    /**
     * Vypočíta percentuálnu zmenu ceny (jednoduchá verzia)
     */
    public function calculatePriceChange($currentPrice, $previousPrice) {
        if (!$currentPrice || !$previousPrice || $previousPrice <= 0) {
            return null;
        }
        
        return (($currentPrice - $previousPrice) / $previousPrice) * 100;
    }
    
    // ========================================
    // MARKET CAP OPERATIONS - UNIFIED
    // ========================================
    
    /**
     * Formátuje market cap hodnotu - UNIFIED LOGIC
     */
    public function formatMarketCap($marketCap) {
        if (!$marketCap || $marketCap <= 0) return 'N/A';
        
        if ($marketCap >= 1e12) return '$' . round($marketCap / 1e12, 1) . 'T';
        if ($marketCap >= 1e9) return '$' . round($marketCap / 1e9, 1) . 'B';
        if ($marketCap >= 1e6) return '$' . round($marketCap / 1e6, 1) . 'M';
        if ($marketCap >= 1e3) return '$' . round($marketCap / 1e3, 1) . 'K';
        
        return '$' . number_format($marketCap, 0);
    }
    
    /**
     * Vypočíta market cap diff
     */
    public function calculateMarketCapDiff($currentMarketCap, $previousMarketCap) {
        if (!$currentMarketCap || !$previousMarketCap || $previousMarketCap <= 0) {
            return ['diff' => null, 'diff_billions' => null];
        }
        
        $diff = $currentMarketCap - $previousMarketCap;
        $diffBillions = $diff / 1e9;
        
        return [
            'diff' => $diff,
            'diff_billions' => round($diffBillions, 2)
        ];
    }
    
    // ========================================
    // VALIDATION FUNCTIONS - UNIFIED
    // ========================================
    
    /**
     * Validuje ticker symbol
     */
    public function isValidTicker($ticker) {
        if (empty($ticker)) return false;
        
        // Basic validation: 1-5 characters, alphanumeric
        return preg_match('/^[A-Z]{1,5}$/', $ticker);
    }
    
    /**
     * Vyčistí názov spoločnosti
     */
    public function sanitizeCompanyName($companyName) {
        if (empty($companyName)) return '';
        
        // Remove common suffixes and clean up
        $cleanName = preg_replace('/\s+(Inc\.?|Corp\.?|Corporation|Company|Co\.?|Ltd\.?|Limited|Group|Holdings?|International|Technologies|Technology|Tech|Systems|Solutions|Services|Enterprises|Industries|Partners|Management|Capital|Acquisition|American Depositary.*|Common Stock|Class [A-Z].*|each.*)/i', '', $companyName);
        $cleanName = preg_replace('/\s*,.*$/', '', $cleanName);
        $cleanName = preg_replace('/\s+/', ' ', $cleanName);
        
        return trim($cleanName);
    }
    
    // ========================================
    // FINNHUB API WRAPPER - UNIFIED
    // ========================================
    
    /**
     * Získa earnings calendar z Finnhub
     */
    public function getFinnhubEarningsCalendar($from, $to) {
        if (!$this->finnhubApiKey) {
            throw new Exception('Finnhub API key not configured');
        }
        
        $url = "https://finnhub.io/api/v1/calendar/earnings?from={$from}&to={$to}&token={$this->finnhubApiKey}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: EarningsTable/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP {$httpCode}: {$response}");
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response");
        }
        
        return $data;
    }
    
    // ========================================
    // POLYGON API WRAPPER - UNIFIED
    // ========================================
    
    /**
     * Získa batch quote data z Polygon
     */
    public function getPolygonBatchQuote($tickers) {
        if (!$this->polygonApiKey) {
            throw new Exception('Polygon API key not configured');
        }
        
        if (empty($tickers)) return [];
        
        $results = [];
        $chunks = array_chunk($tickers, 25); // Process tickers in smaller chunks for better API stability
        
        foreach ($chunks as $chunk) {
            $tickerList = implode(',', $chunk);
            $url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers?tickers={$tickerList}&apikey={$this->polygonApiKey}";
            
            echo "  🔍 Fetching batch data for: " . implode(',', array_slice($chunk, 0, 5)) . "...\n";
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60, // Increased from 30s to 60s
                CURLOPT_CONNECTTIMEOUT => 10, // Connection timeout
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            curl_close($ch);
            
            if ($curlError) {
                echo "  ❌ cURL Error: {$curlError}\n";
                continue;
            }
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                echo "  📊 API Response structure: " . implode(', ', array_keys($data)) . "\n";
                echo "  ⏱️  Total time: {$totalTime}s\n";
                
                if (isset($data['tickers']) && is_array($data['tickers'])) {
                    // Current API format: data.tickers is an array of ticker objects
                    echo "  ✅ Using current API format (data.tickers array)\n";
                    foreach ($data['tickers'] as $result) {
                        if (isset($result['ticker'])) {
                            $ticker = $result['ticker'];
                            $results[$ticker] = $result;
                        }
                    }
                    echo "  📈 Parsed " . count($data['tickers']) . " tickers\n";
                } elseif (isset($data['ticker'])) {
                    // New API format: data.ticker is an object with ticker as key
                    echo "  ✅ Using new API format (data.ticker)\n";
                    foreach ($data['ticker'] as $ticker => $result) {
                        $results[$ticker] = $result;
                    }
                    echo "  📈 Parsed " . count($data['ticker']) . " tickers\n";
                } elseif (isset($data['results'])) {
                    // Old API format: data.results is an array
                    echo "  ✅ Using old API format (data.results)\n";
                    foreach ($data['results'] as $result) {
                        $ticker = $result['ticker'];
                        $results[$ticker] = $result;
                    }
                    echo "  📈 Parsed " . count($data['results']) . " tickers\n";
                } else {
                    echo "  ❌ Unknown API response structure\n";
                    echo "  📋 Response preview: " . substr($response, 0, 200) . "...\n";
                }
            } else {
                echo "  ❌ API request failed: HTTP {$httpCode}, Time: {$totalTime}s\n";
                if ($response) {
                    echo "  📋 Error response: " . substr($response, 0, 200) . "...\n";
                }
            }
            
            // Rate limiting - increased delay for better API stability
            usleep(50000); // 50ms delay (increased from 10ms)
        }
        
        echo "  🎯 Final results: " . count($results) . " tickers\n";
        return $results;
    }
    
    /**
     * Získa batch last trades data z Polygon V3 API
     */
    public function getPolygonBatchLastTrades($tickers) {
        if (!$this->polygonApiKey) {
            throw new Exception('Polygon API key not configured');
        }
        
        if (empty($tickers)) return [];
        
        $results = [];
        
        foreach ($tickers as $ticker) {
            $url = "https://api.polygon.io/v3/trades/{$ticker}/last?apikey={$this->polygonApiKey}";
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['results']) && !empty($data['results'])) {
                    $results[$ticker] = $data['results'][0]; // Get last trade
                }
            }
            
            // Rate limiting
            usleep(10000); // 10ms delay (reduced from 50ms)
        }
        
        return $results;
    }
    
    // ========================================
    // UTILITY FUNCTIONS - UNIFIED
    // ========================================
    
    /**
     * Retry mechanism pre API calls
     */
    public function retryOperation($operation, $maxAttempts = 3, $delay = 1000000) {
        $attempts = 0;
        $lastException = null;
        
        while ($attempts < $maxAttempts) {
            try {
                return $operation();
            } catch (Exception $e) {
                $lastException = $e;
                $attempts++;
                
                if ($attempts < $maxAttempts) {
                    usleep($delay);
                }
            }
        }
        
        throw $lastException;
    }
    
    /**
     * Loguje API performance metrics
     */
    private function logApiPerformance($api, $duration, $success, $endpoint = '') {
        $this->logger->logApiPerformance($api, $duration, $success, $endpoint);
    }
}
?>
