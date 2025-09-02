<?php
/**
 * Unified Application Configuration System
 * Jednotný systém konfigurácie aplikácie
 */

require_once __DIR__ . '/../common/error_handler.php';
require_once __DIR__ . '/env_loader.php';

class AppConfig {
    private static $instance = null;
    private $config = [];
    private $environment;
    
    private function __construct() {
        $this->environment = EnvLoader::get('APP_ENV', 'production');
        $this->loadConfiguration();
    }
    
    /**
     * Singleton pattern
     */
    public static function getInstance(): AppConfig {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load configuration based on environment
     */
    private function loadConfiguration() {
        // Base configuration
        $this->config = [
            // Database
            'database' => [
                'host' => EnvLoader::get('DB_HOST', 'localhost'),
                'name' => EnvLoader::get('DB_NAME', 'earnings_table'),
                'user' => EnvLoader::get('DB_USER', 'root'),
                'pass' => EnvLoader::get('DB_PASS', ''),
                'charset' => 'utf8mb4'
            ],
            
            // API Configuration
            'api' => [
                'finnhub' => [
                    'key' => EnvLoader::get('FINNHUB_API_KEY', ''),
                    'base_url' => 'https://finnhub.io/api/v1'
                ],
                'polygon' => [
                    'key' => EnvLoader::get('POLYGON_API_KEY', ''),
                    'base_url' => 'https://api.polygon.io'
                ],
                'benzinga' => [
                    'key' => EnvLoader::get('BENZINGA_API_KEY', ''),
                    'base_url' => 'https://api.benzinga.com/api/v2.1'
                ]
            ],
            
            // Application
            'app' => [
                'timezone' => EnvLoader::get('TIMEZONE', 'America/New_York'),
                'debug' => EnvLoader::isDebug(),
                'environment' => $this->environment,
                'base_url' => EnvLoader::get('APP_URL', 'http://localhost:8080')
            ],
            
            // Rate Limiting
            'rate_limiting' => [
                'delay' => 0.1,
                'max_concurrent' => 10
            ],
            
            // Logging
            'logging' => [
                'level' => EnvLoader::get('LOG_LEVEL', 'INFO'),
                'path' => __DIR__ . '/../logs/'
            ]
        ];
        
        // Environment-specific overrides
        $this->applyEnvironmentOverrides();
        
        // Validate configuration
        $this->validateConfiguration();
    }
    
    /**
     * Apply environment-specific overrides
     */
    private function applyEnvironmentOverrides() {
        switch ($this->environment) {
            case 'development':
                $this->config['app']['debug'] = true;
                $this->config['logging']['level'] = 'DEBUG';
                $this->config['app']['timezone'] = 'Europe/Prague';
                break;
                
            case 'production':
                $this->config['app']['debug'] = false;
                $this->config['logging']['level'] = 'WARNING';
                break;
                
            case 'testing':
                $this->config['app']['debug'] = true;
                $this->config['logging']['level'] = 'DEBUG';
                $this->config['database']['name'] = 'earnings_table_test';
                break;
        }
    }
    
    /**
     * Validate configuration
     */
    private function validateConfiguration() {
        $errors = [];
        
        // Check API keys
        if (empty($this->config['api']['finnhub']['key']) || 
            $this->config['api']['finnhub']['key'] === 'your_finnhub_api_key_here') {
            $errors[] = 'FINNHUB_API_KEY not configured';
        }
        
        if (empty($this->config['api']['polygon']['key']) || 
            $this->config['api']['polygon']['key'] === 'your_polygon_api_key_here') {
            $errors[] = 'POLYGON_API_KEY not configured';
        }
        
        // Check database
        if (empty($this->config['database']['name'])) {
            $errors[] = 'Database name not configured';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                logConfigError('AppConfig', $error);
            }
            
            if ($this->config['app']['debug']) {
                throw new Exception('Configuration validation failed: ' . implode(', ', $errors));
            }
        }
    }
    
    /**
     * Get configuration value
     */
    public function get(string $key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Get database configuration
     */
    public function getDatabase(): array {
        return $this->config['database'];
    }
    
    /**
     * Get API configuration
     */
    public function getApi(): array {
        return $this->config['api'];
    }
    
    /**
     * Get application configuration
     */
    public function getApp(): array {
        return $this->config['app'];
    }
    
    /**
     * Get rate limiting configuration
     */
    public function getRateLimiting(): array {
        return $this->config['rate_limiting'];
    }
    
    /**
     * Get logging configuration
     */
    public function getLogging(): array {
        return $this->config['logging'];
    }
    
    /**
     * Get environment
     */
    public function getEnvironment(): string {
        return $this->environment;
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function isDebug(): bool {
        return $this->config['app']['debug'];
    }
    
    /**
     * Check if development mode is enabled
     */
    public function isDevelopment(): bool {
        return $this->environment === 'development';
    }
    
    /**
     * Check if production mode is enabled
     */
    public function isProduction(): bool {
        return $this->environment === 'production';
    }
    
    /**
     * Get all configuration
     */
    public function getAll(): array {
        return $this->config;
    }
}
?>
