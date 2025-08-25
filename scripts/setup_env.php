<?php
/**
 * Environment Setup Script
 * Pomáha nastaviť .env súbor
 */

echo "🔐 Environment Setup Script\n";
echo "==========================\n\n";

// 1. Skontroluj, či už .env existuje
if (file_exists('.env')) {
    echo "⚠️  .env súbor už existuje!\n";
    echo "Chceš ho prepísať? (y/N): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim($line) !== 'y' && trim($line) !== 'Y') {
        echo "❌ Setup zrušený.\n";
        exit(0);
    }
}

// 2. Načítaj env.example ako základ
if (!file_exists('env.example')) {
    echo "❌ env.example súbor nenájdený!\n";
    exit(1);
}

$envContent = file_get_contents('env.example');

// 3. Získaj API kľúče od používateľa
echo "\n🔑 API Keys Setup:\n";
echo "Zadaj svoje API kľúče (alebo stlač Enter pre preskočenie):\n\n";

// Polygon API Key
echo "Polygon API Key: ";
$handle = fopen("php://stdin", "r");
$polygonKey = trim(fgets($handle));
fclose($handle);

if (!empty($polygonKey)) {
    $envContent = str_replace('your_polygon_api_key_here', $polygonKey, $envContent);
}

// Finnhub API Key
echo "Finnhub API Key: ";
$handle = fopen("php://stdin", "r");
$finnhubKey = trim(fgets($handle));
fclose($handle);

if (!empty($finnhubKey)) {
    $envContent = str_replace('your_finnhub_api_key_here', $finnhubKey, $envContent);
}

// Yahoo API Key
echo "Yahoo API Key: ";
$handle = fopen("php://stdin", "r");
$yahooKey = trim(fgets($handle));
fclose($handle);

if (!empty($yahooKey)) {
    $envContent = str_replace('your_yahoo_api_key_here', $yahooKey, $envContent);
}

// 4. Generuj náhodný APP_KEY
$appKey = bin2hex(random_bytes(16));
$envContent = str_replace('your_32_character_random_key_here', $appKey, $envContent);

// 5. Získaj databázové údaje
echo "\n🗄️ Database Setup:\n";
echo "Zadaj databázové údaje:\n\n";

echo "Database Host (localhost): ";
$handle = fopen("php://stdin", "r");
$dbHost = trim(fgets($handle));
fclose($handle);
if (!empty($dbHost)) {
    $envContent = str_replace('DB_HOST=localhost', "DB_HOST=$dbHost", $envContent);
}

echo "Database Name (earnings_db): ";
$handle = fopen("php://stdin", "r");
$dbName = trim(fgets($handle));
fclose($handle);
if (!empty($dbName)) {
    $envContent = str_replace('DB_NAME=earnings_db', "DB_NAME=$dbName", $envContent);
}

echo "Database User (root): ";
$handle = fopen("php://stdin", "r");
$dbUser = trim(fgets($handle));
fclose($handle);
if (!empty($dbUser)) {
    $envContent = str_replace('DB_USER=root', "DB_USER=$dbUser", $envContent);
}

echo "Database Password: ";
$handle = fopen("php://stdin", "r");
$dbPass = trim(fgets($handle));
fclose($handle);
if (!empty($dbPass)) {
    $envContent = str_replace('DB_PASS=', "DB_PASS=$dbPass", $envContent);
}

// 6. Získaj environment
echo "\n🌍 Environment Setup:\n";
echo "Environment (development/production): ";
$handle = fopen("php://stdin", "r");
$env = trim(fgets($handle));
fclose($handle);

if (!empty($env) && in_array($env, ['development', 'production'])) {
    $envContent = str_replace('APP_ENV=development', "APP_ENV=$env", $envContent);
    
    if ($env === 'production') {
        $envContent = str_replace('APP_DEBUG=true', 'APP_DEBUG=false', $envContent);
        $envContent = str_replace('SESSION_SECURE=false', 'SESSION_SECURE=true', $envContent);
        $envContent = str_replace('COOKIE_SECURE=false', 'COOKIE_SECURE=true', $envContent);
    }
}

// 7. Zapíš .env súbor
if (file_put_contents('.env', $envContent)) {
    echo "\n✅ .env súbor úspešne vytvorený!\n";
    echo "📁 Súbor: .env\n";
    echo "🔒 Obsahuje citlivé údaje - NIKDY necommitovať do Git!\n\n";
    
    // 8. Test environment loader
    echo "🧪 Testujem environment loader...\n";
    require_once 'config/env_loader.php';
    
    if (class_exists('EnvLoader')) {
        echo "✅ Environment loader funguje správne!\n";
        echo "🔑 Polygon API Key: " . (EnvLoader::get('POLYGON_API_KEY') ? '✅ Nastavený' : '❌ Chýba') . "\n";
        echo "🔑 Finnhub API Key: " . (EnvLoader::get('FINNHUB_API_KEY') ? '✅ Nastavený' : '❌ Chýba') . "\n";
        echo "🗄️ Database: " . EnvLoader::get('DB_HOST') . "/" . EnvLoader::get('DB_NAME') . "\n";
        echo "🌍 Environment: " . EnvLoader::get('APP_ENV') . "\n";
    } else {
        echo "❌ Environment loader nefunguje!\n";
    }
    
} else {
    echo "❌ Chyba pri vytváraní .env súboru!\n";
    exit(1);
}

echo "\n🎉 Environment setup dokončený!\n";
echo "Teraz môžeš bezpečne používať aplikáciu.\n";
?>
