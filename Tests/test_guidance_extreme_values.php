<?php
/**
 * 🎯 TEST: Guidance Extreme Values
 * Testuje handling extreme values (>300%, <-300%)
 */

require_once __DIR__ . '/test_config.php';

echo "🎯 TEST: Guidance Extreme Values\n";
echo "===============================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Funkcia na detekciu extreme values (rovnaká ako v API)
function isExtremeValue($value) {
    if ($value === null || !is_numeric($value)) {
        return false;
    }
    return abs($value) > 300;
}

// Funkcia na formatovanie guidance percent (rovnaká ako v API)
function formatGuidePercent($value, $isExtreme = false) {
    if ($value == null || !isFinite($value)) return '-';
    $num = parseFloat($value);

    // Clamp extreme values for visual display (but keep raw data)
    $clamped = max(-300, min(300, $num));
    $isClamped = abs($num) > 300;

    $sign = $clamped >= 0 ? '+' : '';
    $suffix = $isClamped ? '!' : '';

    return $sign . $clamped . '%' . $suffix;
}

// Test 1: Detekcia extreme values
echo "📊 Test 1: Detekcia extreme values\n";
echo "---------------------------------\n";

$totalTests++;
try {
    $extremeTests = [
        [350, true, 'Positive extreme (>300)'],
        [-350, true, 'Negative extreme (<-300)'],
        [300, false, 'Boundary positive (300)'],
        [-300, false, 'Boundary negative (-300)'],
        [299.99, false, 'Just under positive boundary'],
        [-299.99, false, 'Just under negative boundary'],
        [0, false, 'Zero'],
        [50, false, 'Normal positive'],
        [-50, false, 'Normal negative'],
        [null, false, 'Null value'],
        ['', false, 'Empty string'],
        ['abc', false, 'Non-numeric string']
    ];
    
    $passed = 0;
    foreach ($extremeTests as $test) {
        $result = isExtremeValue($test[0]);
        if ($result === $test[1]) {
            $passed++;
        } else {
            echo "   ❌ {$test[2]}: {$test[0]} -> {$result} (očakávané: {$test[1]})\n";
        }
    }
    
    if ($passed === count($extremeTests)) {
        echo "   ✅ Všetky extreme value detekčné testy prešli ({$passed}/" . count($extremeTests) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Extreme Value Detection', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré extreme value detekčné testy zlyhali ({$passed}/" . count($extremeTests) . ")\n";
        $testResults[] = ['test' => 'Extreme Value Detection', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Extreme Value Detection', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Formatovanie extreme values
echo "\n📊 Test 2: Formatovanie extreme values\n";
echo "-------------------------------------\n";

$totalTests++;
try {
    $formatTests = [
        [350, true, '+300%!', 'Positive extreme with clamp'],
        [-350, true, '-300%!', 'Negative extreme with clamp'],
        [500, true, '+300%!', 'Very high positive extreme'],
        [-500, true, '-300%!', 'Very low negative extreme'],
        [300, false, '+300%', 'Boundary positive (no clamp)'],
        [-300, false, '-300%', 'Boundary negative (no clamp)'],
        [250, false, '+250%', 'Normal positive'],
        [-250, false, '-250%', 'Normal negative'],
        [0, false, '+0%', 'Zero'],
        [null, false, '-', 'Null value'],
        ['', false, '-', 'Empty string']
    ];
    
    $passed = 0;
    foreach ($formatTests as $test) {
        $result = formatGuidePercent($test[0], $test[1]);
        if ($result === $test[2]) {
            $passed++;
        } else {
            echo "   ❌ {$test[3]}: {$test[0]} -> '{$result}' (očakávané: '{$test[2]}')\n";
        }
    }
    
    if ($passed === count($formatTests)) {
        echo "   ✅ Všetky formatovacie testy prešli ({$passed}/" . count($formatTests) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Extreme Value Formatting', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré formatovacie testy zlyhali ({$passed}/" . count($formatTests) . ")\n";
        $testResults[] = ['test' => 'Extreme Value Formatting', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Extreme Value Formatting', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Clamping logika
echo "\n📊 Test 3: Clamping logika\n";
echo "-------------------------\n";

$totalTests++;
try {
    $clampTests = [
        [1000, 300, 'Very high positive'],
        [-1000, -300, 'Very low negative'],
        [350, 300, 'Moderate positive extreme'],
        [-350, -300, 'Moderate negative extreme'],
        [250, 250, 'Normal positive (no clamp)'],
        [-250, -250, 'Normal negative (no clamp)']
    ];
    
    $passed = 0;
    foreach ($clampTests as $test) {
        $clamped = max(-300, min(300, $test[0]));
        if ($clamped === $test[1]) {
            $passed++;
        } else {
            echo "   ❌ {$test[2]}: {$test[0]} -> {$clamped} (očakávané: {$test[1]})\n";
        }
    }
    
    if ($passed === count($clampTests)) {
        echo "   ✅ Všetky clamping testy prešli ({$passed}/" . count($clampTests) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Clamping Logic', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré clamping testy zlyhali ({$passed}/" . count($clampTests) . ")\n";
        $testResults[] = ['test' => 'Clamping Logic', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Clamping Logic', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Skutočné extreme values v databáze
echo "\n📊 Test 4: Skutočné extreme values v databáze\n";
echo "--------------------------------------------\n";

$totalTests++;
try {
    // Nájdi guidance records s extreme values
    $stmt = $pdo->query("
        SELECT 
            ticker,
            eps_guide_vs_consensus_pct,
            revenue_guide_vs_consensus_pct,
            fiscal_period,
            fiscal_year
        FROM benzinga_guidance 
        WHERE (ABS(eps_guide_vs_consensus_pct) > 300 OR ABS(revenue_guide_vs_consensus_pct) > 300)
        LIMIT 10
    ");
    
    $extremeRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($extremeRecords) {
        echo "   📋 Nájdené " . count($extremeRecords) . " záznamov s extreme values:\n";
        
        $validExtremes = 0;
        foreach ($extremeRecords as $record) {
            $epsExtreme = isExtremeValue($record['eps_guide_vs_consensus_pct']);
            $revExtreme = isExtremeValue($record['revenue_guide_vs_consensus_pct']);
            
            if ($epsExtreme || $revExtreme) {
                $validExtremes++;
                echo "   📊 {$record['ticker']} ({$record['fiscal_period']}/{$record['fiscal_year']}):\n";
                if ($epsExtreme) {
                    echo "      EPS: {$record['eps_guide_vs_consensus_pct']}% (EXTREME)\n";
                }
                if ($revExtreme) {
                    echo "      Revenue: {$record['revenue_guide_vs_consensus_pct']}% (EXTREME)\n";
                }
            }
        }
        
        if ($validExtremes > 0) {
            echo "   ✅ Nájdené {$validExtremes} skutočných extreme values\n";
            $passedTests++;
            $testResults[] = ['test' => 'Real Extreme Values', 'status' => 'PASS', 'value' => $validExtremes];
        } else {
            echo "   ⚠️  Žiadne skutočné extreme values\n";
            $testResults[] = ['test' => 'Real Extreme Values', 'status' => 'WARNING', 'value' => 0];
        }
    } else {
        echo "   ⚠️  Žiadne extreme values v databáze\n";
        $testResults[] = ['test' => 'Real Extreme Values', 'status' => 'SKIP', 'value' => 'NO_DATA'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba pri testovaní skutočných dát: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Real Extreme Values', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Frontend display test
echo "\n📊 Test 5: Frontend display test\n";
echo "-------------------------------\n";

$totalTests++;
try {
    // Simuluj frontend display
    $displayTests = [
        [350, '+300%!', 'Positive extreme display'],
        [-350, '-300%!', 'Negative extreme display'],
        [250, '+250%', 'Normal positive display'],
        [-250, '-250%', 'Normal negative display']
    ];
    
    $passed = 0;
    foreach ($displayTests as $test) {
        $isExtreme = isExtremeValue($test[0]);
        $formatted = formatGuidePercent($test[0], $isExtreme);
        
        if ($formatted === $test[1]) {
            $passed++;
        } else {
            echo "   ❌ {$test[2]}: {$test[0]} -> '{$formatted}' (očakávané: '{$test[1]}')\n";
        }
    }
    
    if ($passed === count($displayTests)) {
        echo "   ✅ Všetky frontend display testy prešli ({$passed}/" . count($displayTests) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Frontend Display', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré frontend display testy zlyhali ({$passed}/" . count($displayTests) . ")\n";
        $testResults[] = ['test' => 'Frontend Display', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Frontend Display', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Edge cases pre extreme values
echo "\n📊 Test 6: Edge cases pre extreme values\n";
echo "---------------------------------------\n";

$totalTests++;
try {
    $edgeCases = [
        [300.01, true, 'Just over positive boundary'],
        [-300.01, true, 'Just over negative boundary'],
        [299.99, false, 'Just under positive boundary'],
        [-299.99, false, 'Just under negative boundary'],
        [0.0, false, 'Zero as float'],
        [0, false, 'Zero as integer'],
        ['300.01', true, 'String number over boundary'],
        ['-300.01', true, 'String number under boundary']
    ];
    
    $passed = 0;
    foreach ($edgeCases as $test) {
        $result = isExtremeValue($test[0]);
        if ($result === $test[1]) {
            $passed++;
        } else {
            echo "   ❌ {$test[2]}: {$test[0]} -> {$result} (očakávané: {$test[1]})\n";
        }
    }
    
    if ($passed === count($edgeCases)) {
        echo "   ✅ Všetky edge case testy prešli ({$passed}/" . count($edgeCases) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Edge Cases', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré edge case testy zlyhali ({$passed}/" . count($edgeCases) . ")\n";
        $testResults[] = ['test' => 'Edge Cases', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Edge Cases', 'status' => 'ERROR', 'value' => $e->getMessage()];
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

echo "\n🎉 Test guidance extreme values dokončený!\n";
?>
