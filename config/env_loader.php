<?php
/**
 * Environment Variables Loader
 * Načítava premenné z .env súboru
 */

class EnvLoader {
    private static $loaded = false;
    private static $envFile = '.env';
    
    /**
     * Načíta .env súbor a nastaví premenné
     */
    public static function load($envFile = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($envFile) {
            self::$envFile = $envFile;
        }
        
        $envPath = __DIR__ . '/../' . self::$envFile;
        
        if (!file_exists($envPath)) {
            // Ak .env neexistuje, skús .env.example
            $examplePath = __DIR__ . '/../env.example';
            if (file_exists($examplePath)) {
                logConfigError('env_loader', '.env súbor neexistuje, používam env.example');
                $envPath = $examplePath;
            } else {
                logConfigError('env_loader', 'Žiadny .env súbor nenájdený!');
                return;
            }
        }
        
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Preskoči komentáre
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parsuj KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Odstráň úvodzovky
                if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                    $value = $matches[1];
                }
                
                // Nastav premennú
                if (!defined($key)) {
                    define($key, $value);
                }
                
                // Nastav aj $_ENV
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
        
        self::$loaded = true;
    }
    
    /**
     * Získa hodnotu z environment premennej
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return $_ENV[$key] ?? $default;
    }
    
    /**
     * Kontroluje, či je aplikácia v development móde
     */
    public static function isDevelopment() {
        return self::get('APP_ENV', 'production') === 'development';
    }
    
    /**
     * Kontroluje, či je debug povolený
     */
    public static function isDebug() {
        return self::get('APP_DEBUG', 'false') === 'true';
    }
}

// Automaticky načítaj .env pri include
EnvLoader::load();
?>
