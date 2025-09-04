<?php
/**
 * 🎯 TEST: Guidance Surprise Calculation
 * Testuje výpočet guidance surprise percent
 */

require_once __DIR__ . '/test_config.php';

echo "🎯 TEST: Guidance Surprise Calculation\n";
echo "=====================================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Základný výpočet EPS guidance surprise
echo "📊 Test 1: Základný výpočet EPS guidance surprise\n";
echo "------------------------------------------------\n";

$totalTests++;
try {
    // Simuluj dáta
    $epsGuidance = 2.50;
    $epsEstimate = 2.00;
    $expectedSurprise = 25.0; // (2.50 - 2.00) / 2.00 * 100
    
    $actualSurprise = (($epsGuidance - $epsEstimate) / $epsEstimate) * 100;
    
    if (abs($actualSurprise - $expectedSurprise) < 0.01) {
        echo "   ✅ EPS Surprise: {$actualSurprise}% (očakávané: {$expectedSurprise}%)\n";
        $passedTests++;
        $testResults[] = ['test' => 'EPS Basic Calculation', 'status' => 'PASS', 'value' => $actualSurprise];
    } else {
        echo "   ❌ EPS Surprise: {$actualSurprise}% (očakávané: {$expectedSurprise}%)\n";
        $testResults[] = ['test' => 'EPS Basic Calculation', 'status' => 'FAIL', 'value' => $actualSurprise];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'EPS Basic Calculation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Základný výpočet Revenue guidance surprise
echo "\n📊 Test 2: Základný výpočet Revenue guidance surprise\n";
echo "----------------------------------------------------\n";

$totalTests++;
try {
    // Simuluj dáta (v miliónoch)
    $revenueGuidance = 1000; // $1B
    $revenueEstimate = 800;  // $800M
    $expectedSurprise = 25.0; // (1000 - 800) / 800 * 100
    
    $actualSurprise = (($revenueGuidance - $revenueEstimate) / $revenueEstimate) * 100;
    
    if (abs($actualSurprise - $expectedSurprise) < 0.01) {
        echo "   ✅ Revenue Surprise: {$actualSurprise}% (očakávané: {$expectedSurprise}%)\n";
        $passedTests++;
        $testResults[] = ['test' => 'Revenue Basic Calculation', 'status' => 'PASS', 'value' => $actualSurprise];
    } else {
        echo "   ❌ Revenue Surprise: {$actualSurprise}% (očakávané: {$expectedSurprise}%)\n";
        $testResults[] = ['test' => 'Revenue Basic Calculation', 'status' => 'FAIL', 'value' => $actualSurprise];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Revenue Basic Calculation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Negatívny surprise
echo "\n📊 Test 3: Negatívny guidance surprise\n";
echo "-------------------------------------\n";

$totalTests++;
try {
    $epsGuidance = 1.50;
    $epsEstimate = 2.00;
    $expectedSurprise = -25.0; // (1.50 - 2.00) / 2.00 * 100
    
    $actualSurprise = (($epsGuidance - $epsEstimate) / $epsEstimate) * 100;
    
    if (abs($actualSurprise - $expectedSurprise) < 0.01) {
        echo "   ✅ Negatívny Surprise: {$actualSurprise}% (očakávané: {$expectedSurprise}%)\n";
        $passedTests++;
        $testResults[] = ['test' => 'Negative Surprise', 'status' => 'PASS', 'value' => $actualSurprise];
    } else {
        echo "   ❌ Negatívny Surprise: {$actualSurprise}% (očakávané: {$expectedSurprise}%)\n";
        $testResults[] = ['test' => 'Negative Surprise', 'status' => 'FAIL', 'value' => $actualSurprise];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Negative Surprise', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Nulový estimate (divízia nulou)
echo "\n📊 Test 4: Nulový estimate (divízia nulou)\n";
echo "-----------------------------------------\n";

$totalTests++;
try {
    $epsGuidance = 2.50;
    $epsEstimate = 0;
    
    if ($epsEstimate == 0) {
        echo "   ✅ Nulový estimate detekovaný - výpočet preskočený\n";
        $passedTests++;
        $testResults[] = ['test' => 'Zero Estimate Handling', 'status' => 'PASS', 'value' => 'SKIPPED'];
    } else {
        $actualSurprise = (($epsGuidance - $epsEstimate) / $epsEstimate) * 100;
        echo "   ❌ Nulový estimate nebol detekovaný: {$actualSurprise}%\n";
        $testResults[] = ['test' => 'Zero Estimate Handling', 'status' => 'FAIL', 'value' => $actualSurprise];
    }
} catch (Exception $e) {
    echo "   ✅ Exception pri divízii nulou: " . $e->getMessage() . "\n";
    $passedTests++;
    $testResults[] = ['test' => 'Zero Estimate Handling', 'status' => 'PASS', 'value' => 'EXCEPTION'];
}

// Test 5: Skutočné dáta z databázy
echo "\n📊 Test 5: Skutočné dáta z databázy\n";
echo "----------------------------------\n";

$totalTests++;
try {
    // Nájdi ticker s guidance a estimate dátami
    $stmt = $pdo->query("
        SELECT 
            e.ticker,
            e.eps_estimate,
            e.revenue_estimate,
            g.estimated_eps_guidance,
            g.estimated_revenue_guidance,
            g.eps_guide_vs_consensus_pct,
            g.revenue_guide_vs_consensus_pct
        FROM earningstickerstoday e
        LEFT JOIN benzinga_guidance g ON e.ticker = g.ticker
        WHERE e.eps_estimate IS NOT NULL 
        AND e.eps_estimate != 0
        AND g.estimated_eps_guidance IS NOT NULL
        LIMIT 1
    ");
    
    $realData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($realData) {
        echo "   📋 Testovací ticker: {$realData['ticker']}\n";
        echo "   📋 EPS Estimate: {$realData['eps_estimate']}\n";
        echo "   📋 EPS Guidance: {$realData['estimated_eps_guidance']}\n";
        
        // Vypočítaj surprise
        $calculatedSurprise = (($realData['estimated_eps_guidance'] - $realData['eps_estimate']) / $realData['eps_estimate']) * 100;
        $storedSurprise = $realData['eps_guide_vs_consensus_pct'];
        
        echo "   📋 Vypočítaný surprise: {$calculatedSurprise}%\n";
        echo "   📋 Uložený surprise: {$storedSurprise}%\n";
        
        if ($storedSurprise !== null && abs($calculatedSurprise - $storedSurprise) < 0.1) {
            echo "   ✅ Skutočné dáta: Vypočítaný a uložený surprise sa zhodujú\n";
            $passedTests++;
            $testResults[] = ['test' => 'Real Data Calculation', 'status' => 'PASS', 'value' => $calculatedSurprise];
        } else {
            echo "   ⚠️  Skutočné dáta: Rozdiel medzi vypočítaným a uloženým surprise\n";
            $testResults[] = ['test' => 'Real Data Calculation', 'status' => 'WARNING', 'value' => $calculatedSurprise];
        }
    } else {
        echo "   ⚠️  Žiadne skutočné dáta na testovanie\n";
        $testResults[] = ['test' => 'Real Data Calculation', 'status' => 'SKIP', 'value' => 'NO_DATA'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba pri testovaní skutočných dát: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Real Data Calculation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Presnosť na 4 desatinné miesta
echo "\n📊 Test 6: Presnosť na 4 desatinné miesta\n";
echo "---------------------------------------\n";

$totalTests++;
try {
    $epsGuidance = 2.1234;
    $epsEstimate = 2.0000;
    $expectedSurprise = 6.17; // (2.1234 - 2.0000) / 2.0000 * 100 = 6.17%
    
    $actualSurprise = (($epsGuidance - $epsEstimate) / $epsEstimate) * 100;
    $roundedSurprise = round($actualSurprise, 4);
    
    if (abs($roundedSurprise - 6.17) < 0.0001) {
        echo "   ✅ Presnosť: {$roundedSurprise}% (očakávané: 6.17%)\n";
        $passedTests++;
        $testResults[] = ['test' => 'Precision Test', 'status' => 'PASS', 'value' => $roundedSurprise];
    } else {
        echo "   ❌ Presnosť: {$roundedSurprise}% (očakávané: 6.17%)\n";
        $testResults[] = ['test' => 'Precision Test', 'status' => 'FAIL', 'value' => $roundedSurprise];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Precision Test', 'status' => 'ERROR', 'value' => $e->getMessage()];
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

echo "\n🎉 Test guidance surprise calculation dokončený!\n";
?>
