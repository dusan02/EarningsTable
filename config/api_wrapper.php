<?php
/**
 * API Wrapper with Rate Limiting
 * Bezpečné API volania s kontrolou limitov
 */

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
            throw new Exception("cURL error: $error");
        }
        
        if ($httpCode >= 400) {
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
        $url .= "?apikey=" . POLYGON_API_KEY;
        
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
