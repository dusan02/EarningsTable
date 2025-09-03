<?php
/**
 * Test Finnhub API s dnešnými tickermi
 * Kontroluje eps_actual a revenue_actual
 */

require_once __DIR__ . '/test_config.php';

echo "🔍 Testing Finnhub API with Today's Earnings Tickers\n";
echo "==================================================\n\n";

// 1. Získaj dostupné tickery
echo "1. Getting available earnings tickers...\n";
try {
    // Skús najprv dnešný dátum
    $stmt = $pdo->query("SELECT ticker FROM earningstickerstoday WHERE report_date = CURDATE()");
    $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tickers)) {
        echo "   ⚠️ No tickers found for today, using latest available data\n";
        $stmt = $pdo->query("SELECT ticker FROM earningstickerstoday ORDER BY report_date DESC LIMIT 20");
        $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    echo "   ✅ Found " . count($tickers) . " tickers\n";
    
} catch (Exception $e) {
    echo "   ❌ Error getting tickers: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($tickers)) {
    echo "   ❌ No tickers found in database\n";
    exit(1);
}

// 2. Test Finnhub API
echo "\n2. Testing Finnhub API...\n";

$finnhubApiKey = 'your_finnhub_api_key_here'; // Nahraď skutočným kľúčom
$baseUrl = 'https://finnhub.io/api/v1/quote';

$results = [];
$epsActualCount = 0;
$revenueActualCount = 0;

foreach (array_slice($tickers, 0, 10) as $ticker) { // Testujem len prvých 10
    echo "   Testing $ticker... ";
    
    try {
        $url = $baseUrl . "?symbol=$ticker&token=$finnhubApiKey";
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            echo "❌ Failed\n";
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['c']) && $data['c'] > 0) {
            echo "✅ Price: $" . number_format($data['c'], 2) . "\n";
            
            // Kontroluj eps_actual a revenue_actual v databáze
            $stmt = $pdo->prepare("SELECT eps_actual, revenue_actual FROM todayearningsmovements WHERE ticker = ?");
            $stmt->execute([$ticker]);
            $earningsData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($earningsData) {
                if (!empty($earningsData['eps_actual']) && $earningsData['eps_actual'] !== 'N/A') {
                    $epsActualCount++;
                    echo "      📊 EPS Actual: " . $earningsData['eps_actual'] . "\n";
                }
                
                if (!empty($earningsData['revenue_actual']) && $earningsData['revenue_actual'] !== 'N/A') {
                    $revenueActualCount++;
                    echo "      💰 Revenue Actual: " . $earningsData['revenue_actual'] . "\n";
                }
            }
            
            $results[$ticker] = $data;
        } else {
            echo "❌ No price data\n";
        }
        
        usleep(100000); // 0.1s delay
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// 3. Výsledky
echo "\n3. Results Summary:\n";
echo "==================\n";
echo "Total tickers tested: " . count($results) . "\n";
echo "Tickers with EPS Actual: $epsActualCount\n";
echo "Tickers with Revenue Actual: $revenueActualCount\n";

if (count($results) > 0) {
    echo "Success rate: " . round((count($results) / min(10, count($tickers))) * 100, 1) . "%\n";
} else {
    echo "Success rate: 0% (no successful API calls)\n";
}

// 4. Detailné dáta
if (!empty($results)) {
    echo "\n4. Detailed Data:\n";
    foreach ($results as $ticker => $data) {
        echo "$ticker: $" . number_format($data['c'], 2) . " (Change: " . number_format($data['d'], 2) . ")\n";
    }
} else {
    echo "\n4. No successful API calls to display\n";
}

echo "\n✅ Finnhub API test completed!\n";
?>
