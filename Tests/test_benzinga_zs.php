<?php
/**
 * Test Benzinga API s tickerom ZS
 * Testuje Corporate Guidance API s Polygon API kľúčom
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Benzinga.php';

echo "=== BENZINGA API TEST - TICKER ZS ===\n";
echo "📅 Date: " . date('Y-m-d H:i:s') . "\n\n";

// 1) Check if Polygon API key is set (Benzinga používa ten istý kľúč)
if (empty(POLYGON_API_KEY)) {
    echo "❌ POLYGON_API_KEY is not set!\n";
    echo "Please add your Polygon API key to config/production.env:\n";
    echo "POLYGON_API_KEY=your_polygon_api_key_here\n\n";
    exit(1);
}

echo "✅ POLYGON_API_KEY is set: " . substr(POLYGON_API_KEY, 0, 10) . "...\n";

// 2) Create Benzinga client with Polygon API key
$client = new BenzingaClient(POLYGON_API_KEY);
echo "✅ Benzinga client created with Polygon API key\n";

// 3) Test API with ticker ZS
echo "\n=== TESTING BENZINGA API ===\n";

// Get today's date in NY timezone
$timezone = new DateTimeZone('America/New_York');
$today = new DateTime('now', $timezone);
$from = $today->format('Y-m-d');
$to = $today->format('Y-m-d');

echo "🔍 Fetching guidance for date: {$from}\n";
echo "🎯 Looking for ticker: ZS\n";
echo "🔑 Using API key: " . substr(POLYGON_API_KEY, 0, 10) . "...\n";

try {
    $startTime = microtime(true);
    
    // Fetch guidance data
    $result = $client->getGuidance($from, $to, 1, 100);
    
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    if (!$result['success']) {
        echo "❌ API call failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    
    echo "✅ API call successful in {$executionTime}ms\n";
    
    $data = $result['data'];
    
    // Check response structure
    if (!isset($data['guidance'])) {
        echo "⚠️  No 'guidance' key in response\n";
        echo "Response keys: " . implode(', ', array_keys($data)) . "\n";
        echo "Full response:\n";
        print_r($data);
        exit(1);
    }
    
    $guidanceData = $data['guidance'];
    echo "📊 Found " . count($guidanceData) . " guidance records\n";
    
    // Look for ZS ticker
    $zsGuidance = null;
    foreach ($guidanceData as $guidance) {
        if (isset($guidance['ticker']) && $guidance['ticker'] === 'ZS') {
            $zsGuidance = $guidance;
            break;
        }
    }
    
    if ($zsGuidance) {
        echo "\n🎯 FOUND ZS GUIDANCE DATA:\n";
        echo "Ticker: " . ($zsGuidance['ticker'] ?? 'N/A') . "\n";
        echo "Company: " . ($zsGuidance['company_name'] ?? 'N/A') . "\n";
        echo "Date: " . ($zsGuidance['date'] ?? 'N/A') . "\n";
        echo "Period: " . ($zsGuidance['period'] ?? 'N/A') . "\n";
        
        if (isset($zsGuidance['eps_guidance'])) {
            echo "EPS Guidance: " . ($zsGuidance['eps_guidance'] ?? 'N/A') . "\n";
        }
        
        if (isset($zsGuidance['revenue_guidance'])) {
            echo "Revenue Guidance: " . ($zsGuidance['revenue_guidance'] ?? 'N/A') . "\n";
        }
        
        echo "\nFull data:\n";
        print_r($zsGuidance);
        
    } else {
        echo "\n❌ No guidance data found for ZS ticker\n";
        echo "Available tickers:\n";
        $tickers = [];
        foreach ($guidanceData as $guidance) {
            if (isset($guidance['ticker'])) {
                $tickers[] = $guidance['ticker'];
            }
        }
        echo implode(', ', array_unique($tickers)) . "\n";
    }
    
    // Show sample of other guidance data
    if (count($guidanceData) > 0) {
        echo "\n📋 SAMPLE GUIDANCE DATA:\n";
        $sample = array_slice($guidanceData, 0, 3);
        foreach ($sample as $guidance) {
            echo "- " . ($guidance['ticker'] ?? 'N/A') . ": " . ($guidance['company_name'] ?? 'N/A') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Benzinga API test completed successfully!\n";
echo "🔑 Used Polygon API key for Benzinga API\n";
?>
