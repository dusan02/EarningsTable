<?php
/**
 * 📊 TEST: Guidance Calculation Speed
 * Testuje rýchlosť výpočtov guidance
 */

require_once __DIR__ . '/test_config.php';

echo "📊 TEST: Guidance Calculation Speed\n";
echo "==================================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Rýchlosť základných guidance výpočtov
echo "📊 Test 1: Rýchlosť základných guidance výpočtov\n";
echo "-----------------------------------------------\n";

$totalTests++;
try {
    $iterations = 100;
    $totalTime = 0;
    
    echo "   📋 Testovanie {$iterations} iterácií základných výpočtov:\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $startTime = microtime(true);
        
        // Simulácia základného guidance výpočtu
        $epsEstimate = 1.25;
        $epsGuidance = 1.50;
        $revenueEstimate = 1000000000;
        $revenueGuidance = 1200000000;
        
        $epsSurprise = (($epsGuidance - $epsEstimate) / $epsEstimate) * 100;
        $revenueSurprise = (($revenueGuidance - $revenueEstimate) / $revenueEstimate) * 100;
        
        $endTime = microtime(true);
        $totalTime += ($endTime - $startTime) * 1000; // v milisekundách
    }
    
    $averageTime = round($totalTime / $iterations, 4);
    echo "   📊 Priemerný čas výpočtu: {$averageTime}ms\n";
    
    if ($averageTime <= 0.1) { // 0.1ms
        echo "   ✅ Veľmi rýchly výpočet\n";
        $passedTests++;
        $testResults[] = ['test' => 'Basic Calculation Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 1.0) { // 1ms
        echo "   ✅ Rýchly výpočet\n";
        $passedTests++;
        $testResults[] = ['test' => 'Basic Calculation Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 5.0) { // 5ms
        echo "   ⚠️  Priemerný výpočet\n";
        $testResults[] = ['test' => 'Basic Calculation Speed', 'status' => 'WARNING', 'value' => $averageTime];
    } else {
        echo "   ❌ Pomalý výpočet\n";
        $testResults[] = ['test' => 'Basic Calculation Speed', 'status' => 'FAIL', 'value' => $averageTime];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Basic Calculation Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Rýchlosť databázových dotazov pre guidance
echo "\n📊 Test 2: Rýchlosť databázových dotazov pre guidance\n";
echo "----------------------------------------------------\n";

$totalTests++;
try {
    $iterations = 10;
    $totalTime = 0;
    
    echo "   📋 Testovanie {$iterations} iterácií databázových dotazov:\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $startTime = microtime(true);
        
        // Komplexný dotaz pre guidance dáta
        $stmt = $pdo->query("
            SELECT 
                g.ticker,
                g.fiscal_period,
                g.fiscal_year,
                g.estimated_eps_guidance,
                g.estimated_revenue_guidance,
                g.eps_guide_vs_consensus_pct,
                g.revenue_guide_vs_consensus_pct,
                e.eps_estimate,
                e.revenue_estimate
            FROM benzinga_guidance g
            LEFT JOIN earningstickerstoday e ON g.ticker = e.ticker 
                AND g.fiscal_period = e.fiscal_period 
                AND g.fiscal_year = e.fiscal_year
            WHERE g.fiscal_year >= 2024
            ORDER BY g.last_updated DESC
            LIMIT 50
        ");
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $queryTime = ($endTime - $startTime) * 1000;
        $totalTime += $queryTime;
        
        echo "   📊 Iterácia {$i}: {$queryTime}ms (" . count($results) . " záznamov)\n";
    }
    
    $averageTime = round($totalTime / $iterations, 2);
    echo "   📊 Priemerný čas dotazu: {$averageTime}ms\n";
    
    if ($averageTime <= 50) { // 50ms
        echo "   ✅ Veľmi rýchly databázový dotaz\n";
        $passedTests++;
        $testResults[] = ['test' => 'Database Query Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 200) { // 200ms
        echo "   ✅ Rýchly databázový dotaz\n";
        $passedTests++;
        $testResults[] = ['test' => 'Database Query Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 500) { // 500ms
        echo "   ⚠️  Priemerný databázový dotaz\n";
        $testResults[] = ['test' => 'Database Query Speed', 'status' => 'WARNING', 'value' => $averageTime];
    } else {
        echo "   ❌ Pomalý databázový dotaz\n";
        $testResults[] = ['test' => 'Database Query Speed', 'status' => 'FAIL', 'value' => $averageTime];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Database Query Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Rýchlosť batch výpočtov guidance surprise
echo "\n📊 Test 3: Rýchlosť batch výpočtov guidance surprise\n";
echo "----------------------------------------------------\n";

$totalTests++;
try {
    // Načítanie testovacích dát
    $stmt = $pdo->query("
        SELECT 
            g.ticker,
            g.estimated_eps_guidance,
            g.estimated_revenue_guidance,
            e.eps_estimate,
            e.revenue_estimate
        FROM benzinga_guidance g
        LEFT JOIN earningstickerstoday e ON g.ticker = e.ticker 
            AND g.fiscal_period = e.fiscal_period 
            AND g.fiscal_year = e.fiscal_year
        WHERE g.estimated_eps_guidance IS NOT NULL 
        AND e.eps_estimate IS NOT NULL 
        AND e.eps_estimate != 0
        LIMIT 20
    ");
    
    $testData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($testData) > 0) {
        $iterations = 5;
        $totalTime = 0;
        
        echo "   📋 Testovanie {$iterations} iterácií batch výpočtov pre " . count($testData) . " záznamov:\n";
        
        for ($i = 1; $i <= $iterations; $i++) {
            $startTime = microtime(true);
            
            // Batch výpočet guidance surprise
            foreach ($testData as $record) {
                if ($record['eps_estimate'] != 0) {
                    $epsSurprise = (($record['estimated_eps_guidance'] - $record['eps_estimate']) / $record['eps_estimate']) * 100;
                }
                if ($record['revenue_estimate'] != 0) {
                    $revenueSurprise = (($record['estimated_revenue_guidance'] - $record['revenue_estimate']) / $record['revenue_estimate']) * 100;
                }
            }
            
            $endTime = microtime(true);
            $batchTime = ($endTime - $startTime) * 1000;
            $totalTime += $batchTime;
            
            echo "   📊 Iterácia {$i}: {$batchTime}ms\n";
        }
        
        $averageTime = round($totalTime / $iterations, 2);
        $timePerRecord = round($averageTime / count($testData), 4);
        echo "   📊 Priemerný čas batch: {$averageTime}ms\n";
        echo "   📊 Čas na záznam: {$timePerRecord}ms\n";
        
        if ($timePerRecord <= 0.1) { // 0.1ms per record
            echo "   ✅ Veľmi rýchly batch výpočet\n";
            $passedTests++;
            $testResults[] = ['test' => 'Batch Calculation Speed', 'status' => 'PASS', 'value' => $timePerRecord];
        } elseif ($timePerRecord <= 0.5) { // 0.5ms per record
            echo "   ✅ Rýchly batch výpočet\n";
            $passedTests++;
            $testResults[] = ['test' => 'Batch Calculation Speed', 'status' => 'PASS', 'value' => $timePerRecord];
        } elseif ($timePerRecord <= 2.0) { // 2ms per record
            echo "   ⚠️  Priemerný batch výpočet\n";
            $testResults[] = ['test' => 'Batch Calculation Speed', 'status' => 'WARNING', 'value' => $timePerRecord];
        } else {
            echo "   ❌ Pomalý batch výpočet\n";
            $testResults[] = ['test' => 'Batch Calculation Speed', 'status' => 'FAIL', 'value' => $timePerRecord];
        }
    } else {
        echo "   ⚠️  Žiadne testovacie dáta pre batch výpočty\n";
        $testResults[] = ['test' => 'Batch Calculation Speed', 'status' => 'SKIP', 'value' => 'NO_DATA'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Batch Calculation Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Rýchlosť extreme value detekcie
echo "\n📊 Test 4: Rýchlosť extreme value detekcie\n";
echo "------------------------------------------\n";

$totalTests++;
try {
    $iterations = 1000;
    $totalTime = 0;
    
    echo "   📋 Testovanie {$iterations} iterácií extreme value detekcie:\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $startTime = microtime(true);
        
        // Simulácia extreme value detekcie
        $testValues = [25.5, -15.2, 350.8, -450.2, 150.0, -200.0, 50.0, -75.0];
        
        foreach ($testValues as $value) {
            $isExtreme = (abs($value) > 300);
            $clampedValue = $isExtreme ? ($value > 0 ? 300 : -300) : $value;
        }
        
        $endTime = microtime(true);
        $totalTime += ($endTime - $startTime) * 1000;
    }
    
    $averageTime = round($totalTime / $iterations, 4);
    echo "   📊 Priemerný čas detekcie: {$averageTime}ms\n";
    
    if ($averageTime <= 0.01) { // 0.01ms
        echo "   ✅ Veľmi rýchla extreme value detekcia\n";
        $passedTests++;
        $testResults[] = ['test' => 'Extreme Value Detection Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 0.1) { // 0.1ms
        echo "   ✅ Rýchla extreme value detekcia\n";
        $passedTests++;
        $testResults[] = ['test' => 'Extreme Value Detection Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 0.5) { // 0.5ms
        echo "   ⚠️  Priemerná extreme value detekcia\n";
        $testResults[] = ['test' => 'Extreme Value Detection Speed', 'status' => 'WARNING', 'value' => $averageTime];
    } else {
        echo "   ❌ Pomalá extreme value detekcia\n";
        $testResults[] = ['test' => 'Extreme Value Detection Speed', 'status' => 'FAIL', 'value' => $averageTime];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Extreme Value Detection Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Rýchlosť API endpoint volania
echo "\n📊 Test 5: Rýchlosť API endpoint volania\n";
echo "---------------------------------------\n";

$totalTests++;
try {
    $iterations = 5;
    $totalTime = 0;
    $successfulCalls = 0;
    
    echo "   📋 Testovanie {$iterations} iterácií API volania:\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $startTime = microtime(true);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents('http://localhost:8000/api/earnings-tickers-today.php', false, $context);
        
        $endTime = microtime(true);
        $apiTime = ($endTime - $startTime) * 1000;
        $totalTime += $apiTime;
        
        if ($response !== false) {
            $successfulCalls++;
            $data = json_decode($response, true);
            $recordCount = isset($data['data']) ? count($data['data']) : 0;
            echo "   📊 Iterácia {$i}: {$apiTime}ms ({$recordCount} záznamov)\n";
        } else {
            echo "   ❌ Iterácia {$i}: Timeout/Error\n";
        }
    }
    
    $averageTime = round($totalTime / $iterations, 2);
    $successRate = round(($successfulCalls / $iterations) * 100, 1);
    echo "   📊 Priemerný čas API: {$averageTime}ms\n";
    echo "   📊 Úspešnosť volaní: {$successfulCalls}/{$iterations} ({$successRate}%)\n";
    
    if ($averageTime <= 100 && $successRate >= 80) { // 100ms a 80% success
        echo "   ✅ Rýchle a spoľahlivé API volania\n";
        $passedTests++;
        $testResults[] = ['test' => 'API Endpoint Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 500 && $successRate >= 60) { // 500ms a 60% success
        echo "   ⚠️  Priemerné API volania\n";
        $testResults[] = ['test' => 'API Endpoint Speed', 'status' => 'WARNING', 'value' => $averageTime];
    } else {
        echo "   ❌ Pomalé alebo nespolehlivé API volania\n";
        $testResults[] = ['test' => 'API Endpoint Speed', 'status' => 'FAIL', 'value' => $averageTime];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'API Endpoint Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Memory usage test
echo "\n📊 Test 6: Memory usage test\n";
echo "---------------------------\n";

$totalTests++;
try {
    $initialMemory = memory_get_usage(true);
    
    // Simulácia veľkého množstva guidance výpočtov
    $largeDataSet = [];
    for ($i = 0; $i < 1000; $i++) {
        $largeDataSet[] = [
            'eps_estimate' => rand(1, 100) / 10,
            'eps_guidance' => rand(1, 100) / 10,
            'revenue_estimate' => rand(1000000, 10000000),
            'revenue_guidance' => rand(1000000, 10000000)
        ];
    }
    
    $memoryAfterData = memory_get_usage(true);
    
    // Výpočty
    foreach ($largeDataSet as $data) {
        if ($data['eps_estimate'] != 0) {
            $epsSurprise = (($data['eps_guidance'] - $data['eps_estimate']) / $data['eps_estimate']) * 100;
        }
        if ($data['revenue_estimate'] != 0) {
            $revenueSurprise = (($data['revenue_guidance'] - $data['revenue_estimate']) / $data['revenue_estimate']) * 100;
        }
    }
    
    $finalMemory = memory_get_usage(true);
    
    $dataMemoryUsage = round(($memoryAfterData - $initialMemory) / 1024 / 1024, 2); // MB
    $totalMemoryUsage = round(($finalMemory - $initialMemory) / 1024 / 1024, 2); // MB
    
    echo "   📋 Memory usage štatistiky:\n";
    echo "   📊 Počiatočná memory: " . round($initialMemory / 1024 / 1024, 2) . "MB\n";
    echo "   📊 Memory po načítaní dát: " . round($memoryAfterData / 1024 / 1024, 2) . "MB\n";
    echo "   📊 Finálna memory: " . round($finalMemory / 1024 / 1024, 2) . "MB\n";
    echo "   📊 Memory pre dáta: {$dataMemoryUsage}MB\n";
    echo "   📊 Celková memory usage: {$totalMemoryUsage}MB\n";
    
    if ($totalMemoryUsage <= 10) { // 10MB
        echo "   ✅ Nízka memory usage\n";
        $passedTests++;
        $testResults[] = ['test' => 'Memory Usage', 'status' => 'PASS', 'value' => $totalMemoryUsage];
    } elseif ($totalMemoryUsage <= 50) { // 50MB
        echo "   ⚠️  Priemerná memory usage\n";
        $testResults[] = ['test' => 'Memory Usage', 'status' => 'WARNING', 'value' => $totalMemoryUsage];
    } else {
        echo "   ❌ Vysoká memory usage\n";
        $testResults[] = ['test' => 'Memory Usage', 'status' => 'FAIL', 'value' => $totalMemoryUsage];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Memory Usage', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Výsledky
echo "\n📊 VÝSLEDKY TESTOVANIA\n";
echo "======================\n";
echo "🎯 Celkovo testov: $totalTests\n";
echo "✅ Úspešné: $passedTests\n";
echo "❌ Zlyhalo: " . ($totalTests - $passedTests) . "\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
echo "📈 Úspešnosť: $successRate%\n";

echo "\n📋 Detailné výsledky:\n";
foreach ($testResults as $result) {
    $statusIcon = $result['status'] === 'PASS' ? '✅' : ($result['status'] === 'WARNING' ? '⚠️' : '❌');
    echo "   $statusIcon {$result['test']}: {$result['value']}\n";
}

echo "\n";
if ($successRate >= 90) {
    echo "🏆 VÝBORNE! Guidance calculation speed je výborná!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina performance testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica performance testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho performance testov zlyhalo.\n";
}

echo "\n🎉 Test guidance calculation speed dokončený!\n";
?>
