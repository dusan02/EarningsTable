<?php
require_once 'config.php';

echo "=== CHECKING SPECIFIC TICKERS ===\n";

try {
    // Check specific tickers that should match Investing.com
    $tickers = ['FN', 'BTDR', 'XP', 'BHP', 'PANW'];
    
    foreach ($tickers as $ticker) {
        $stmt = $pdo->prepare("SELECT ticker, eps_estimate, revenue_estimate, market_cap FROM TodayEarningsMovements WHERE ticker = ?");
        $stmt->execute([$ticker]);
        $row = $stmt->fetch();
        
        if ($row) {
            echo $ticker . ": EPS=" . ($row['eps_estimate'] ?: 'NULL') . 
                 ", Rev=" . ($row['revenue_estimate'] ?: 'NULL') . 
                 ", MC=" . ($row['market_cap'] ?: 'NULL') . "\n";
        } else {
            echo $ticker . ": NOT FOUND\n";
        }
    }
    
    // Check API endpoint
    echo "\n=== API ENDPOINT CHECK ===\n";
    $response = file_get_contents('http://localhost/api/earnings-tickers-today.php');
    $data = json_decode($response, true);
    
    if ($data && isset($data['data'])) {
        echo "API returned " . count($data['data']) . " records\n";
        
        // Check first few records for EPS estimates
        for ($i = 0; $i < min(5, count($data['data'])); $i++) {
            $item = $data['data'][$i];
            echo $item['ticker'] . ": EPS=" . ($item['eps_estimate'] ?: 'NULL') . 
                 ", Rev=" . ($item['revenue_estimate'] ?: 'NULL') . "\n";
        }
    } else {
        echo "API error or no data\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
