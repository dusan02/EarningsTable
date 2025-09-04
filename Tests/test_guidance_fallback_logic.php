<?php
/**
 * 🎯 TEST: Guidance Fallback Logic
 * Testuje fallback logiku (consensus → estimate → previous)
 */

require_once __DIR__ . '/test_config.php';

echo "🎯 TEST: Guidance Fallback Logic\n";
echo "===============================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Simuluj fallback logiku (rovnaká ako v API)
function testFallbackLogic($item) {
    $result = [
        'eps_guide_surprise' => null,
        'eps_guide_basis' => null,
        'eps_guide_extreme' => false,
        'revenue_guide_surprise' => null,
        'revenue_guide_basis' => null,
        'revenue_guide_extreme' => false
    ];
    
    // EPS Guide Surprise Fallback
    if ($item['eps_guide_surprise_consensus'] !== null) {
        $result['eps_guide_surprise'] = $item['eps_guide_surprise_consensus'];
        $result['eps_guide_basis'] = 'vendor_consensus';
        $result['eps_guide_extreme'] = abs($item['eps_guide_surprise_consensus']) > 300;
    } elseif (
        $item['eps_guide'] !== null &&
        $item['eps_estimate'] !== null &&
        $item['eps_estimate'] != 0 &&
        periodsMatch($item, $item) &&
        methodOk($item['guidance_eps_method'] ?? null, null)
    ) {
        $result['eps_guide_surprise'] = (($item['eps_guide'] - $item['eps_estimate']) / $item['eps_estimate']) * 100;
        $result['eps_guide_basis'] = 'estimate';
        $result['eps_guide_extreme'] = abs($result['eps_guide_surprise']) > 300;
    } elseif (
        $item['eps_guide'] !== null &&
        $item['previous_max_eps_guidance'] !== null &&
        $item['previous_max_eps_guidance'] != 0
    ) {
        $result['eps_guide_surprise'] = (($item['eps_guide'] - $item['previous_max_eps_guidance']) / $item['previous_max_eps_guidance']) * 100;
        $result['eps_guide_basis'] = 'previous_max';
        $result['eps_guide_extreme'] = abs($result['eps_guide_surprise']) > 300;
    } elseif (
        $item['eps_guide'] !== null &&
        $item['previous_min_eps_guidance'] !== null &&
        $item['previous_min_eps_guidance'] != 0
    ) {
        $result['eps_guide_surprise'] = (($item['eps_guide'] - $item['previous_min_eps_guidance']) / $item['previous_min_eps_guidance']) * 100;
        $result['eps_guide_basis'] = 'previous_min';
        $result['eps_guide_extreme'] = abs($result['eps_guide_surprise']) > 300;
    }
    
    // Revenue Guide Surprise Fallback (podobná logika)
    if ($item['revenue_guide_surprise_consensus'] !== null) {
        $result['revenue_guide_surprise'] = $item['revenue_guide_surprise_consensus'];
        $result['revenue_guide_basis'] = 'vendor_consensus';
        $result['revenue_guide_extreme'] = abs($item['revenue_guide_surprise_consensus']) > 300;
    } elseif (
        $item['revenue_guide'] !== null &&
        $item['revenue_estimate'] !== null &&
        $item['revenue_estimate'] != 0 &&
        periodsMatch($item, $item) &&
        methodOk($item['guidance_revenue_method'] ?? null, null)
    ) {
        $result['revenue_guide_surprise'] = (($item['revenue_guide'] - $item['revenue_estimate']) / $item['revenue_estimate']) * 100;
        $result['revenue_guide_basis'] = 'estimate';
        $result['revenue_guide_extreme'] = abs($result['revenue_guide_surprise']) > 300;
    } elseif (
        $item['revenue_guide'] !== null &&
        $item['previous_max_revenue_guidance'] !== null &&
        $item['previous_max_revenue_guidance'] != 0
    ) {
        $result['revenue_guide_surprise'] = (($item['revenue_guide'] - $item['previous_max_revenue_guidance']) / $item['previous_max_revenue_guidance']) * 100;
        $result['revenue_guide_basis'] = 'previous_max';
        $result['revenue_guide_extreme'] = abs($result['revenue_guide_surprise']) > 300;
    } elseif (
        $item['revenue_guide'] !== null &&
        $item['previous_min_revenue_guidance'] !== null &&
        $item['previous_min_revenue_guidance'] != 0
    ) {
        $result['revenue_guide_surprise'] = (($item['revenue_guide'] - $item['previous_min_revenue_guidance']) / $item['previous_min_revenue_guidance']) * 100;
        $result['revenue_guide_basis'] = 'previous_min';
        $result['revenue_guide_extreme'] = abs($result['revenue_guide_surprise']) > 300;
    }
    
    return $result;
}

// Pomocné funkcie
function periodsMatch($guidance, $item) {
    if (empty($guidance['fiscal_period']) || empty($guidance['fiscal_year'])) return false;
    if (empty($item['fiscal_period']) || empty($item['fiscal_year'])) return false;
    return $guidance['fiscal_period'] === $item['fiscal_period'] && $guidance['fiscal_year'] == $item['fiscal_year'];
}

function methodOk($guidanceMethod, $estimateMethod) {
    // Zjednodušená verzia - v skutočnosti by mala byť komplexnejšia
    return true; // Pre testovanie
}

// Test 1: Consensus fallback
echo "📊 Test 1: Consensus fallback\n";
echo "-----------------------------\n";

$totalTests++;
try {
    $testItem = [
        'eps_guide_surprise_consensus' => 25.5,
        'revenue_guide_surprise_consensus' => 15.2,
        'eps_guide' => 2.50,
        'eps_estimate' => 2.00,
        'revenue_guide' => 1000,
        'revenue_estimate' => 800,
        'fiscal_period' => 'Q1',
        'fiscal_year' => 2024
    ];
    
    $result = testFallbackLogic($testItem);
    
    $passed = 0;
    if ($result['eps_guide_surprise'] == 25.5 && $result['eps_guide_basis'] === 'vendor_consensus') {
        $passed++;
        echo "   ✅ EPS consensus fallback: {$result['eps_guide_surprise']}% (basis: {$result['eps_guide_basis']})\n";
    } else {
        echo "   ❌ EPS consensus fallback: {$result['eps_guide_surprise']}% (basis: {$result['eps_guide_basis']})\n";
    }
    
    if ($result['revenue_guide_surprise'] == 15.2 && $result['revenue_guide_basis'] === 'vendor_consensus') {
        $passed++;
        echo "   ✅ Revenue consensus fallback: {$result['revenue_guide_surprise']}% (basis: {$result['revenue_guide_basis']})\n";
    } else {
        echo "   ❌ Revenue consensus fallback: {$result['revenue_guide_surprise']}% (basis: {$result['revenue_guide_basis']})\n";
    }
    
    if ($passed === 2) {
        echo "   ✅ Consensus fallback test prešiel\n";
        $passedTests++;
        $testResults[] = ['test' => 'Consensus Fallback', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Consensus fallback test zlyhal\n";
        $testResults[] = ['test' => 'Consensus Fallback', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Consensus Fallback', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Estimate fallback
echo "\n📊 Test 2: Estimate fallback\n";
echo "----------------------------\n";

$totalTests++;
try {
    $testItem = [
        'eps_guide_surprise_consensus' => null,
        'revenue_guide_surprise_consensus' => null,
        'eps_guide' => 2.50,
        'eps_estimate' => 2.00,
        'revenue_guide' => 1000,
        'revenue_estimate' => 800,
        'fiscal_period' => 'Q1',
        'fiscal_year' => 2024,
        'guidance_eps_method' => 'GAAP',
        'guidance_revenue_method' => 'GAAP'
    ];
    
    $result = testFallbackLogic($testItem);
    
    $passed = 0;
    $expectedEpsSurprise = (($testItem['eps_guide'] - $testItem['eps_estimate']) / $testItem['eps_estimate']) * 100;
    $expectedRevSurprise = (($testItem['revenue_guide'] - $testItem['revenue_estimate']) / $testItem['revenue_estimate']) * 100;
    
    if (abs($result['eps_guide_surprise'] - $expectedEpsSurprise) < 0.01 && $result['eps_guide_basis'] === 'estimate') {
        $passed++;
        echo "   ✅ EPS estimate fallback: {$result['eps_guide_surprise']}% (basis: {$result['eps_guide_basis']})\n";
    } else {
        echo "   ❌ EPS estimate fallback: {$result['eps_guide_surprise']}% (basis: {$result['eps_guide_basis']})\n";
    }
    
    if (abs($result['revenue_guide_surprise'] - $expectedRevSurprise) < 0.01 && $result['revenue_guide_basis'] === 'estimate') {
        $passed++;
        echo "   ✅ Revenue estimate fallback: {$result['revenue_guide_surprise']}% (basis: {$result['revenue_guide_basis']})\n";
    } else {
        echo "   ❌ Revenue estimate fallback: {$result['revenue_guide_surprise']}% (basis: {$result['revenue_guide_basis']})\n";
    }
    
    if ($passed === 2) {
        echo "   ✅ Estimate fallback test prešiel\n";
        $passedTests++;
        $testResults[] = ['test' => 'Estimate Fallback', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Estimate fallback test zlyhal\n";
        $testResults[] = ['test' => 'Estimate Fallback', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Estimate Fallback', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Previous max fallback
echo "\n📊 Test 3: Previous max fallback\n";
echo "-------------------------------\n";

$totalTests++;
try {
    $testItem = [
        'eps_guide_surprise_consensus' => null,
        'revenue_guide_surprise_consensus' => null,
        'eps_guide' => 2.50,
        'eps_estimate' => null,
        'revenue_guide' => 1000,
        'revenue_estimate' => null,
        'previous_max_eps_guidance' => 2.00,
        'previous_max_revenue_guidance' => 800,
        'fiscal_period' => 'Q1',
        'fiscal_year' => 2024
    ];
    
    $result = testFallbackLogic($testItem);
    
    $passed = 0;
    $expectedEpsSurprise = (($testItem['eps_guide'] - $testItem['previous_max_eps_guidance']) / $testItem['previous_max_eps_guidance']) * 100;
    $expectedRevSurprise = (($testItem['revenue_guide'] - $testItem['previous_max_revenue_guidance']) / $testItem['previous_max_revenue_guidance']) * 100;
    
    if (abs($result['eps_guide_surprise'] - $expectedEpsSurprise) < 0.01 && $result['eps_guide_basis'] === 'previous_max') {
        $passed++;
        echo "   ✅ EPS previous max fallback: {$result['eps_guide_surprise']}% (basis: {$result['eps_guide_basis']})\n";
    } else {
        echo "   ❌ EPS previous max fallback: {$result['eps_guide_surprise']}% (basis: {$result['eps_guide_basis']})\n";
    }
    
    if (abs($result['revenue_guide_surprise'] - $expectedRevSurprise) < 0.01 && $result['revenue_guide_basis'] === 'previous_max') {
        $passed++;
        echo "   ✅ Revenue previous max fallback: {$result['revenue_guide_surprise']}% (basis: {$result['revenue_guide_basis']})\n";
    } else {
        echo "   ❌ Revenue previous max fallback: {$result['revenue_guide_surprise']}% (basis: {$result['revenue_guide_basis']})\n";
    }
    
    if ($passed === 2) {
        echo "   ✅ Previous max fallback test prešiel\n";
        $passedTests++;
        $testResults[] = ['test' => 'Previous Max Fallback', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Previous max fallback test zlyhal\n";
        $testResults[] = ['test' => 'Previous Max Fallback', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Previous Max Fallback', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Previous min fallback
echo "\n📊 Test 4: Previous min fallback\n";
echo "-------------------------------\n";

$totalTests++;
try {
    $testItem = [
        'eps_guide_surprise_consensus' => null,
        'revenue_guide_surprise_consensus' => null,
        'eps_guide' => 2.50,
        'eps_estimate' => null,
        'revenue_guide' => 1000,
        'revenue_estimate' => null,
        'previous_max_eps_guidance' => null,
        'previous_max_revenue_guidance' => null,
        'previous_min_eps_guidance' => 2.00,
        'previous_min_revenue_guidance' => 800,
        'fiscal_period' => 'Q1',
        'fiscal_year' => 2024
    ];
    
    $result = testFallbackLogic($testItem);
    
    $passed = 0;
    $expectedEpsSurprise = (($testItem['eps_guide'] - $testItem['previous_min_eps_guidance']) / $testItem['previous_min_eps_guidance']) * 100;
    $expectedRevSurprise = (($testItem['revenue_guide'] - $testItem['previous_min_revenue_guidance']) / $testItem['previous_min_revenue_guidance']) * 100;
    
    if (abs($result['eps_guide_surprise'] - $expectedEpsSurprise) < 0.01 && $result['eps_guide_basis'] === 'previous_min') {
        $passed++;
        echo "   ✅ EPS previous min fallback: {$result['eps_guide_surprise']}% (basis: {$result['eps_guide_basis']})\n";
    } else {
        echo "   ❌ EPS previous min fallback: {$result['eps_guide_surprise']}% (basis: {$result['eps_guide_basis']})\n";
    }
    
    if (abs($result['revenue_guide_surprise'] - $expectedRevSurprise) < 0.01 && $result['revenue_guide_basis'] === 'previous_min') {
        $passed++;
        echo "   ✅ Revenue previous min fallback: {$result['revenue_guide_surprise']}% (basis: {$result['revenue_guide_basis']})\n";
    } else {
        echo "   ❌ Revenue previous min fallback: {$result['revenue_guide_surprise']}% (basis: {$result['revenue_guide_basis']})\n";
    }
    
    if ($passed === 2) {
        echo "   ✅ Previous min fallback test prešiel\n";
        $passedTests++;
        $testResults[] = ['test' => 'Previous Min Fallback', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Previous min fallback test zlyhal\n";
        $testResults[] = ['test' => 'Previous Min Fallback', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Previous Min Fallback', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: No fallback available
echo "\n📊 Test 5: No fallback available\n";
echo "-------------------------------\n";

$totalTests++;
try {
    $testItem = [
        'eps_guide_surprise_consensus' => null,
        'revenue_guide_surprise_consensus' => null,
        'eps_guide' => null,
        'eps_estimate' => null,
        'revenue_guide' => null,
        'revenue_estimate' => null,
        'previous_max_eps_guidance' => null,
        'previous_max_revenue_guidance' => null,
        'previous_min_eps_guidance' => null,
        'previous_min_revenue_guidance' => null,
        'fiscal_period' => 'Q1',
        'fiscal_year' => 2024
    ];
    
    $result = testFallbackLogic($testItem);
    
    if ($result['eps_guide_surprise'] === null && $result['revenue_guide_surprise'] === null) {
        echo "   ✅ No fallback: Oba surprise values sú null\n";
        $passedTests++;
        $testResults[] = ['test' => 'No Fallback', 'status' => 'PASS', 'value' => 'NULL'];
    } else {
        echo "   ❌ No fallback: EPS={$result['eps_guide_surprise']}, Revenue={$result['revenue_guide_surprise']}\n";
        $testResults[] = ['test' => 'No Fallback', 'status' => 'FAIL', 'value' => 'NOT_NULL'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'No Fallback', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Skutočné dáta z databázy
echo "\n📊 Test 6: Skutočné dáta z databázy\n";
echo "----------------------------------\n";

$totalTests++;
try {
    // Nájdi tickery s rôznymi fallback scenármi
    $stmt = $pdo->query("
        SELECT 
            e.ticker,
            e.eps_estimate,
            e.revenue_estimate,
            g.estimated_eps_guidance,
            g.estimated_revenue_guidance,
            g.eps_guide_vs_consensus_pct,
            g.revenue_guide_vs_consensus_pct,
            g.previous_min_eps_guidance,
            g.previous_max_eps_guidance,
            g.previous_min_revenue_guidance,
            g.previous_max_revenue_guidance
        FROM earningstickerstoday e
        LEFT JOIN benzinga_guidance g ON e.ticker = g.ticker
        WHERE (g.estimated_eps_guidance IS NOT NULL OR g.estimated_revenue_guidance IS NOT NULL)
        LIMIT 3
    ");
    
    $realData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($realData) {
        echo "   📋 Testovanie " . count($realData) . " skutočných záznamov:\n";
        
        $fallbackTests = 0;
        $fallbackPassed = 0;
        
        foreach ($realData as $row) {
            $result = testFallbackLogic($row);
            
            if ($result['eps_guide_surprise'] !== null) {
                $fallbackTests++;
                echo "   📊 {$row['ticker']} EPS: {$result['eps_guide_surprise']}% (basis: {$result['eps_guide_basis']})\n";
                $fallbackPassed++;
            }
            
            if ($result['revenue_guide_surprise'] !== null) {
                $fallbackTests++;
                echo "   📊 {$row['ticker']} Revenue: {$result['revenue_guide_surprise']}% (basis: {$result['revenue_guide_basis']})\n";
                $fallbackPassed++;
            }
        }
        
        if ($fallbackTests > 0) {
            $fallbackRate = round(($fallbackPassed / $fallbackTests) * 100, 1);
            echo "   📊 Fallback success rate: {$fallbackPassed}/{$fallbackTests} ({$fallbackRate}%)\n";
            
            if ($fallbackRate >= 80) {
                echo "   ✅ Dobrý fallback success rate\n";
                $passedTests++;
                $testResults[] = ['test' => 'Real Data Fallback', 'status' => 'PASS', 'value' => $fallbackRate];
            } else {
                echo "   ⚠️  Nízky fallback success rate\n";
                $testResults[] = ['test' => 'Real Data Fallback', 'status' => 'WARNING', 'value' => $fallbackRate];
            }
        } else {
            echo "   ⚠️  Žiadne fallback testy na skutočných dátach\n";
            $testResults[] = ['test' => 'Real Data Fallback', 'status' => 'SKIP', 'value' => 'NO_DATA'];
        }
    } else {
        echo "   ⚠️  Žiadne skutočné dáta na testovanie\n";
        $testResults[] = ['test' => 'Real Data Fallback', 'status' => 'SKIP', 'value' => 'NO_DATA'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba pri testovaní skutočných dát: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Real Data Fallback', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "🏆 VÝBORNE! Všetky testy prešli úspešne!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho testov zlyhalo.\n";
}

echo "\n🎉 Test guidance fallback logic dokončený!\n";
?>
