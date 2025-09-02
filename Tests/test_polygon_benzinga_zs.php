<?php
/**
 * Test Polygon API s Benzinga rozšírením pre ticker ZS
 * Testuje Polygon Benzinga Guidance endpoint: /benzinga/v1/guidance
 */

require_once __DIR__ . '/../config.php';

echo "=== POLYGON BENZINGA GUIDANCE API TEST - TICKER ZS ===\n";
echo "📅 Date: " . date('Y-m-d H:i:s') . "\n\n";

// 1) Check if Polygon API key is set
if (empty(POLYGON_API_KEY)) {
    echo "❌ POLYGON_API_KEY is not set!\n";
    exit(1);
}

echo "✅ POLYGON_API_KEY is set: " . substr(POLYGON_API_KEY, 0, 10) . "...\n";

// 2) Test Polygon Benzinga Guidance endpoint
echo "\n=== TESTING POLYGON BENZINGA GUIDANCE ENDPOINT ===\n";

$benzingaUrl = "https://api.polygon.io/benzinga/v1/guidance?" . http_build_query([
    'apiKey' => POLYGON_API_KEY,
    'ticker' => 'ZS',
    'limit' => 100,
    'sort' => 'date.desc'
]);

echo "🔍 Testing Polygon Benzinga Guidance API\n";
echo "URL: " . substr($benzingaUrl, 0, 80) . "...\n";

try {
    $startTime = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $benzingaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EarningsTable/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification for testing
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    if ($error) {
        echo "❌ cURL Error: {$error}\n";
    } elseif ($httpCode !== 200) {
        echo "❌ HTTP Error: {$httpCode}\n";
        echo "Response: " . substr($response, 0, 200) . "...\n";
        
        // Check if it's a plan upgrade error
        if ($httpCode === 403 && strpos($response, 'NOT_AUTHORIZED') !== false) {
            echo "\n⚠️  PLAN UPGRADE REQUIRED:\n";
            echo "Benzinga Guidance API vyžaduje vyšší Polygon plan.\n";
            echo "Aktuálny plan neobsahuje Benzinga data.\n";
            echo "Upgrade na: https://polygon.io/pricing\n";
        }
    } else {
        echo "✅ Polygon Benzinga Guidance API Success in {$executionTime}ms\n";
        
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "📊 Response structure: " . implode(', ', array_keys($data)) . "\n";
            
            if (isset($data['results'])) {
                echo "🎯 Found guidance data: " . count($data['results']) . " records\n";
                
                if (isset($data['count'])) {
                    echo "📈 Total count: " . $data['count'] . "\n";
                }
                
                // Look for ZS ticker guidance
                $zsFound = false;
                foreach ($data['results'] as $guidance) {
                    if (isset($guidance['ticker']) && $guidance['ticker'] === 'ZS') {
                        echo "\n🎉 FOUND ZS GUIDANCE DATA!\n";
                        echo "Ticker: " . ($guidance['ticker'] ?? 'N/A') . "\n";
                        echo "Company: " . ($guidance['company_name'] ?? 'N/A') . "\n";
                        echo "Date: " . ($guidance['date'] ?? 'N/A') . "\n";
                        echo "Time: " . ($guidance['time'] ?? 'N/A') . "\n";
                        echo "Fiscal Period: " . ($guidance['fiscal_period'] ?? 'N/A') . "\n";
                        echo "Fiscal Year: " . ($guidance['fiscal_year'] ?? 'N/A') . "\n";
                        
                        if (isset($guidance['estimated_eps_guidance'])) {
                            echo "EPS Guidance: " . ($guidance['estimated_eps_guidance'] ?? 'N/A') . "\n";
                        }
                        
                        if (isset($guidance['estimated_revenue_guidance'])) {
                            echo "Revenue Guidance: " . ($guidance['estimated_revenue_guidance'] ?? 'N/A') . "\n";
                        }
                        
                        if (isset($guidance['min_eps_guidance']) && isset($guidance['max_eps_guidance'])) {
                            echo "EPS Range: " . ($guidance['min_eps_guidance'] ?? 'N/A') . " - " . ($guidance['max_eps_guidance'] ?? 'N/A') . "\n";
                        }
                        
                        if (isset($guidance['min_revenue_guidance']) && isset($guidance['max_revenue_guidance'])) {
                            echo "Revenue Range: " . ($guidance['min_revenue_guidance'] ?? 'N/A') . " - " . ($guidance['max_revenue_guidance'] ?? 'N/A') . "\n";
                        }
                        
                        if (isset($guidance['importance'])) {
                            echo "Importance: " . $guidance['importance'] . "/5\n";
                        }
                        
                        if (isset($guidance['positioning'])) {
                            echo "Positioning: " . $guidance['positioning'] . "\n";
                        }
                        
                        if (isset($guidance['notes'])) {
                            echo "Notes: " . $guidance['notes'] . "\n";
                        }
                        
                        echo "\nFull ZS guidance data:\n";
                        print_r($guidance);
                        $zsFound = true;
                        break;
                    }
                }
                
                if (!$zsFound) {
                    echo "\n❌ No ZS ticker found in guidance data\n";
                    echo "Available tickers:\n";
                    $tickers = [];
                    foreach ($data['results'] as $guidance) {
                        if (isset($guidance['ticker'])) {
                            $tickers[] = $guidance['ticker'];
                        }
                    }
                    echo implode(', ', array_unique($tickers)) . "\n";
                }
                
                // Show sample of other guidance data
                if (count($data['results']) > 0) {
                    echo "\n📋 SAMPLE GUIDANCE DATA:\n";
                    $sample = array_slice($data['results'], 0, 3);
                    foreach ($sample as $guidance) {
                        echo "- " . ($guidance['ticker'] ?? 'N/A') . ": " . ($guidance['company_name'] ?? 'N/A') . " (" . ($guidance['date'] ?? 'N/A') . ")\n";
                    }
                }
                
            } else {
                echo "⚠️  No 'results' key in response\n";
                echo "Full response:\n";
                print_r($data);
            }
        } else {
            echo "❌ JSON Error: " . json_last_error_msg() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// 3) Test with broader search (without ticker filter)
echo "\n=== TESTING BROADER GUIDANCE SEARCH ===\n";

$broadUrl = "https://api.polygon.io/benzinga/v1/guidance?" . http_build_query([
    'apiKey' => POLYGON_API_KEY,
    'limit' => 10,
    'sort' => 'date.desc'
]);

echo "🔍 Testing broader guidance search\n";
echo "URL: " . substr($broadUrl, 0, 80) . "...\n";

try {
    $startTime = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $broadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'EarningsTable/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    if ($error) {
        echo "❌ cURL Error: {$error}\n";
    } elseif ($httpCode !== 200) {
        echo "❌ HTTP Error: {$httpCode}\n";
        echo "Response: " . substr($response, 0, 200) . "...\n";
    } else {
        echo "✅ Broader search Success in {$executionTime}ms\n";
        
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($data['results'])) {
            echo "📊 Found " . count($data['results']) . " guidance records\n";
            
            // Show recent guidance
            echo "\n📋 RECENT GUIDANCE RECORDS:\n";
            foreach ($data['results'] as $guidance) {
                echo "- " . ($guidance['ticker'] ?? 'N/A') . " (" . ($guidance['date'] ?? 'N/A') . "): " . ($guidance['company_name'] ?? 'N/A') . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ Polygon Benzinga Guidance API test completed!\n";
echo "🔑 Used Polygon API key for Benzinga data\n";
echo "📚 Endpoint: /benzinga/v1/guidance\n";
?>
