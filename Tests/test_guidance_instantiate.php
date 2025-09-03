<?php
echo "🔍 Testing BenzingaGuidance Instantiation\n";
echo "=========================================\n\n";

// Load config
require_once 'config.php';

try {
    echo "1. Creating BenzingaGuidance instance...\n";
    $benzinga = new BenzingaGuidance();
    echo "✅ BenzingaGuidance instance created successfully\n";
    
    echo "\n2. Testing getTickersFromStaticCron...\n";
    $tickers = $benzinga->getTickersFromStaticCron();
    echo "✅ Found " . count($tickers) . " tickers\n";
    
    if (count($tickers) > 0) {
        echo "   Sample tickers: " . implode(', ', array_slice($tickers, 0, 5)) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Test completed\n";
?>
