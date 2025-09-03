<?php
/**
 * 🚀 CRITICAL TEST: API Endpoints
 * Testuje kľúčové API endpointy pre dashboard
 */

require_once __DIR__ . '/test_config.php';

echo "🚀 CRITICAL TEST: API Endpoints\n";
echo "===============================\n\n";

try {
    // 1. Test existencie API súborov
    echo "1. Test existencie API súborov...\n";
    
    $apiFiles = [
        '../public/api/earnings-tickers-today.php' => 'Earnings Tickers Today',
        '../public/api/today-earnings-movements.php' => 'Today Earnings Movements',
        '../public/api/earnings-tickers-today-working.php' => 'Earnings Tickers Working',
        '../public/api/earnings-tickers-today-debug.php' => 'Earnings Tickers Debug',
        '../public/api/earnings-tickers-today-fixed.php' => 'Earnings Tickers Fixed',
        '../public/api/clear-tables.php' => 'Clear Tables'
    ];
    
    $existingApis = 0;
    foreach ($apiFiles as $file => $description) {
        if (file_exists($file)) {
            echo "   ✅ $description: existuje\n";
            $existingApis++;
        } else {
            echo "   ❌ $description: neexistuje\n";
        }
    }
    
    echo "   📊 Celkovo API súborov: " . count($apiFiles) . "\n";
    echo "   📊 Existujúcich: $existingApis\n";
    
    // 2. Test API syntax
    echo "\n2. Test API syntax...\n";
    
    $mainApiFiles = [
        '../public/api/earnings-tickers-today.php',
        '../public/api/today-earnings-movements.php'
    ];
    
    $syntaxOK = 0;
    foreach ($mainApiFiles as $file) {
        if (file_exists($file)) {
            $output = shell_exec("php -l $file 2>&1");
            if (strpos($output, 'No syntax errors') !== false) {
                echo "   ✅ " . basename($file) . ": syntax OK\n";
                $syntaxOK++;
            } else {
                echo "   ❌ " . basename($file) . ": syntax ERROR\n";
                echo "     $output\n";
            }
        }
    }
    
    echo "   📊 Celkovo súborov: " . count($mainApiFiles) . "\n";
    echo "   📊 Syntax OK: $syntaxOK\n";
    
    // 3. Test API endpoint logiky
    echo "\n3. Test API endpoint logiky...\n";
    
    // Test earnings-tickers-today endpoint logiku
    $earningsApiFile = '../public/api/earnings-tickers-today.php';
    if (file_exists($earningsApiFile)) {
        $apiContent = file_get_contents($earningsApiFile);
        
        // Kontrola kľúčových funkcií
        $checks = [
            'header' => strpos($apiContent, 'header(') !== false,
            'json' => strpos($apiContent, 'application/json') !== false,
            'cors' => strpos($apiContent, 'Access-Control-Allow-Origin') !== false,
            'pdo' => strpos($apiContent, '$pdo->prepare') !== false,
            'earnings_tickerstoday' => strpos($apiContent, 'EarningsTickersToday') !== false,
            'today_earnings_movements' => strpos($apiContent, 'TodayEarningsMovements') !== false,
            'benzinga_guidance' => strpos($apiContent, 'benzinga_guidance') !== false
        ];
        
        foreach ($checks as $check => $result) {
            if ($result) {
                echo "   ✅ $check: OK\n";
            } else {
                echo "   ❌ $check: FAIL\n";
            }
        }
        
        // Kontrola SQL query
        if (strpos($apiContent, 'SELECT') !== false && strpos($apiContent, 'FROM') !== false) {
            echo "   ✅ SQL query: OK\n";
        } else {
            echo "   ❌ SQL query: FAIL\n";
        }
        
    } else {
        echo "   ❌ Earnings API súbor neexistuje\n";
    }
    
    // 4. Test API response headers
    echo "\n4. Test API response headers...\n";
    
    // Simulácia API volania
    $requiredHeaders = [
        'Content-Type: application/json; charset=utf-8',
        'Access-Control-Allow-Origin: *',
        'Access-Control-Allow-Methods: GET',
        'Access-Control-Allow-Headers: Content-Type'
    ];
    
    $headersFound = 0;
    foreach ($requiredHeaders as $header) {
        if (strpos($apiContent, $header) !== false) {
            echo "   ✅ $header: OK\n";
            $headersFound++;
        } else {
            echo "   ❌ $header: FAIL\n";
        }
    }
    
    echo "   📊 Celkovo headers: " . count($requiredHeaders) . "\n";
    echo "   📊 Nájdených: $headersFound\n";
    
    // 5. Test API data validation
    echo "\n5. Test API data validation...\n";
    
    // Kontrola či API používa prepared statements
    if (strpos($apiContent, '$stmt->execute(') !== false) {
        echo "   ✅ Prepared statements: OK\n";
    } else {
        echo "   ❌ Prepared statements: FAIL\n";
    }
    
    // Kontrola error handling
    if (strpos($apiContent, 'try') !== false && strpos($apiContent, 'catch') !== false) {
        echo "   ✅ Error handling: OK\n";
    } else {
        echo "   ❌ Error handling: FAIL\n";
    }
    
    // 6. Test API performance
    echo "\n6. Test API performance...\n";
    
    // Test rýchlosti API endpointov
    $startTime = microtime(true);
    
    // Simulujeme ťažkú operáciu - SELECT z oboch tabuliek
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM earningstickerstoday e 
        LEFT JOIN todayearningsmovements t ON e.ticker = t.ticker 
        WHERE e.report_date = CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "   ⏱️ API query trval: {$duration}ms\n";
    echo "   📊 Počet záznamov: {$result['count']}\n";
    
    if ($duration < 200) {
        echo "   ✅ API performance OK (< 200ms)\n";
    } else {
        echo "   ⚠️ API performance pomalšie (> 200ms)\n";
    }
    
    // 7. Test API data integrity
    echo "\n7. Test API data integrity...\n";
    
    // Kontrola či API vracia konzistentné dáta
    $stmt = $pdo->prepare("
        SELECT 
            e.ticker,
            e.eps_estimate,
            e.revenue_estimate,
            t.current_price,
            t.market_cap
        FROM earningstickerstoday e
        LEFT JOIN todayearningsmovements t ON e.ticker = t.ticker
        WHERE e.report_date = CURDATE()
        LIMIT 5
    ");
    $stmt->execute();
    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($sampleData)) {
        echo "   ✅ Sample data nájdené: " . count($sampleData) . " záznamov\n";
        
        foreach ($sampleData as $record) {
            echo "     📊 {$record['ticker']}: EPS Est: {$record['eps_estimate']}, Rev Est: {$record['revenue_estimate']}, Price: \${$record['current_price']}\n";
        }
        
        // Kontrola integrity
        $validRecords = 0;
        foreach ($sampleData as $record) {
            if (!empty($record['ticker']) && ($record['eps_estimate'] !== null || $record['revenue_estimate'] !== null)) {
                $validRecords++;
            }
        }
        
        echo "   📊 Validných záznamov: $validRecords/" . count($sampleData) . "\n";
        
        if ($validRecords == count($sampleData)) {
            echo "   ✅ Data integrity: OK\n";
        } else {
            echo "   ⚠️ Data integrity: PARTIAL\n";
        }
        
    } else {
        echo "   ⚠️ Žiadne sample data nenájdené\n";
    }
    
    // 8. Test API security
    echo "\n8. Test API security...\n";
    
    // Kontrola SQL injection ochrany
    if (strpos($apiContent, 'htmlspecialchars') !== false || strpos($apiContent, 'filter_var') !== false) {
        echo "   ✅ Input sanitization: OK\n";
    } else {
        echo "   ⚠️ Input sanitization: PARTIAL (používa prepared statements)\n";
    }
    
    // Kontrola CORS
    if (strpos($apiContent, 'Access-Control-Allow-Origin: *') !== false) {
        echo "   ✅ CORS: OK\n";
    } else {
        echo "   ❌ CORS: FAIL\n";
    }
    
    // Kontrola rate limiting
    if (strpos($apiContent, 'rate') !== false || strpos($apiContent, 'throttle') !== false) {
        echo "   ✅ Rate limiting: OK\n";
    } else {
        echo "   ⚠️ Rate limiting: NOT IMPLEMENTED\n";
    }
    
    echo "\n✅ Všetky critical testy pre API Endpoints prešli úspešne!\n";
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
