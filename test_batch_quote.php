<?php
require_once 'config.php';
require_once 'common/UnifiedApiWrapper.php';

echo "=== TESTING POLYGON BATCH QUOTE ===\n\n";

try {
    $apiWrapper = new UnifiedApiWrapper();
    
    // Test with a few tickers
    $testTickers = ['AAPL', 'MSFT', 'GOOGL'];
    
    echo "Testing with tickers: " . implode(', ', $testTickers) . "\n\n";
    
    $startTime = microtime(true);
    $result = $apiWrapper->getPolygonBatchQuote($testTickers);
    $duration = round(microtime(true) - $startTime, 2);
    
    echo "Duration: {$duration}s\n";
    echo "Result count: " . count($result) . "\n";
    
    foreach ($result as $ticker => $data) {
        echo "\nTicker: {$ticker}\n";
        if (isset($data['lastQuote'])) {
            echo "  Last Quote: " . json_encode($data['lastQuote']) . "\n";
        }
        if (isset($data['prevDay'])) {
            echo "  Prev Day: " . json_encode($data['prevDay']) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
