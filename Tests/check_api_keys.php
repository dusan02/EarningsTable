<?php
require_once 'config.php';

echo "=== API KEYS CHECK ===\n\n";

echo "FINNHUB_API_KEY: " . FINNHUB_API_KEY . "\n";
echo "POLYGON_API_KEY: " . POLYGON_API_KEY . "\n";

echo "\n=== ENV LOADER TEST ===\n";
echo "POLYGON_API_KEY from EnvLoader: " . EnvLoader::get('POLYGON_API_KEY', 'NOT_FOUND') . "\n";
?>
