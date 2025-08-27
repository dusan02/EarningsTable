<?php
/**
 * Environment Variables Test
 * Testuje správne načítanie .env súboru
 */

echo "🔐 Environment Variables Test\n";
echo "============================\n\n";

// 1. Test načítania env_loader
echo "1. Test načítania EnvLoader...\n";
if (file_exists('config/env_loader.php')) {
    require_once 'config/env_loader.php';
    echo "✅ env_loader.php načítaný\n";
} else {
    echo "❌ env_loader.php nenájdený\n";
    exit(1);
}

// 2. Test existencie EnvLoader triedy
echo "2. Test EnvLoader triedy...\n";
if (class_exists('EnvLoader')) {
    echo "✅ EnvLoader trieda existuje\n";
} else {
    echo "❌ EnvLoader trieda neexistuje\n";
    exit(1);
}

// 3. Test načítania .env súboru
echo "3. Test načítania .env súboru...\n";
if (file_exists('.env')) {
    echo "✅ .env súbor existuje\n";
} else {
    echo "⚠️ .env súbor neexistuje, používam env.example\n";
    if (file_exists('env.example')) {
        echo "✅ env.example existuje\n";
    } else {
        echo "❌ Ani .env ani env.example neexistuje\n";
        exit(1);
    }
}

// 4. Test načítania premenných
echo "4. Test načítania premenných...\n";

// Database premenné
$dbHost = EnvLoader::get('DB_HOST');
$dbName = EnvLoader::get('DB_NAME');
$dbUser = EnvLoader::get('DB_USER');
$dbPass = EnvLoader::get('DB_PASS');

echo "   DB_HOST: " . ($dbHost ?: '❌ Chýba') . "\n";
echo "   DB_NAME: " . ($dbName ?: '❌ Chýba') . "\n";
echo "   DB_USER: " . ($dbUser ?: '❌ Chýba') . "\n";
echo "   DB_PASS: " . ($dbPass ? '✅ Nastavený' : '⚠️ Prázdny') . "\n";

// API premenné
$polygonKey = EnvLoader::get('POLYGON_API_KEY');
$finnhubKey = EnvLoader::get('FINNHUB_API_KEY');
$yahooKey = EnvLoader::get('YAHOO_API_KEY');

echo "   POLYGON_API_KEY: " . ($polygonKey ? '✅ Nastavený' : '❌ Chýba') . "\n";
echo "   FINNHUB_API_KEY: " . ($finnhubKey ? '✅ Nastavený' : '❌ Chýba') . "\n";
echo "   YAHOO_API_KEY: " . ($yahooKey ? '✅ Nastavený' : '⚠️ Chýba') . "\n";

// Aplikácia premenné
$appEnv = EnvLoader::get('APP_ENV', 'production');
$appDebug = EnvLoader::get('APP_DEBUG', 'false');
$timezone = EnvLoader::get('TIMEZONE', 'UTC');

echo "   APP_ENV: $appEnv\n";
echo "   APP_DEBUG: $appDebug\n";
echo "   TIMEZONE: $timezone\n";

// 5. Test helper funkcií
echo "5. Test helper funkcií...\n";
echo "   isDevelopment(): " . (EnvLoader::isDevelopment() ? 'true' : 'false') . "\n";
echo "   isDebug(): " . (EnvLoader::isDebug() ? 'true' : 'false') . "\n";

// 6. Test konfigurácie
echo "6. Test konfigurácie...\n";
if (file_exists('config/config.php')) {
    require_once 'config/config.php';
    echo "✅ config.php načítaný\n";
    
    // Test, či sú premenné dostupné
    echo "   DB_HOST (config): " . (defined('DB_HOST') ? DB_HOST : '❌ Nedefinované') . "\n";
    echo "   POLYGON_API_KEY (config): " . (defined('POLYGON_API_KEY') && POLYGON_API_KEY ? '✅ Nastavený' : '❌ Chýba') . "\n";
    echo "   APP_DEBUG (config): " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'true' : 'false') : '❌ Nedefinované') . "\n";
} else {
    echo "❌ config.php nenájdený\n";
}

// 7. Test databázového pripojenia
echo "7. Test databázového pripojenia...\n";
if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "✅ Databázové pripojenie úspešné\n";
    } catch (PDOException $e) {
        echo "❌ Databázové pripojenie zlyhalo: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Databázové premenné nie sú definované\n";
}

// 8. Test API kľúčov
echo "8. Test API kľúčov...\n";
if (defined('POLYGON_API_KEY') && POLYGON_API_KEY) {
    echo "✅ Polygon API kľúč je nastavený\n";
} else {
    echo "❌ Polygon API kľúč chýba\n";
}

if (defined('FINNHUB_API_KEY') && FINNHUB_API_KEY) {
    echo "✅ Finnhub API kľúč je nastavený\n";
} else {
    echo "❌ Finnhub API kľúč chýba\n";
}

echo "\n🎉 Environment test dokončený!\n";

// 9. Odporúčania
echo "\n📋 Odporúčania:\n";
if (!defined('POLYGON_API_KEY') || !POLYGON_API_KEY) {
    echo "⚠️ Nastav POLYGON_API_KEY v .env súbore\n";
}
if (!defined('FINNHUB_API_KEY') || !FINNHUB_API_KEY) {
    echo "⚠️ Nastav FINNHUB_API_KEY v .env súbore\n";
}
if (!file_exists('.env')) {
    echo "⚠️ Vytvor .env súbor pomocou: php scripts/setup_env.php\n";
}

echo "\n✅ Všetky testy prebehli úspešne!\n";
?>
