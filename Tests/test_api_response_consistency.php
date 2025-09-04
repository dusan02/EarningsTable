<?php
/**
 * 📊 TEST: API Response Consistency
 * Testuje konzistentnosť API odpovedí
 */

require_once __DIR__ . '/test_config.php';

echo "📊 TEST: API Response Consistency\n";
echo "================================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Kontrola API endpoint dostupnosti
echo "📊 Test 1: Kontrola API endpoint dostupnosti\n";
echo "--------------------------------------------\n";

$totalTests++;
try {
    $apiEndpoints = [
        'http://localhost:8000/api/earnings-tickers-today.php',
        'http://localhost:8000/api/earnings-tickers-today.php?date=' . date('Y-m-d'),
        'http://localhost:8000/api/earnings-tickers-today.php?date=2024-01-01'
    ];
    
    $availableEndpoints = 0;
    $totalEndpoints = count($apiEndpoints);
    
    echo "   📋 Kontrola {$totalEndpoints} API endpointov:\n";
    
    foreach ($apiEndpoints as $endpoint) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($endpoint, false, $context);
        
        if ($response !== false) {
            $availableEndpoints++;
            $data = json_decode($response, true);
            if ($data && isset($data['data'])) {
                echo "   ✅ {$endpoint} (dostupný, " . count($data['data']) . " záznamov)\n";
            } else {
                echo "   ⚠️  {$endpoint} (dostupný, ale neplatný JSON)\n";
            }
        } else {
            echo "   ❌ {$endpoint} (nedostupný)\n";
        }
    }
    
    $availabilityRate = round(($availableEndpoints / $totalEndpoints) * 100, 1);
    echo "   📊 Dostupnosť API: {$availableEndpoints}/{$totalEndpoints} ({$availabilityRate}%)\n";
    
    if ($availabilityRate >= 80) {
        echo "   ✅ Väčšina API endpointov je dostupná\n";
        $passedTests++;
        $testResults[] = ['test' => 'API Endpoint Availability', 'status' => 'PASS', 'value' => $availabilityRate];
    } else {
        echo "   ❌ Mnoho API endpointov nie je dostupných\n";
        $testResults[] = ['test' => 'API Endpoint Availability', 'status' => 'FAIL', 'value' => $availabilityRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'API Endpoint Availability', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Kontrola JSON štruktúry odpovedí
echo "\n📊 Test 2: Kontrola JSON štruktúry odpovedí\n";
echo "------------------------------------------\n";

$totalTests++;
try {
    $endpoint = 'http://localhost:8000/api/earnings-tickers-today.php';
    $response = @file_get_contents($endpoint);
    
    if ($response === false) {
        echo "   ❌ API endpoint nie je dostupný\n";
        $testResults[] = ['test' => 'JSON Structure', 'status' => 'FAIL', 'value' => 'NO_RESPONSE'];
    } else {
        $data = json_decode($response, true);
        
        if ($data === null) {
            echo "   ❌ Neplatný JSON response\n";
            $testResults[] = ['test' => 'JSON Structure', 'status' => 'FAIL', 'value' => 'INVALID_JSON'];
        } else {
            $requiredFields = ['data', 'status', 'timestamp'];
            $hasRequiredFields = 0;
            
            echo "   📋 Kontrola JSON štruktúry:\n";
            foreach ($requiredFields as $field) {
                if (isset($data[$field])) {
                    $hasRequiredFields++;
                    echo "   ✅ {$field}: " . (is_array($data[$field]) ? count($data[$field]) . " items" : $data[$field]) . "\n";
                } else {
                    echo "   ❌ {$field}: chýba\n";
                }
            }
            
            if ($hasRequiredFields === count($requiredFields)) {
                echo "   ✅ JSON štruktúra je správna\n";
                $passedTests++;
                $testResults[] = ['test' => 'JSON Structure', 'status' => 'PASS', 'value' => $hasRequiredFields];
            } else {
                echo "   ❌ JSON štruktúra je neúplná\n";
                $testResults[] = ['test' => 'JSON Structure', 'status' => 'FAIL', 'value' => $hasRequiredFields];
            }
        }
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'JSON Structure', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Kontrola konzistentnosti dát v odpovediach
echo "\n📊 Test 3: Kontrola konzistentnosti dát v odpovediach\n";
echo "-----------------------------------------------------\n";

$totalTests++;
try {
    $endpoint = 'http://localhost:8000/api/earnings-tickers-today.php';
    $response = @file_get_contents($endpoint);
    
    if ($response === false) {
        echo "   ❌ API endpoint nie je dostupný\n";
        $testResults[] = ['test' => 'Data Consistency', 'status' => 'FAIL', 'value' => 'NO_RESPONSE'];
    } else {
        $data = json_decode($response, true);
        
        if ($data && isset($data['data']) && is_array($data['data'])) {
            $records = $data['data'];
            $totalRecords = count($records);
            
            if ($totalRecords > 0) {
                $requiredFields = ['ticker', 'company', 'eps_estimate', 'revenue_estimate'];
                $consistentRecords = 0;
                
                echo "   📋 Kontrola konzistentnosti pre {$totalRecords} záznamov:\n";
                
                foreach ($records as $record) {
                    $hasAllFields = true;
                    foreach ($requiredFields as $field) {
                        if (!isset($record[$field])) {
                            $hasAllFields = false;
                            break;
                        }
                    }
                    
                    if ($hasAllFields) {
                        $consistentRecords++;
                    }
                }
                
                $consistencyRate = round(($consistentRecords / $totalRecords) * 100, 1);
                echo "   📊 Konzistentnosť záznamov: {$consistentRecords}/{$totalRecords} ({$consistencyRate}%)\n";
                
                if ($consistencyRate >= 90) {
                    echo "   ✅ Vysoká konzistentnosť dát v API odpovediach\n";
                    $passedTests++;
                    $testResults[] = ['test' => 'Data Consistency', 'status' => 'PASS', 'value' => $consistencyRate];
                } elseif ($consistencyRate >= 70) {
                    echo "   ⚠️  Priemerná konzistentnosť dát v API odpovediach\n";
                    $testResults[] = ['test' => 'Data Consistency', 'status' => 'WARNING', 'value' => $consistencyRate];
                } else {
                    echo "   ❌ Nízka konzistentnosť dát v API odpovediach\n";
                    $testResults[] = ['test' => 'Data Consistency', 'status' => 'FAIL', 'value' => $consistencyRate];
                }
            } else {
                echo "   ⚠️  Žiadne dáta v API odpovedi\n";
                $testResults[] = ['test' => 'Data Consistency', 'status' => 'WARNING', 'value' => 0];
            }
        } else {
            echo "   ❌ Neplatná štruktúra API odpovede\n";
            $testResults[] = ['test' => 'Data Consistency', 'status' => 'FAIL', 'value' => 'INVALID_STRUCTURE'];
        }
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Data Consistency', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Test rôznych dátumov
echo "\n📊 Test 4: Test rôznych dátumov\n";
echo "------------------------------\n";

$totalTests++;
try {
    $testDates = [
        date('Y-m-d'), // Dnešný dátum
        date('Y-m-d', strtotime('-1 day')), // Včerajší dátum
        '2024-01-01', // Fixný dátum
        '2024-12-31'  // Iný fixný dátum
    ];
    
    $successfulDates = 0;
    $totalDates = count($testDates);
    
    echo "   📋 Testovanie {$totalDates} rôznych dátumov:\n";
    
    foreach ($testDates as $date) {
        $endpoint = "http://localhost:8000/api/earnings-tickers-today.php?date={$date}";
        $response = @file_get_contents($endpoint);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['data'])) {
                $successfulDates++;
                echo "   ✅ {$date}: " . count($data['data']) . " záznamov\n";
            } else {
                echo "   ❌ {$date}: Neplatný JSON\n";
            }
        } else {
            echo "   ❌ {$date}: Nedostupný\n";
        }
    }
    
    $successRate = round(($successfulDates / $totalDates) * 100, 1);
    echo "   📊 Úspešnosť dátumov: {$successfulDates}/{$totalDates} ({$successRate}%)\n";
    
    if ($successRate >= 75) {
        echo "   ✅ Väčšina dátumov funguje správne\n";
        $passedTests++;
        $testResults[] = ['test' => 'Different Dates', 'status' => 'PASS', 'value' => $successRate];
    } else {
        echo "   ❌ Mnoho dátumov nefunguje správne\n";
        $testResults[] = ['test' => 'Different Dates', 'status' => 'FAIL', 'value' => $successRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Different Dates', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Test response time
echo "\n📊 Test 5: Test response time\n";
echo "----------------------------\n";

$totalTests++;
try {
    $endpoint = 'http://localhost:8000/api/earnings-tickers-today.php';
    $iterations = 3;
    $totalTime = 0;
    
    echo "   📋 Testovanie response time ({$iterations} iterácií):\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $startTime = microtime(true);
        $response = @file_get_contents($endpoint);
        $endTime = microtime(true);
        
        $responseTime = ($endTime - $startTime) * 1000; // v milisekundách
        $totalTime += $responseTime;
        
        if ($response !== false) {
            echo "   ✅ Iterácia {$i}: {$responseTime}ms\n";
        } else {
            echo "   ❌ Iterácia {$i}: Timeout/Error\n";
        }
    }
    
    $averageTime = round($totalTime / $iterations, 2);
    echo "   📊 Priemerný response time: {$averageTime}ms\n";
    
    if ($averageTime <= 1000) { // 1 sekunda
        echo "   ✅ Rýchly response time\n";
        $passedTests++;
        $testResults[] = ['test' => 'Response Time', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 3000) { // 3 sekundy
        echo "   ⚠️  Priemerný response time\n";
        $testResults[] = ['test' => 'Response Time', 'status' => 'WARNING', 'value' => $averageTime];
    } else {
        echo "   ❌ Pomalý response time\n";
        $testResults[] = ['test' => 'Response Time', 'status' => 'FAIL', 'value' => $averageTime];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Response Time', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Test error handling
echo "\n📊 Test 6: Test error handling\n";
echo "-----------------------------\n";

$totalTests++;
try {
    $errorTestCases = [
        'http://localhost:8000/api/earnings-tickers-today.php?date=invalid-date',
        'http://localhost:8000/api/earnings-tickers-today.php?date=2020-13-01',
        'http://localhost:8000/api/earnings-tickers-today.php?date=2020-02-30'
    ];
    
    $handledErrors = 0;
    $totalErrors = count($errorTestCases);
    
    echo "   📋 Testovanie error handling pre {$totalErrors} prípadov:\n";
    
    foreach ($errorTestCases as $testCase) {
        $response = @file_get_contents($testCase);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['error'])) {
                $handledErrors++;
                echo "   ✅ " . basename($testCase) . ": Error handled\n";
            } else {
                echo "   ❌ " . basename($testCase) . ": No error response\n";
            }
        } else {
            echo "   ⚠️  " . basename($testCase) . ": No response\n";
        }
    }
    
    $errorHandlingRate = round(($handledErrors / $totalErrors) * 100, 1);
    echo "   📊 Error handling rate: {$handledErrors}/{$totalErrors} ({$errorHandlingRate}%)\n";
    
    if ($errorHandlingRate >= 66) {
        echo "   ✅ Dobrý error handling\n";
        $passedTests++;
        $testResults[] = ['test' => 'Error Handling', 'status' => 'PASS', 'value' => $errorHandlingRate];
    } else {
        echo "   ❌ Slabý error handling\n";
        $testResults[] = ['test' => 'Error Handling', 'status' => 'FAIL', 'value' => $errorHandlingRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Error Handling', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "🏆 VÝBORNE! API response consistency je výborná!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina API testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica API testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho API testov zlyhalo.\n";
}

echo "\n🎉 Test API response consistency dokončený!\n";
?>
