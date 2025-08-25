<?php
/**
 * API Rate Limiter
 * Obmedzuje počet API volaní za určitý čas
 */

class RateLimiter {
    private $storage;
    private $limit;
    private $window;
    private $identifier;
    
    /**
     * Konštruktor
     */
    public function __construct($identifier = 'default', $limit = null, $window = null) {
        $this->identifier = $identifier;
        $this->limit = $limit ?? EnvLoader::get('API_RATE_LIMIT', 100);
        $this->window = $window ?? EnvLoader::get('API_RATE_WINDOW', 60);
        $this->storage = $this->getStoragePath();
    }
    
    /**
     * Získa cestu k úložisku pre rate limiting
     */
    private function getStoragePath() {
        $storageDir = __DIR__ . '/../storage/rate_limits';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        return $storageDir . '/' . $this->identifier . '.json';
    }
    
    /**
     * Skontroluje, či je možné vykonať API volanie
     */
    public function canProceed() {
        $data = $this->loadData();
        $now = time();
        
        // Vyčisti staré záznamy
        $data = array_filter($data, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window;
        });
        
        // Skontroluj limit
        if (count($data) >= $this->limit) {
            return false;
        }
        
        // Pridaj nový záznam
        $data[] = $now;
        $this->saveData($data);
        
        return true;
    }
    
    /**
     * Získa počet zostávajúcich volaní
     */
    public function getRemaining() {
        $data = $this->loadData();
        $now = time();
        
        // Vyčisti staré záznamy
        $data = array_filter($data, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window;
        });
        
        return max(0, $this->limit - count($data));
    }
    
    /**
     * Získa čas do resetu
     */
    public function getResetTime() {
        $data = $this->loadData();
        if (empty($data)) {
            return 0;
        }
        
        $oldest = min($data);
        return ($oldest + $this->window) - time();
    }
    
    /**
     * Načíta dáta z úložiska
     */
    private function loadData() {
        if (!file_exists($this->storage)) {
            return [];
        }
        
        $content = file_get_contents($this->storage);
        $data = json_decode($content, true);
        
        return is_array($data) ? $data : [];
    }
    
    /**
     * Uloží dáta do úložiska
     */
    private function saveData($data) {
        file_put_contents($this->storage, json_encode($data));
    }
    
    /**
     * Vyčisti staré záznamy
     */
    public function cleanup() {
        $data = $this->loadData();
        $now = time();
        
        $data = array_filter($data, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window;
        });
        
        $this->saveData($data);
    }
    
    /**
     * Získa štatistiky
     */
    public function getStats() {
        $data = $this->loadData();
        $now = time();
        
        // Vyčisti staré záznamy
        $data = array_filter($data, function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window;
        });
        
        return [
            'current' => count($data),
            'limit' => $this->limit,
            'remaining' => max(0, $this->limit - count($data)),
            'reset_time' => $this->getResetTime(),
            'window' => $this->window
        ];
    }
}

/**
 * API Rate Limiter Manager
 * Spravuje viacero rate limiterov pre rôzne API
 */
class RateLimiterManager {
    private static $limiters = [];
    
    /**
     * Získa rate limiter pre konkrétne API
     */
    public static function getLimiter($api, $identifier = null) {
        $key = $api . '_' . ($identifier ?? 'default');
        
        if (!isset(self::$limiters[$key])) {
            $limit = self::getApiLimit($api);
            $window = self::getApiWindow($api);
            self::$limiters[$key] = new RateLimiter($key, $limit, $window);
        }
        
        return self::$limiters[$key];
    }
    
    /**
     * Získa limit pre konkrétne API
     */
    private static function getApiLimit($api) {
        $limits = [
            'polygon' => EnvLoader::get('POLYGON_RATE_LIMIT', 100),
            'finnhub' => EnvLoader::get('FINNHUB_RATE_LIMIT', 60),
            'yahoo' => EnvLoader::get('YAHOO_RATE_LIMIT', 100),
            'default' => EnvLoader::get('API_RATE_LIMIT', 100)
        ];
        
        return $limits[strtolower($api)] ?? $limits['default'];
    }
    
    /**
     * Získa časové okno pre konkrétne API
     */
    private static function getApiWindow($api) {
        $windows = [
            'polygon' => EnvLoader::get('POLYGON_RATE_WINDOW', 60),
            'finnhub' => EnvLoader::get('FINNHUB_RATE_WINDOW', 60),
            'yahoo' => EnvLoader::get('YAHOO_RATE_WINDOW', 60),
            'default' => EnvLoader::get('API_RATE_WINDOW', 60)
        ];
        
        return $windows[strtolower($api)] ?? $windows['default'];
    }
    
    /**
     * Skontroluje, či je možné vykonať API volanie
     */
    public static function canProceed($api, $identifier = null) {
        $limiter = self::getLimiter($api, $identifier);
        return $limiter->canProceed();
    }
    
    /**
     * Získa štatistiky pre všetky API
     */
    public static function getAllStats() {
        $stats = [];
        foreach (self::$limiters as $key => $limiter) {
            $stats[$key] = $limiter->getStats();
        }
        return $stats;
    }
    
    /**
     * Vyčisti všetky rate limitery
     */
    public static function cleanup() {
        foreach (self::$limiters as $limiter) {
            $limiter->cleanup();
        }
    }
}
?>
