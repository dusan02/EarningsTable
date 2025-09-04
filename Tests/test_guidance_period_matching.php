<?php
/**
 * 🎯 TEST: Guidance Period Matching
 * Testuje matching fiscal periods (Q1 vs Q1, FY vs FY)
 */

require_once __DIR__ . '/test_config.php';

echo "🎯 TEST: Guidance Period Matching\n";
echo "================================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Funkcia na normalizáciu period (rovnaká ako v API)
function normalizePeriod($period) {
    if (empty($period)) return null;
    
    switch (strtoupper(trim($period))) {
        case 'Q1': case '1Q': return 'Q1';
        case 'Q2': case '2Q': return 'Q2';
        case 'Q3': case '3Q': return 'Q3';
        case 'Q4': case '4Q': return 'Q4';
        case 'FY': case 'FULL YEAR': return 'FY';
        case 'H1': case '1H': case 'HALF YEAR': return 'H1';
        case 'H2': case '2H': return 'H2';
        default: return strtoupper(trim($period));
    }
}

// Funkcia na testovanie period matchingu
function periodsMatch($guidancePeriod, $guidanceYear, $estimatePeriod, $estimateYear) {
    if (empty($guidancePeriod) || empty($guidanceYear) || empty($estimatePeriod) || empty($estimateYear)) {
        return false;
    }
    
    $normalizedGuidancePeriod = normalizePeriod($guidancePeriod);
    $normalizedEstimatePeriod = normalizePeriod($estimatePeriod);
    
    return $normalizedGuidancePeriod === $normalizedEstimatePeriod && $guidanceYear == $estimateYear;
}

// Test 1: Základný period matching
echo "📊 Test 1: Základný period matching\n";
echo "----------------------------------\n";

$totalTests++;
try {
    $testCases = [
        ['Q1', 2024, 'Q1', 2024, true, 'Q1 vs Q1 same year'],
        ['Q2', 2024, 'Q2', 2024, true, 'Q2 vs Q2 same year'],
        ['Q3', 2024, 'Q3', 2024, true, 'Q3 vs Q3 same year'],
        ['Q4', 2024, 'Q4', 2024, true, 'Q4 vs Q4 same year'],
        ['FY', 2024, 'FY', 2024, true, 'FY vs FY same year'],
        ['H1', 2024, 'H1', 2024, true, 'H1 vs H1 same year'],
        ['H2', 2024, 'H2', 2024, true, 'H2 vs H2 same year']
    ];
    
    $passed = 0;
    foreach ($testCases as $case) {
        $result = periodsMatch($case[0], $case[1], $case[2], $case[3]);
        if ($result === $case[4]) {
            $passed++;
        } else {
            echo "   ❌ {$case[5]}: {$case[0]}/{$case[1]} vs {$case[2]}/{$case[3]} = {$result} (očakávané: {$case[4]})\n";
        }
    }
    
    if ($passed === count($testCases)) {
        echo "   ✅ Všetky základné period matching testy prešli ({$passed}/" . count($testCases) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Basic Period Matching', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré základné period matching testy zlyhali ({$passed}/" . count($testCases) . ")\n";
        $testResults[] = ['test' => 'Basic Period Matching', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Basic Period Matching', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Normalizácia period
echo "\n📊 Test 2: Normalizácia period\n";
echo "-----------------------------\n";

$totalTests++;
try {
    $normalizationTests = [
        ['Q1', 'Q1'],
        ['1Q', 'Q1'],
        ['Q2', 'Q2'],
        ['2Q', 'Q2'],
        ['Q3', 'Q3'],
        ['3Q', 'Q3'],
        ['Q4', 'Q4'],
        ['4Q', 'Q4'],
        ['FY', 'FY'],
        ['FULL YEAR', 'FY'],
        ['H1', 'H1'],
        ['1H', 'H1'],
        ['HALF YEAR', 'H1'],
        ['H2', 'H2'],
        ['2H', 'H2']
    ];
    
    $passed = 0;
    foreach ($normalizationTests as $test) {
        $result = normalizePeriod($test[0]);
        if ($result === $test[1]) {
            $passed++;
        } else {
            echo "   ❌ '{$test[0]}' -> '{$result}' (očakávané: '{$test[1]}')\n";
        }
    }
    
    if ($passed === count($normalizationTests)) {
        echo "   ✅ Všetky normalizačné testy prešli ({$passed}/" . count($normalizationTests) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Period Normalization', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré normalizačné testy zlyhali ({$passed}/" . count($normalizationTests) . ")\n";
        $testResults[] = ['test' => 'Period Normalization', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Period Normalization', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Nezhodné periody
echo "\n📊 Test 3: Nezhodné periody\n";
echo "--------------------------\n";

$totalTests++;
try {
    $mismatchTests = [
        ['Q1', 2024, 'Q2', 2024, false, 'Q1 vs Q2 same year'],
        ['Q1', 2024, 'Q1', 2025, false, 'Q1 vs Q1 different year'],
        ['FY', 2024, 'Q1', 2024, false, 'FY vs Q1 same year'],
        ['H1', 2024, 'Q1', 2024, false, 'H1 vs Q1 same year'],
        ['Q3', 2024, 'H2', 2024, false, 'Q3 vs H2 same year']
    ];
    
    $passed = 0;
    foreach ($mismatchTests as $test) {
        $result = periodsMatch($test[0], $test[1], $test[2], $test[3]);
        if ($result === $test[4]) {
            $passed++;
        } else {
            echo "   ❌ {$test[5]}: {$test[0]}/{$test[1]} vs {$test[2]}/{$test[3]} = {$result} (očakávané: {$test[4]})\n";
        }
    }
    
    if ($passed === count($mismatchTests)) {
        echo "   ✅ Všetky nezhodné period testy prešli ({$passed}/" . count($mismatchTests) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Period Mismatch', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré nezhodné period testy zlyhali ({$passed}/" . count($mismatchTests) . ")\n";
        $testResults[] = ['test' => 'Period Mismatch', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Period Mismatch', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Prázdne/null hodnoty
echo "\n📊 Test 4: Prázdne/null hodnoty\n";
echo "------------------------------\n";

$totalTests++;
try {
    $nullTests = [
        [null, 2024, 'Q1', 2024, false, 'null guidance period'],
        ['Q1', null, 'Q1', 2024, false, 'null guidance year'],
        ['Q1', 2024, null, 2024, false, 'null estimate period'],
        ['Q1', 2024, 'Q1', null, false, 'null estimate year'],
        ['', 2024, 'Q1', 2024, false, 'empty guidance period'],
        ['Q1', 2024, '', 2024, false, 'empty estimate period']
    ];
    
    $passed = 0;
    foreach ($nullTests as $test) {
        $result = periodsMatch($test[0], $test[1], $test[2], $test[3]);
        if ($result === $test[4]) {
            $passed++;
        } else {
            echo "   ❌ {$test[5]}: {$test[0]}/{$test[1]} vs {$test[2]}/{$test[3]} = {$result} (očakávané: {$test[4]})\n";
        }
    }
    
    if ($passed === count($nullTests)) {
        echo "   ✅ Všetky null/empty testy prešli ({$passed}/" . count($nullTests) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Null/Empty Handling', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré null/empty testy zlyhali ({$passed}/" . count($nullTests) . ")\n";
        $testResults[] = ['test' => 'Null/Empty Handling', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Null/Empty Handling', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Skutočné dáta z databázy
echo "\n📊 Test 5: Skutočné dáta z databázy\n";
echo "----------------------------------\n";

$totalTests++;
try {
    // Nájdi tickery s guidance a estimate dátami
    $stmt = $pdo->query("
        SELECT 
            e.ticker,
            e.fiscal_period as estimate_period,
            e.fiscal_year as estimate_year,
            g.fiscal_period as guidance_period,
            g.fiscal_year as guidance_year
        FROM earningstickerstoday e
        LEFT JOIN benzinga_guidance g ON e.ticker = g.ticker
        WHERE e.fiscal_period IS NOT NULL 
        AND e.fiscal_year IS NOT NULL
        AND g.fiscal_period IS NOT NULL
        AND g.fiscal_year IS NOT NULL
        LIMIT 5
    ");
    
    $realData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($realData) {
        echo "   📋 Testovanie " . count($realData) . " skutočných záznamov:\n";
        
        $matched = 0;
        $total = count($realData);
        
        foreach ($realData as $row) {
            $isMatch = periodsMatch(
                $row['guidance_period'], 
                $row['guidance_year'],
                $row['estimate_period'], 
                $row['estimate_year']
            );
            
            if ($isMatch) {
                $matched++;
                echo "   ✅ {$row['ticker']}: {$row['guidance_period']}/{$row['guidance_year']} vs {$row['estimate_period']}/{$row['estimate_year']} = MATCH\n";
            } else {
                echo "   ❌ {$row['ticker']}: {$row['guidance_period']}/{$row['guidance_year']} vs {$row['estimate_period']}/{$row['estimate_year']} = NO MATCH\n";
            }
        }
        
        $matchRate = round(($matched / $total) * 100, 1);
        echo "   📊 Match rate: {$matched}/{$total} ({$matchRate}%)\n";
        
        if ($matchRate >= 80) {
            echo "   ✅ Dobrý match rate pre skutočné dáta\n";
            $passedTests++;
            $testResults[] = ['test' => 'Real Data Matching', 'status' => 'PASS', 'value' => $matchRate];
        } else {
            echo "   ⚠️  Nízky match rate pre skutočné dáta\n";
            $testResults[] = ['test' => 'Real Data Matching', 'status' => 'WARNING', 'value' => $matchRate];
        }
    } else {
        echo "   ⚠️  Žiadne skutočné dáta na testovanie\n";
        $testResults[] = ['test' => 'Real Data Matching', 'status' => 'SKIP', 'value' => 'NO_DATA'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba pri testovaní skutočných dát: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Real Data Matching', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Edge cases
echo "\n📊 Test 6: Edge cases\n";
echo "--------------------\n";

$totalTests++;
try {
    $edgeCases = [
        ['q1', 2024, 'Q1', 2024, true, 'lowercase vs uppercase'],
        ['Q1', 2024, 'q1', 2024, true, 'uppercase vs lowercase'],
        [' Q1 ', 2024, 'Q1', 2024, true, 'whitespace trimming'],
        ['Q1', 2024, ' Q1 ', 2024, true, 'whitespace trimming'],
        ['1Q', 2024, 'Q1', 2024, true, 'alternative format matching']
    ];
    
    $passed = 0;
    foreach ($edgeCases as $case) {
        $result = periodsMatch($case[0], $case[1], $case[2], $case[3]);
        if ($result === $case[4]) {
            $passed++;
        } else {
            echo "   ❌ {$case[5]}: {$case[0]}/{$case[1]} vs {$case[2]}/{$case[3]} = {$result} (očakávané: {$case[4]})\n";
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

echo "\n🎉 Test guidance period matching dokončený!\n";
?>
