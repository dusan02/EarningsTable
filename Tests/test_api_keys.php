<?php
echo "🔍 Testing API Keys\n";
echo "==================\n\n";

// Load config
require_once 'config.php';

echo "1. Checking API keys...\n";

if (defined('POLYGON_API_KEY')) {
    echo "✅ POLYGON_API_KEY is defined\n";
    echo "   Length: " . strlen(POLYGON_API_KEY) . " characters\n";
    echo "   Starts with: " . substr(POLYGON_API_KEY, 0, 4) . "...\n";
} else {
    echo "❌ POLYGON_API_KEY is not defined\n";
}

if (defined('BENZINGA_API_KEY')) {
    echo "✅ BENZINGA_API_KEY is defined\n";
    echo "   Length: " . strlen(BENZINGA_API_KEY) . " characters\n";
    echo "   Starts with: " . substr(BENZINGA_API_KEY, 0, 4) . "...\n";
} else {
    echo "❌ BENZINGA_API_KEY is not defined\n";
}

if (defined('FINNHUB_API_KEY')) {
    echo "✅ FINNHUB_API_KEY is defined\n";
    echo "   Length: " . strlen(FINNHUB_API_KEY) . " characters\n";
    echo "   Starts with: " . substr(FINNHUB_API_KEY, 0, 4) . "...\n";
} else {
    echo "❌ FINNHUB_API_KEY is not defined\n";
}

echo "\n2. Checking database constants...\n";
if (defined('DB_NAME')) {
    echo "✅ DB_NAME: " . DB_NAME . "\n";
} else {
    echo "❌ DB_NAME is not defined\n";
}

echo "\n✅ Test completed\n";
?>
