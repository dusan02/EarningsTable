<?php
/**
 * 🎯 MASTER TEST ORCHESTRATOR
 * Spúšťa všetky existujúce testy a checky
 */

require_once __DIR__ . '/test_config.php';

echo "🎯 MASTER TEST ORCHESTRATOR\n";
echo "==========================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

// Definícia všetkých testov a checkov
$tests = [
    // === CHECK SÚBORY (Diagnostika) ===
    'check' => [
        'health_db.php' => 'Kontrola zdravia databázy',
        'check_tickers.php' => 'Kontrola tickerov',
        'check_earnings.php' => 'Kontrola earnings dát',
        'check_data.php' => 'Prehľad dnešných dát',
        'check_tables.php' => 'Kontrola štruktúry tabuliek'
    ],
    
    // === TEST SÚBORY (Funkcionalita) ===
    'test' => [
        'test_api.php' => 'Test API endpoint',
        'test_sql_injection_simple.php' => 'Test SQL injection ochrany',
        'test_logging_simple.php' => 'Test logging funkcionality',
        'test_security_headers.php' => 'Test security headers',
        'test_polygon_api.php' => 'Test Polygon API',
        'test_curl_multi_speed.php' => 'Test cURL performance',
        'test_krok5_optimizations.php' => 'Test KROK 5 optimalizácií'
    ],
    
    // === CRITICAL TEST SÚBORY (Kritické funkcionality) ===
    'critical' => [
        'test_unified_api_wrapper.php' => 'Test UnifiedApiWrapper (CRITICAL)',
        'test_daily_data_setup.php' => 'Test DailyDataSetup (CRITICAL)',
        'test_regular_data_updates.php' => 'Test RegularDataUpdatesDynamic (CRITICAL)',
        'test_cron_jobs.php' => 'Test Cron Jobs (CRITICAL)',
        'test_api_endpoints.php' => 'Test API Endpoints (CRITICAL)'
    ],
    
    // === ANALÝZA SÚBORY (Špecializované) ===
    'analysis' => [
        'analyze_earnings_data.php' => 'Analýza earnings dát',
        'detailed_earnings_analysis.php' => 'Detailná analýza earnings'
    ]
];

// Štatistiky
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$skippedTests = 0;

$results = [];

echo "📊 SPÚŠŤAM TESTOVANIE...\n";
echo "========================\n\n";

// 1. SPUSTENIE CHECK SÚBOROV
echo "🔍 KATEGÓRIA: CHECK SÚBORY (Diagnostika)\n";
echo "=========================================\n";

foreach ($tests['check'] as $file => $description) {
    $totalTests++;
    echo "\n📋 $description ($file)...\n";
    
    try {
        $startTime = microtime(true);
        ob_start();
        
        $exitCode = 0;
        $output = '';
        
        // Spusti súbor
        if (file_exists(__DIR__ . '/' . $file)) {
            $output = shell_exec("php " . __DIR__ . '/' . $file . " 2>&1");
            $exitCode = $output === null ? 1 : 0;
        } else {
            $output = "❌ Súbor neexistuje";
            $exitCode = 1;
        }
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        if ($exitCode === 0) {
            echo "   ✅ ÚSPEŠNÉ ($duration ms)\n";
            $passedTests++;
            $status = 'PASS';
        } else {
            echo "   ❌ ZLYHALO ($duration ms)\n";
            $failedTests++;
            $status = 'FAIL';
        }
        
        $results[] = [
            'category' => 'check',
            'file' => $file,
            'description' => $description,
            'status' => $status,
            'duration' => $duration,
            'output' => $output
        ];
        
    } catch (Exception $e) {
        echo "   ❌ CHYBA: " . $e->getMessage() . "\n";
        $failedTests++;
        $results[] = [
            'category' => 'check',
            'file' => $file,
            'description' => $description,
            'status' => 'ERROR',
            'duration' => 0,
            'output' => $e->getMessage()
        ];
    }
}

echo "\n";

// 2. SPUSTENIE TEST SÚBOROV
echo "🧪 KATEGÓRIA: TEST SÚBORY (Funkcionalita)\n";
echo "==========================================\n";

foreach ($tests['test'] as $file => $description) {
    $totalTests++;
    echo "\n📋 $description ($file)...\n";
    
    try {
        $startTime = microtime(true);
        ob_start();
        
        $exitCode = 0;
        $output = '';
        
        // Spusti súbor
        if (file_exists(__DIR__ . '/' . $file)) {
            $output = shell_exec("php " . __DIR__ . '/' . $file . " 2>&1");
            $exitCode = $output === null ? 1 : 0;
        } else {
            $output = "❌ Súbor neexistuje";
            $exitCode = 1;
        }
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        if ($exitCode === 0) {
            echo "   ✅ ÚSPEŠNÉ ($duration ms)\n";
            $passedTests++;
            $status = 'PASS';
        } else {
            echo "   ❌ ZLYHALO ($duration ms)\n";
            $failedTests++;
            $status = 'FAIL';
        }
        
        $results[] = [
            'category' => 'test',
            'file' => $file,
            'description' => $description,
            'status' => $status,
            'duration' => $duration,
            'output' => $output
        ];
        
    } catch (Exception $e) {
        echo "   ❌ CHYBA: " . $e->getMessage() . "\n";
        $failedTests++;
        $results[] = [
            'category' => 'test',
            'file' => $file,
            'description' => $description,
            'status' => 'ERROR',
            'duration' => 0,
            'output' => $e->getMessage()
        ];
    }
}

echo "\n";

// 3. SPUSTENIE CRITICAL TEST SÚBOROV
echo "🚨 KATEGÓRIA: CRITICAL TEST SÚBORY (Kritické funkcionality)\n";
echo "============================================================\n";

foreach ($tests['critical'] as $file => $description) {
    $totalTests++;
    echo "\n📋 $description ($file)...\n";
    
    try {
        $startTime = microtime(true);
        ob_start();
        
        $exitCode = 0;
        $output = '';
        
        // Spusti súbor
        if (file_exists(__DIR__ . '/' . $file)) {
            $output = shell_exec("php " . __DIR__ . '/' . $file . " 2>&1");
            $exitCode = $output === null ? 1 : 0;
        } else {
            $output = "❌ Súbor neexistuje";
            $exitCode = 1;
        }
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        if ($exitCode === 0) {
            echo "   ✅ ÚSPEŠNÉ ($duration ms)\n";
            $passedTests++;
            $status = 'PASS';
        } else {
            echo "   ❌ ZLYHALO ($duration ms)\n";
            $failedTests++;
            $status = 'FAIL';
        }
        
        $results[] = [
            'category' => 'critical',
            'file' => $file,
            'description' => $description,
            'status' => $status,
            'duration' => $duration,
            'output' => $output
        ];
        
    } catch (Exception $e) {
        echo "   ❌ CHYBA: " . $e->getMessage() . "\n";
        $failedTests++;
        $results[] = [
            'category' => 'critical',
            'file' => $file,
            'description' => $description,
            'status' => 'ERROR',
            'duration' => 0,
            'output' => $e->getMessage()
        ];
    }
}

echo "\n";

// 4. SPUSTENIE ANALÝZA SÚBOROV
echo "📊 KATEGÓRIA: ANALÝZA SÚBORY (Špecializované)\n";
echo "==============================================\n";

foreach ($tests['analysis'] as $file => $description) {
    $totalTests++;
    echo "\n📋 $description ($file)...\n";
    
    try {
        $startTime = microtime(true);
        ob_start();
        
        $exitCode = 0;
        $output = '';
        
        // Spusti súbor
        if (file_exists(__DIR__ . '/' . $file)) {
            $output = shell_exec("php " . __DIR__ . '/' . $file . " 2>&1");
            $exitCode = $output === null ? 1 : 0;
        } else {
            $output = "❌ Súbor neexistuje";
            $exitCode = 1;
        }
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        if ($exitCode === 0) {
            echo "   ✅ ÚSPEŠNÉ ($duration ms)\n";
            $passedTests++;
            $status = 'PASS';
        } else {
            echo "   ❌ ZLYHALO ($duration ms)\n";
            $failedTests++;
            $status = 'FAIL';
        }
        
        $results[] = [
            'category' => 'analysis',
            'file' => $file,
            'description' => $description,
            'status' => $status,
            'duration' => $duration,
            'output' => $output
        ];
        
    } catch (Exception $e) {
        echo "   ❌ CHYBA: " . $e->getMessage() . "\n";
        $failedTests++;
        $results[] = [
            'category' => 'analysis',
            'file' => $file,
            'description' => $description,
            'status' => 'ERROR',
            'duration' => 0,
            'output' => $e->getMessage()
        ];
    }
}

echo "\n";

// 4. VÝSLEDKY
echo "📊 VÝSLEDKY TESTOVANIA\n";
echo "======================\n";

echo "🎯 Celkovo testov: $totalTests\n";
echo "✅ Úspešné: $passedTests\n";
echo "❌ Zlyhalo: $failedTests\n";
echo "⏭️ Preskočené: $skippedTests\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
echo "📈 Úspešnosť: $successRate%\n";

echo "\n";

// 5. DETAILNÉ VÝSLEDKY PODĽA KATEGÓRIÍ
echo "📋 DETAILNÉ VÝSLEDKY PODĽA KATEGÓRIÍ:\n";
echo "=====================================\n";

foreach (['check', 'test', 'critical', 'analysis'] as $category) {
    $categoryResults = array_filter($results, function($r) use ($category) {
        return $r['category'] === $category;
    });
    
    $categoryPassed = count(array_filter($categoryResults, function($r) {
        return $r['status'] === 'PASS';
    }));
    
    $categoryTotal = count($categoryResults);
    $categoryRate = $categoryTotal > 0 ? round(($categoryPassed / $categoryTotal) * 100, 1) : 0;
    
    echo "\n🔍 $category: $categoryPassed/$categoryTotal ($categoryRate%)\n";
    
    foreach ($categoryResults as $result) {
        $statusIcon = $result['status'] === 'PASS' ? '✅' : '❌';
        echo "   $statusIcon {$result['file']} - {$result['description']} ({$result['duration']}ms)\n";
    }
}

echo "\n";

// 6. ZÁVER
echo "🎉 MASTER TEST DOKONČENÝ!\n";
echo "========================\n";

if ($successRate >= 90) {
    echo "🏆 VÝBORNE! Všetky testy prešli úspešne!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho testov zlyhalo.\n";
}

echo "\n📝 Pre detailné informácie pozri jednotlivé testy.\n";
echo "🔄 Pre opakovanie spusti: php Tests/master_test.php\n";
?>
