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
        'check_tables.php' => 'Kontrola štruktúry tabuliek',
        'check_collation_status.php' => 'Kontrola collation statusu databázy',
        'check_ett_structure.php' => 'Kontrola štruktúry EarningsTickersToday tabuľky'
    ],
    
    // === TEST SÚBORY (Funkcionalita) ===
    'test' => [
        'test_api.php' => 'Test API endpoint',
        'test_sql_injection_simple.php' => 'Test SQL injection ochrany',
        'test_logging_simple.php' => 'Test logging funkcionality',
        'test_security_headers.php' => 'Test security headers',
        'test_polygon_api.php' => 'Test Polygon API',
        'test_curl_multi_speed.php' => 'Test cURL performance',
        'test_krok5_optimizations.php' => 'Test KROK 5 optimalizácií',
        // === NOVÉ GUIDANCE TESTS ===
        'test_api_keys.php' => 'Test API kľúčov',
        'test_benzinga_direct.php' => 'Test Benzinga API priamo',
        'test_benzinga_guidance.php' => 'Test Benzinga guidance funkcionality',
        'test_finnhub_data.php' => 'Test Finnhub API dát',
        'test_guidance_instantiate.php' => 'Test guidance tried inštancovania',
        'test_guidance_simple.php' => 'Test základnej guidance logiky',
        'test_simple_db.php' => 'Test databázového pripojenia',
        // === NOVÉ BENZINGA PIPELINE TESTS ===
        'test_benzinga_pipeline.php' => 'Test Benzinga data pipeline (CRITICAL)',
        'test_benzinga_issues.php' => 'Test Benzinga issues resolution (CRITICAL)',
        // === NOVÉ GUIDANCE MATCHING TESTS ===
        'test_avgo_matching.php' => 'Test AVGO guidance matching logiky',
        // === NOVÉ GUIDANCE SURPRISE TESTS ===
        'test_guidance_surprise_calculation.php' => 'Test výpočtu guidance surprise percent',
        'test_guidance_period_matching.php' => 'Test matchingu fiscal periods (Q1 vs Q1, FY vs FY)',
        'test_guidance_extreme_values.php' => 'Test handlingu extreme values (>300%, <-300%)',
        'test_guidance_fallback_logic.php' => 'Test fallback logiky (consensus → estimate → previous)',
        // === NOVÉ COLLATION & DATABASE TESTS ===
        'test_collation_consistency.php' => 'Test konzistentnosti collation v celej DB',
        'test_database_migrations.php' => 'Test migračných skriptov',
        'test_fiscal_period_derivation.php' => 'Test odvodenia fiscal periods z report_date',
        // === NOVÉ API & DATA INTEGRITY TESTS ===
        'test_api_response_consistency.php' => 'Test konzistentnosti API odpovedí',
        'test_guidance_data_validation.php' => 'Test validácie guidance dát',
        'test_earnings_estimate_accuracy.php' => 'Test presnosti earnings estimates',
        // === NOVÉ PERFORMANCE TESTS ===
        'test_guidance_calculation_speed.php' => 'Test rýchlosti výpočtov guidance',
        'test_batch_operations.php' => 'Test batch operácií v databáze',
        'test_api_rate_limiting.php' => 'Test rate limiting pre API volania',
        // === NOVÉ SECURITY & VALIDATION TESTS ===
        'test_sql_injection_guidance.php' => 'Test SQL injection ochrany v guidance queries',
        'test_input_validation.php' => 'Test validácie vstupných dát',
        'test_data_sanitization.php' => 'Test sanitizácie dát',
        // === DODATOČNÉ DÔLEŽITÉ TESTS ===
        'test_api_endpoint.php' => 'Test API endpoint funkcionality',
        'test_batch_quote.php' => 'Test Polygon batch quote performance',
        'test_finnhub_earnings.php' => 'Test Finnhub earnings API',
        'test_dashboard_data.php' => 'Test dashboard data integrity',
        // === NOVÉ KRITICKÉ BEZPEČNOSTNÉ TESTS ===
        'test_authentication_authorization.php' => 'Test Authentication & Authorization systému',
        'test_csrf_protection.php' => 'Test CSRF Protection ochrany',
        'test_session_management.php' => 'Test Session Management bezpečnosti',
        'test_api_security.php' => 'Test API Security ochrany'
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
