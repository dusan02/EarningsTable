<?php
/**
 * API Wrapper with Rate Limiting
 * Bezpečné API volania s kontrolou limitov
 */

require_once __DIR__ . '/../common/error_handler.php';
require_once __DIR__ . '/rate_limiter.php';
require_once __DIR__ . '/../common/Finnhub.php';
require_once __DIR__ . '/../config.php';

class ApiWrapper {
    private $api;
    private $limiter;
    
    /**
     * Konštruktor
     */
    public function __construct($api) {
        $this->api = $api;
        $this->limiter = RateLimiterManager::getLimiter($api);
    }
    
    /**
     * Vykoná API volanie s rate limiting
     */
    public function call($url, $options = []) {
        // Skontroluj rate limit
        if (!$this->limiter->canProceed()) {
            $stats = $this->limiter->getStats();
            throw new Exception("API rate limit exceeded. Reset in {$stats['reset_time']} seconds.");
        }
        
        // Nastav predvolené opcie
        $defaultOptions = [
            'timeout' => 30,
            'headers' => [],
            'method' => 'GET',
            'data' => null
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Vykonaj API volanie
        $response = $this->makeRequest($url, $options);
        
        // Log API volanie
        $this->logApiCall($url, $response);
        
        return $response;
    }
    
    /**
     * Vykoná HTTP požiadavku
     */
    private function makeRequest($url, $options) {
        $ch = curl_init();
        
        // Základné nastavenia
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'EarningsTable/1.0');
        
        // Nastav metódu
        if ($options['method'] === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($options['data']) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $options['data']);
            }
        }
        
        // Nastav hlavičky
        if (!empty($options['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        }
        
        // Vykonaj požiadavku
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Skontroluj chyby
        if ($error) {
            logApiError($this->api, $url, "cURL error: $error", [
                'http_code' => $httpCode,
                'error' => $error
            ]);
            throw new Exception("cURL error: $error");
        }
        
        if ($httpCode >= 400) {
            logApiError($this->api, $url, "HTTP error $httpCode", [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new Exception("HTTP error $httpCode: $response");
        }
        
        return [
            'data' => $response,
            'http_code' => $httpCode,
            'url' => $url
        ];
    }
    
    /**
     * Loguje API volanie
     */
    private function logApiCall($url, $response) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'api' => $this->api,
            'url' => $url,
            'http_code' => $response['http_code'],
            'response_size' => strlen($response['data'])
        ];
        
        $logFile = __DIR__ . '/../logs/api_calls.log';
        $logEntry = json_encode($logData) . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Získa štatistiky rate limitera
     */
    public function getStats() {
        return $this->limiter->getStats();
    }
    
    /**
     * Získa zostávajúce volania
     */
    public function getRemaining() {
        return $this->limiter->getRemaining();
    }
    
    /**
     * Získa čas do resetu
     */
    public function getResetTime() {
        return $this->limiter->getResetTime();
    }
}

/**
 * Polygon API Wrapper
 */
class PolygonApiWrapper extends ApiWrapper {
    public function __construct() {
        parent::__construct('polygon');
    }
    
    /**
     * Získa real-time quotes
     */
    public function getQuote($ticker) {
        $url = POLYGON_BASE_URL . "/v2/snapshot/locale/us/markets/stocks/tickers/$ticker/quote";
        $url .= "?apikey=" . POLYGON_API_KEY;
        
        return $this->call($url);
    }
    
    /**
     * Získa company data
     */
    public function getCompany($ticker) {
        $url = POLYGON_BASE_URL . "/v3/reference/tickers/$ticker";
        $url .= "?apiKey=" . POLYGON_API_KEY;
        
        return $this->call($url);
    }
    
    /**
     * Získa earnings data
     */
    public function getEarnings($ticker) {
        $url = POLYGON_BASE_URL . "/v2/reference/financials/$ticker";
        $url .= "?apikey=" . POLYGON_API_KEY;
        
        return $this->call($url);
    }
    
    /**
     * Get batch snapshot for all tickers (optimized)
     */
    public function getBatchSnapshot($tickers) {
        $url = POLYGON_BASE_URL . '/v2/snapshot/locale/us/markets/stocks/tickers';
        $url .= '?apikey=' . POLYGON_API_KEY;
        
        $startTime = microtime(true);
        
        $response = $this->call($url);
        $endTime = microtime(true);
        
        // LOGGING: HTTP hit verification
        $responseSize = strlen($response['data']);
        $timeToFirstByte = round(($endTime - $startTime) * 1000, 2);
        echo "🔍 HTTP HIT VERIFICATION:\n";
        echo "  📡 URL: " . POLYGON_BASE_URL . '/v2/snapshot/locale/us/markets/stocks/tickers' . "\n";
        echo "  📊 Response size: " . number_format($responseSize) . " bytes (" . round($responseSize / 1024 / 1024, 2) . " MB)\n";
        echo "  ⏱️  Time to first byte: {$timeToFirstByte}ms\n";
        echo "  🎯 Expected: 1 hit, ~8-12MB, 400-800ms\n\n";
        
        $data = json_decode($response['data'], true);
        
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
    public function getAccurateMarketCap($ticker) {
        $url = POLYGON_BASE_URL . '/v3/reference/tickers/' . $ticker;
        $url .= '?apiKey=' . POLYGON_API_KEY;
        
        $response = $this->call($url);
        $data = json_decode($response['data'], true);
        
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
     */
    public function getAccurateMarketCapBatch($tickers) {
        $results = [];
        $apiCalls = 0;
        $maxCalls = 100; // Increased limit for better coverage
        
        // Priority tickers that should always get accurate data
        $priorityTickers = ['LLY', 'MSFT', 'AAPL', 'GOOGL', 'AMZN', 'NVDA', 'META', 'TSLA', 'MSI', 'FLUT', 'LNG', 'VST'];
        
        // First, get accurate data for priority tickers
        foreach ($priorityTickers as $ticker) {
            if (in_array($ticker, $tickers)) {
                $sharesOutstanding = $this->getSharesOutstandingEnhanced($ticker);
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
            
            $sharesOutstanding = $this->getSharesOutstandingEnhanced($ticker);
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
     * Enhanced function to get shares outstanding with multiple fallbacks
     */
    public function getSharesOutstandingEnhanced($ticker) {
        // Try Polygon V3 first (most accurate)
        $polygonData = $this->getAccurateMarketCap($ticker);
        if ($polygonData && isset($polygonData['shares_outstanding']) && $polygonData['shares_outstanding'] > 0) {
            return $polygonData['shares_outstanding'];
        }
        
        // Try Finnhub (already implemented)
        $finnhubShares = Finnhub::getSharesOutstanding($ticker);
        if ($finnhubShares !== null && $finnhubShares > 0) {
            return $finnhubShares * 1000000; // Convert from millions
        }
        
        // No shares outstanding data available
        return null;
    }
    
    /**
     * Process ticker data with accurate market cap
     */
    public function processTickerDataWithAccurateMC(array $snapshot, string $ticker, ?array $accurate = null): array {
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

/**
 * Finnhub API Wrapper
 */
class FinnhubApiWrapper extends ApiWrapper {
    public function __construct() {
        parent::__construct('finnhub');
    }
    
    /**
     * Získa earnings calendar
     */
    public function getEarningsCalendar($from = null, $to = null) {
        if (!$from) $from = date('Y-m-d');
        if (!$to) $to = date('Y-m-d', strtotime('+7 days'));
        
        $url = FINNHUB_BASE_URL . "/calendar/earnings";
        $url .= "?from=$from&to=$to&token=" . FINNHUB_API_KEY;
        
        return $this->call($url);
    }
    
    /**
     * Získa company profile
     */
    public function getCompanyProfile($ticker) {
        $url = FINNHUB_BASE_URL . "/stock/profile2";
        $url .= "?symbol=$ticker&token=" . FINNHUB_API_KEY;
        
        return $this->call($url);
    }
    
    /**
     * Získa quote
     */
    public function getQuote($ticker) {
        $url = FINNHUB_BASE_URL . "/quote";
        $url .= "?symbol=$ticker&token=" . FINNHUB_API_KEY;
        
        return $this->call($url);
    }
}

/**
 * API Factory
 */
class ApiFactory {
    /**
     * Vytvorí API wrapper
     */
    public static function create($api) {
        switch (strtolower($api)) {
            case 'polygon':
                return new PolygonApiWrapper();
            case 'finnhub':
                return new FinnhubApiWrapper();
            default:
                return new ApiWrapper($api);
        }
    }
    
    /**
     * Získa všetky API štatistiky
     */
    public static function getAllStats() {
        return RateLimiterManager::getAllStats();
    }
    
    /**
     * Vyčisti všetky rate limitery
     */
    public static function cleanup() {
        RateLimiterManager::cleanup();
    }
}
?>
