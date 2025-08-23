<?php
require_once 'config.php';

echo "=== CHECKING MISSING TICKERS IN API ===\n";

try {
    // Check specific missing tickers in database
    $missingTickers = ['BHP', 'GMBXF', 'PPERY', 'TLK', 'MTNOY', 'BPHLY', 'VRNA'];
    
    foreach ($missingTickers as $ticker) {
        $stmt = $pdo->prepare("SELECT ticker, current_price, market_cap, size FROM TodayEarningsMovements WHERE ticker = ?");
        $stmt->execute([$ticker]);
        $row = $stmt->fetch();
        
        if ($row) {
            echo "✅ {$ticker}: Price=$" . ($row['current_price'] ?: 'NULL') . 
                 ", MC=" . ($row['market_cap'] ? number_format($row['market_cap'] / 1000000000, 1) . 'B' : 'NULL') . 
                 ", Size=" . ($row['size'] ?: 'NULL') . "\n";
        } else {
            echo "❌ {$ticker}: NOT FOUND\n";
        }
    }
    
    // Check API endpoint
    echo "\n=== API ENDPOINT CHECK ===\n";
    $response = file_get_contents('http://localhost/api/earnings-tickers-today.php');
    $data = json_decode($response, true);
    
    if ($data && isset($data['data'])) {
        echo "API returned " . count($data['data']) . " records\n";
        
        // Look for missing tickers in API response
        $foundTickers = [];
        foreach ($data['data'] as $item) {
            if (in_array($item['ticker'], $missingTickers)) {
                $foundTickers[] = $item['ticker'];
                echo "✅ {$item['ticker']} found in API\n";
            }
        }
        
        $notFound = array_diff($missingTickers, $foundTickers);
        foreach ($notFound as $ticker) {
            echo "❌ {$ticker} NOT found in API\n";
        }
        
    } else {
        echo "API error or no data\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
