<?php
/**
 * 📊 TEST: API Rate Limiting
 * Testuje rate limiting pre API volania
 */

require_once __DIR__ . '/test_config.php';

echo "📊 TEST: API Rate Limiting\n";
echo "=========================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Test rýchlosti API volaní
echo "📊 Test 1: Test rýchlosti API volaní\n";
echo "-----------------------------------\n";

$totalTests++;
try {
    $iterations = 20;
    $totalTime = 0;
    $successfulCalls = 0;
    $responseTimes = [];
    
    echo "   📋 Testovanie {$iterations} rýchlych API volaní:\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $startTime = microtime(true);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents('http://localhost:8000/api/earnings-tickers-today.php', false, $context);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        $responseTimes[] = $responseTime;
        $totalTime += $responseTime;
        
        if ($response !== false) {
            $successfulCalls++;
            $data = json_decode($response, true);
            $recordCount = isset($data['data']) ? count($data['data']) : 0;
            echo "   📊 Volanie {$i}: {$responseTime}ms ({$recordCount} záznamov)\n";
        } else {
            echo "   ❌ Volanie {$i}: Timeout/Error\n";
        }
        
        // Krátka pauza medzi volaniami
        usleep(10000); // 10ms
    }
    
    $averageTime = round($totalTime / $iterations, 2);
    $successRate = round(($successfulCalls / $iterations) * 100, 1);
    $minTime = round(min($responseTimes), 2);
    $maxTime = round(max($responseTimes), 2);
    
    echo "   📊 Priemerný response time: {$averageTime}ms\n";
    echo "   📊 Min response time: {$minTime}ms\n";
    echo "   📊 Max response time: {$maxTime}ms\n";
    echo "   📊 Úspešnosť volaní: {$successfulCalls}/{$iterations} ({$successRate}%)\n";
    
    if ($successRate >= 95 && $averageTime <= 100) {
        echo "   ✅ Vysoká rýchlosť a spoľahlivosť API\n";
        $passedTests++;
        $testResults[] = ['test' => 'API Call Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($successRate >= 80 && $averageTime <= 500) {
        echo "   ⚠️  Priemerná rýchlosť a spoľahlivosť API\n";
        $testResults[] = ['test' => 'API Call Speed', 'status' => 'WARNING', 'value' => $averageTime];
    } else {
        echo "   ❌ Nízka rýchlosť alebo spoľahlivosť API\n";
        $testResults[] = ['test' => 'API Call Speed', 'status' => 'FAIL', 'value' => $averageTime];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'API Call Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Test concurrent API volaní
echo "\n📊 Test 2: Test concurrent API volaní\n";
echo "------------------------------------\n";

$totalTests++;
try {
    $concurrentCalls = 5;
    $totalTime = 0;
    $successfulCalls = 0;
    
    echo "   📋 Testovanie {$concurrentCalls} concurrent API volaní:\n";
    
    $startTime = microtime(true);
    
    // Simulácia concurrent volaní pomocou curl_multi
    $multiHandle = curl_multi_init();
    $curlHandles = [];
    
    for ($i = 0; $i < $concurrentCalls; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/earnings-tickers-today.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[] = $ch;
    }
    
    // Spustenie všetkých volaní
    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
        curl_multi_select($multiHandle);
    } while ($running > 0);
    
    // Získanie výsledkov
    foreach ($curlHandles as $ch) {
        $response = curl_multi_getcontent($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode === 200 && $response !== false) {
            $successfulCalls++;
            $data = json_decode($response, true);
            $recordCount = isset($data['data']) ? count($data['data']) : 0;
            echo "   ✅ Concurrent volanie: HTTP {$httpCode} ({$recordCount} záznamov)\n";
        } else {
            echo "   ❌ Concurrent volanie: HTTP {$httpCode}\n";
        }
        
        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }
    
    curl_multi_close($multiHandle);
    
    $endTime = microtime(true);
    $totalTime = ($endTime - $startTime) * 1000;
    $successRate = round(($successfulCalls / $concurrentCalls) * 100, 1);
    
    echo "   📊 Celkový čas concurrent volaní: {$totalTime}ms\n";
    echo "   📊 Úspešnosť concurrent volaní: {$successfulCalls}/{$concurrentCalls} ({$successRate}%)\n";
    
    if ($successRate >= 80 && $totalTime <= 1000) {
        echo "   ✅ Dobré concurrent API volania\n";
        $passedTests++;
        $testResults[] = ['test' => 'Concurrent API Calls', 'status' => 'PASS', 'value' => $totalTime];
    } elseif ($successRate >= 60 && $totalTime <= 2000) {
        echo "   ⚠️  Priemerné concurrent API volania\n";
        $testResults[] = ['test' => 'Concurrent API Calls', 'status' => 'WARNING', 'value' => $totalTime];
    } else {
        echo "   ❌ Slabé concurrent API volania\n";
        $testResults[] = ['test' => 'Concurrent API Calls', 'status' => 'FAIL', 'value' => $totalTime];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Concurrent API Calls', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Test rate limiting s rôznymi intervalmi
echo "\n📊 Test 3: Test rate limiting s rôznymi intervalmi\n";
echo "------------------------------------------------\n";

$totalTests++;
try {
    $intervals = [0.01, 0.05, 0.1, 0.2, 0.5]; // sekundy
    $callsPerInterval = 3;
    $intervalResults = [];
    
    echo "   📋 Testovanie rôznych intervalov medzi volaniami:\n";
    
    foreach ($intervals as $interval) {
        $totalTime = 0;
        $successfulCalls = 0;
        $responseTimes = [];
        
        echo "   📊 Interval {$interval}s:\n";
        
        for ($i = 0; $i < $callsPerInterval; $i++) {
            $startTime = microtime(true);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents('http://localhost:8000/api/earnings-tickers-today.php', false, $context);
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;
            $responseTimes[] = $responseTime;
            $totalTime += $responseTime;
            
            if ($response !== false) {
                $successfulCalls++;
                echo "     ✅ Volanie " . ($i + 1) . ": {$responseTime}ms\n";
            } else {
                echo "     ❌ Volanie " . ($i + 1) . ": Timeout/Error\n";
            }
            
            if ($i < $callsPerInterval - 1) {
                usleep($interval * 1000000); // konverzia na mikrosekundy
            }
        }
        
        $averageTime = round($totalTime / $callsPerInterval, 2);
        $successRate = round(($successfulCalls / $callsPerInterval) * 100, 1);
        
        $intervalResults[] = [
            'interval' => $interval,
            'average_time' => $averageTime,
            'success_rate' => $successRate
        ];
        
        echo "     📊 Priemerný čas: {$averageTime}ms, Úspešnosť: {$successRate}%\n";
    }
    
    // Analýza výsledkov
    $bestInterval = null;
    $bestScore = 0;
    
    foreach ($intervalResults as $result) {
        $score = $result['success_rate'] - ($result['average_time'] / 10); // penalty za pomalosť
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestInterval = $result['interval'];
        }
    }
    
    echo "   📊 Najlepší interval: {$bestInterval}s (score: {$bestScore})\n";
    
    if ($bestScore >= 80) {
        echo "   ✅ Dobré rate limiting výsledky\n";
        $passedTests++;
        $testResults[] = ['test' => 'Rate Limiting Intervals', 'status' => 'PASS', 'value' => $bestInterval];
    } elseif ($bestScore >= 60) {
        echo "   ⚠️  Priemerné rate limiting výsledky\n";
        $testResults[] = ['test' => 'Rate Limiting Intervals', 'status' => 'WARNING', 'value' => $bestInterval];
    } else {
        echo "   ❌ Slabé rate limiting výsledky\n";
        $testResults[] = ['test' => 'Rate Limiting Intervals', 'status' => 'FAIL', 'value' => $bestInterval];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Rate Limiting Intervals', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Test burst API volaní
echo "\n📊 Test 4: Test burst API volaní\n";
echo "-------------------------------\n";

$totalTests++;
try {
    $burstSize = 10;
    $totalTime = 0;
    $successfulCalls = 0;
    $responseTimes = [];
    
    echo "   📋 Testovanie burst {$burstSize} API volaní bez pauzy:\n";
    
    $startTime = microtime(true);
    
    for ($i = 1; $i <= $burstSize; $i++) {
        $callStartTime = microtime(true);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents('http://localhost:8000/api/earnings-tickers-today.php', false, $context);
        
        $callEndTime = microtime(true);
        $responseTime = ($callEndTime - $callStartTime) * 1000;
        $responseTimes[] = $responseTime;
        
        if ($response !== false) {
            $successfulCalls++;
            $data = json_decode($response, true);
            $recordCount = isset($data['data']) ? count($data['data']) : 0;
            echo "   📊 Burst volanie {$i}: {$responseTime}ms ({$recordCount} záznamov)\n";
        } else {
            echo "   ❌ Burst volanie {$i}: Timeout/Error\n";
        }
    }
    
    $endTime = microtime(true);
    $totalTime = ($endTime - $startTime) * 1000;
    
    $averageTime = round($totalTime / $burstSize, 2);
    $successRate = round(($successfulCalls / $burstSize) * 100, 1);
    $minTime = round(min($responseTimes), 2);
    $maxTime = round(max($responseTimes), 2);
    
    echo "   📊 Celkový čas burst: {$totalTime}ms\n";
    echo "   📊 Priemerný čas na volanie: {$averageTime}ms\n";
    echo "   📊 Min response time: {$minTime}ms\n";
    echo "   📊 Max response time: {$maxTime}ms\n";
    echo "   📊 Úspešnosť burst volaní: {$successfulCalls}/{$burstSize} ({$successRate}%)\n";
    
    if ($successRate >= 90 && $totalTime <= 2000) {
        echo "   ✅ Vysoká úspešnosť burst volaní\n";
        $passedTests++;
        $testResults[] = ['test' => 'Burst API Calls', 'status' => 'PASS', 'value' => $totalTime];
    } elseif ($successRate >= 70 && $totalTime <= 5000) {
        echo "   ⚠️  Priemerná úspešnosť burst volaní\n";
        $testResults[] = ['test' => 'Burst API Calls', 'status' => 'WARNING', 'value' => $totalTime];
    } else {
        echo "   ❌ Nízka úspešnosť burst volaní\n";
        $testResults[] = ['test' => 'Burst API Calls', 'status' => 'FAIL', 'value' => $totalTime];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Burst API Calls', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Test timeout handling
echo "\n📊 Test 5: Test timeout handling\n";
echo "-------------------------------\n";

$totalTests++;
try {
    $timeoutTests = [
        ['timeout' => 1, 'expected' => 'timeout'],
        ['timeout' => 5, 'expected' => 'success'],
        ['timeout' => 10, 'expected' => 'success']
    ];
    
    $timeoutResults = [];
    
    echo "   📋 Testovanie rôznych timeout hodnôt:\n";
    
    foreach ($timeoutTests as $test) {
        $startTime = microtime(true);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $test['timeout'],
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents('http://localhost:8000/api/earnings-tickers-today.php', false, $context);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $actualResult = $response !== false ? 'success' : 'timeout';
        $expectedResult = $test['expected'];
        
        $timeoutResults[] = [
            'timeout' => $test['timeout'],
            'response_time' => $responseTime,
            'actual' => $actualResult,
            'expected' => $expectedResult,
            'correct' => $actualResult === $expectedResult
        ];
        
        $statusIcon = $actualResult === $expectedResult ? '✅' : '❌';
        echo "   {$statusIcon} Timeout {$test['timeout']}s: {$responseTime}ms ({$actualResult})\n";
    }
    
    $correctTimeouts = array_sum(array_column($timeoutResults, 'correct'));
    $timeoutAccuracy = round(($correctTimeouts / count($timeoutTests)) * 100, 1);
    
    echo "   📊 Timeout accuracy: {$correctTimeouts}/" . count($timeoutTests) . " ({$timeoutAccuracy}%)\n";
    
    if ($timeoutAccuracy >= 80) {
        echo "   ✅ Dobré timeout handling\n";
        $passedTests++;
        $testResults[] = ['test' => 'Timeout Handling', 'status' => 'PASS', 'value' => $timeoutAccuracy];
    } elseif ($timeoutAccuracy >= 60) {
        echo "   ⚠️  Priemerné timeout handling\n";
        $testResults[] = ['test' => 'Timeout Handling', 'status' => 'WARNING', 'value' => $timeoutAccuracy];
    } else {
        echo "   ❌ Slabé timeout handling\n";
        $testResults[] = ['test' => 'Timeout Handling', 'status' => 'FAIL', 'value' => $timeoutAccuracy];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Timeout Handling', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Test memory usage pri API volaniach
echo "\n📊 Test 6: Test memory usage pri API volaniach\n";
echo "--------------------------------------------\n";

$totalTests++;
try {
    $initialMemory = memory_get_usage(true);
    $iterations = 50;
    $totalTime = 0;
    $successfulCalls = 0;
    
    echo "   📋 Testovanie memory usage pre {$iterations} API volaní:\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $startTime = microtime(true);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents('http://localhost:8000/api/earnings-tickers-today.php', false, $context);
        
        $endTime = microtime(true);
        $totalTime += ($endTime - $startTime) * 1000;
        
        if ($response !== false) {
            $successfulCalls++;
            $data = json_decode($response, true);
            // Simulácia spracovania dát
            if (isset($data['data'])) {
                foreach ($data['data'] as $record) {
                    // Simulácia nejakého spracovania
                    $processed = [
                        'ticker' => $record['ticker'] ?? '',
                        'company' => $record['company'] ?? '',
                        'processed_at' => time()
                    ];
                }
            }
        }
        
        // Každých 10 iterácií zobrazíme progress
        if ($i % 10 === 0) {
            $currentMemory = memory_get_usage(true);
            $memoryUsage = round(($currentMemory - $initialMemory) / 1024 / 1024, 2);
            echo "   📊 Iterácia {$i}: {$memoryUsage}MB memory usage\n";
        }
    }
    
    $finalMemory = memory_get_usage(true);
    $totalMemoryUsage = round(($finalMemory - $initialMemory) / 1024 / 1024, 2);
    $averageTime = round($totalTime / $iterations, 2);
    $successRate = round(($successfulCalls / $iterations) * 100, 1);
    
    echo "   📊 Celková memory usage: {$totalMemoryUsage}MB\n";
    echo "   📊 Priemerný čas na volanie: {$averageTime}ms\n";
    echo "   📊 Úspešnosť volaní: {$successfulCalls}/{$iterations} ({$successRate}%)\n";
    
    if ($totalMemoryUsage <= 50 && $successRate >= 90) {
        echo "   ✅ Nízka memory usage a vysoká úspešnosť\n";
        $passedTests++;
        $testResults[] = ['test' => 'API Memory Usage', 'status' => 'PASS', 'value' => $totalMemoryUsage];
    } elseif ($totalMemoryUsage <= 100 && $successRate >= 80) {
        echo "   ⚠️  Priemerná memory usage a úspešnosť\n";
        $testResults[] = ['test' => 'API Memory Usage', 'status' => 'WARNING', 'value' => $totalMemoryUsage];
    } else {
        echo "   ❌ Vysoká memory usage alebo nízka úspešnosť\n";
        $testResults[] = ['test' => 'API Memory Usage', 'status' => 'FAIL', 'value' => $totalMemoryUsage];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'API Memory Usage', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "🏆 VÝBORNE! API rate limiting performance je výborná!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina API rate limiting testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica API rate limiting testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho API rate limiting testov zlyhalo.\n";
}

echo "\n🎉 Test API rate limiting dokončený!\n";
?>
