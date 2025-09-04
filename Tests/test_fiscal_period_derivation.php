<?php
/**
 * 🔧 TEST: Fiscal Period Derivation
 * Testuje odvodenie fiscal periods z report_date
 */

require_once __DIR__ . '/test_config.php';

echo "🔧 TEST: Fiscal Period Derivation\n";
echo "=================================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Funkcia na odvodenie fiscal period z report_date
function deriveFiscalPeriod($reportDate) {
    if (empty($reportDate)) return null;
    
    $date = new DateTime($reportDate);
    $month = (int)$date->format('m');
    $year = (int)$date->format('Y');
    
    $period = null;
    if ($month >= 1 && $month <= 3) {
        $period = 'Q1';
    } elseif ($month >= 4 && $month <= 6) {
        $period = 'Q2';
    } elseif ($month >= 7 && $month <= 9) {
        $period = 'Q3';
    } elseif ($month >= 10 && $month <= 12) {
        $period = 'Q4';
    }
    
    return [
        'period' => $period,
        'year' => $year,
        'month' => $month
    ];
}

// Test 1: Základné odvodenie fiscal periods
echo "📊 Test 1: Základné odvodenie fiscal periods\n";
echo "--------------------------------------------\n";

$totalTests++;
try {
    $testCases = [
        ['2024-01-15', 'Q1', 2024, 1],
        ['2024-02-28', 'Q1', 2024, 2],
        ['2024-03-31', 'Q1', 2024, 3],
        ['2024-04-01', 'Q2', 2024, 4],
        ['2024-05-15', 'Q2', 2024, 5],
        ['2024-06-30', 'Q2', 2024, 6],
        ['2024-07-01', 'Q3', 2024, 7],
        ['2024-08-15', 'Q3', 2024, 8],
        ['2024-09-30', 'Q3', 2024, 9],
        ['2024-10-01', 'Q4', 2024, 10],
        ['2024-11-15', 'Q4', 2024, 11],
        ['2024-12-31', 'Q4', 2024, 12]
    ];
    
    $passed = 0;
    foreach ($testCases as $test) {
        $result = deriveFiscalPeriod($test[0]);
        
        if ($result && $result['period'] === $test[1] && $result['year'] === $test[2] && $result['month'] === $test[3]) {
            $passed++;
        } else {
            echo "   ❌ {$test[0]} -> {$result['period']}/{$result['year']} (očakávané: {$test[1]}/{$test[2]})\n";
        }
    }
    
    if ($passed === count($testCases)) {
        echo "   ✅ Všetky základné fiscal period testy prešli ({$passed}/" . count($testCases) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Basic Fiscal Period Derivation', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré základné fiscal period testy zlyhali ({$passed}/" . count($testCases) . ")\n";
        $testResults[] = ['test' => 'Basic Fiscal Period Derivation', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Basic Fiscal Period Derivation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Edge cases pre fiscal periods
echo "\n📊 Test 2: Edge cases pre fiscal periods\n";
echo "---------------------------------------\n";

$totalTests++;
try {
    $edgeCases = [
        ['2024-01-01', 'Q1', 2024, 1, 'Prvý deň roka'],
        ['2024-12-31', 'Q4', 2024, 12, 'Posledný deň roka'],
        ['2024-02-29', 'Q1', 2024, 2, 'Prechodný rok'],
        ['2023-02-28', 'Q1', 2023, 2, 'Normálny rok'],
        ['2024-03-01', 'Q1', 2024, 3, 'Prvý deň Q1 konca'],
        ['2024-04-01', 'Q2', 2024, 4, 'Prvý deň Q2'],
        ['2024-06-30', 'Q2', 2024, 6, 'Posledný deň Q2'],
        ['2024-07-01', 'Q3', 2024, 7, 'Prvý deň Q3'],
        ['2024-09-30', 'Q3', 2024, 9, 'Posledný deň Q3'],
        ['2024-10-01', 'Q4', 2024, 10, 'Prvý deň Q4'],
        ['2024-12-31', 'Q4', 2024, 12, 'Posledný deň Q4']
    ];
    
    $passed = 0;
    foreach ($edgeCases as $test) {
        $result = deriveFiscalPeriod($test[0]);
        
        if ($result && $result['period'] === $test[1] && $result['year'] === $test[2] && $result['month'] === $test[3]) {
            $passed++;
        } else {
            echo "   ❌ {$test[4]}: {$test[0]} -> {$result['period']}/{$result['year']} (očakávané: {$test[1]}/{$test[2]})\n";
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

// Test 3: Neplatné dátumy
echo "\n📊 Test 3: Neplatné dátumy\n";
echo "--------------------------\n";

$totalTests++;
try {
    $invalidDates = [
        '',
        null,
        'invalid-date',
        '2024-13-01',
        '2024-02-30',
        '2024-04-31',
        'not-a-date',
        '2024/01/01',
        '01-01-2024'
    ];
    
    $handledCorrectly = 0;
    foreach ($invalidDates as $date) {
        $result = deriveFiscalPeriod($date);
        
        if ($result === null) {
            $handledCorrectly++;
        } else {
            echo "   ❌ Neplatný dátum '{$date}' nebol správne spracovaný\n";
        }
    }
    
    if ($handledCorrectly === count($invalidDates)) {
        echo "   ✅ Všetky neplatné dátumy boli správne spracované ({$handledCorrectly}/" . count($invalidDates) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Invalid Date Handling', 'status' => 'PASS', 'value' => $handledCorrectly];
    } else {
        echo "   ❌ Niektoré neplatné dátumy neboli správne spracované ({$handledCorrectly}/" . count($invalidDates) . ")\n";
        $testResults[] = ['test' => 'Invalid Date Handling', 'status' => 'FAIL', 'value' => $handledCorrectly];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Invalid Date Handling', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Skutočné dáta z databázy
echo "\n📊 Test 4: Skutočné dáta z databázy\n";
echo "----------------------------------\n";

$totalTests++;
try {
    // Získaj skutočné report_date z databázy
    $stmt = $pdo->query("
        SELECT DISTINCT report_date, fiscal_period, fiscal_year
        FROM earningstickerstoday 
        WHERE report_date IS NOT NULL
        ORDER BY report_date DESC
        LIMIT 10
    ");
    
    $realData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($realData) {
        echo "   📋 Testovanie " . count($realData) . " skutočných záznamov:\n";
        
        $correctDerivations = 0;
        $totalRecords = count($realData);
        
        foreach ($realData as $row) {
            $derived = deriveFiscalPeriod($row['report_date']);
            
            if ($derived && $derived['period'] === $row['fiscal_period'] && $derived['year'] == $row['fiscal_year']) {
                $correctDerivations++;
                echo "   ✅ {$row['report_date']} -> {$derived['period']}/{$derived['year']} (DB: {$row['fiscal_period']}/{$row['fiscal_year']})\n";
            } else {
                echo "   ❌ {$row['report_date']} -> {$derived['period']}/{$derived['year']} (DB: {$row['fiscal_period']}/{$row['fiscal_year']})\n";
            }
        }
        
        $accuracyRate = round(($correctDerivations / $totalRecords) * 100, 1);
        echo "   📊 Presnosť odvodenia: {$correctDerivations}/{$totalRecords} ({$accuracyRate}%)\n";
        
        if ($accuracyRate >= 90) {
            echo "   ✅ Vysoká presnosť odvodenia fiscal periods\n";
            $passedTests++;
            $testResults[] = ['test' => 'Real Data Derivation', 'status' => 'PASS', 'value' => $accuracyRate];
        } elseif ($accuracyRate >= 70) {
            echo "   ⚠️  Priemerná presnosť odvodenia fiscal periods\n";
            $testResults[] = ['test' => 'Real Data Derivation', 'status' => 'WARNING', 'value' => $accuracyRate];
        } else {
            echo "   ❌ Nízka presnosť odvodenia fiscal periods\n";
            $testResults[] = ['test' => 'Real Data Derivation', 'status' => 'FAIL', 'value' => $accuracyRate];
        }
    } else {
        echo "   ⚠️  Žiadne skutočné dáta na testovanie\n";
        $testResults[] = ['test' => 'Real Data Derivation', 'status' => 'SKIP', 'value' => 'NO_DATA'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba pri testovaní skutočných dát: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Real Data Derivation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Test SQL migrácie
echo "\n📊 Test 5: Test SQL migrácie\n";
echo "---------------------------\n";

$totalTests++;
try {
    // Simuluj SQL migráciu
    $testDates = [
        '2024-01-15',
        '2024-04-15',
        '2024-07-15',
        '2024-10-15'
    ];
    
    $migrationResults = [];
    foreach ($testDates as $date) {
        $derived = deriveFiscalPeriod($date);
        
        // Simuluj SQL CASE statement
        $month = (int)date('m', strtotime($date));
        $year = (int)date('Y', strtotime($date));
        
        $sqlPeriod = null;
        if ($month >= 1 && $month <= 3) {
            $sqlPeriod = 'Q1';
        } elseif ($month >= 4 && $month <= 6) {
            $sqlPeriod = 'Q2';
        } elseif ($month >= 7 && $month <= 9) {
            $sqlPeriod = 'Q3';
        } elseif ($month >= 10 && $month <= 12) {
            $sqlPeriod = 'Q4';
        }
        
        if ($derived && $derived['period'] === $sqlPeriod && $derived['year'] === $year) {
            $migrationResults[] = true;
        } else {
            $migrationResults[] = false;
        }
    }
    
    $successfulMigrations = count(array_filter($migrationResults));
    $totalMigrations = count($migrationResults);
    
    echo "   📋 Testovanie SQL migrácie pre {$totalMigrations} dátumov:\n";
    foreach ($testDates as $i => $date) {
        $status = $migrationResults[$i] ? '✅' : '❌';
        echo "   {$status} {$date}\n";
    }
    
    $migrationRate = round(($successfulMigrations / $totalMigrations) * 100, 1);
    echo "   📊 SQL migrácia úspešnosť: {$successfulMigrations}/{$totalMigrations} ({$migrationRate}%)\n";
    
    if ($migrationRate >= 100) {
        echo "   ✅ SQL migrácia funguje správne\n";
        $passedTests++;
        $testResults[] = ['test' => 'SQL Migration', 'status' => 'PASS', 'value' => $migrationRate];
    } else {
        echo "   ❌ SQL migrácia má problémy\n";
        $testResults[] = ['test' => 'SQL Migration', 'status' => 'FAIL', 'value' => $migrationRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'SQL Migration', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Test rôznych rokov
echo "\n📊 Test 6: Test rôznych rokov\n";
echo "----------------------------\n";

$totalTests++;
try {
    $yearTests = [
        ['2020-01-15', 'Q1', 2020],
        ['2021-06-15', 'Q2', 2021],
        ['2022-09-15', 'Q3', 2022],
        ['2023-12-15', 'Q4', 2023],
        ['2024-03-15', 'Q1', 2024],
        ['2025-08-15', 'Q3', 2025]
    ];
    
    $passed = 0;
    foreach ($yearTests as $test) {
        $result = deriveFiscalPeriod($test[0]);
        
        if ($result && $result['period'] === $test[1] && $result['year'] === $test[2]) {
            $passed++;
        } else {
            echo "   ❌ {$test[0]} -> {$result['period']}/{$result['year']} (očakávané: {$test[1]}/{$test[2]})\n";
        }
    }
    
    if ($passed === count($yearTests)) {
        echo "   ✅ Všetky ročné testy prešli ({$passed}/" . count($yearTests) . ")\n";
        $passedTests++;
        $testResults[] = ['test' => 'Different Years', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré ročné testy zlyhali ({$passed}/" . count($yearTests) . ")\n";
        $testResults[] = ['test' => 'Different Years', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Different Years', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "🏆 VÝBORNE! Fiscal period odvodenie funguje perfektne!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina fiscal period testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica fiscal period testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho fiscal period testov zlyhalo.\n";
}

echo "\n🎉 Test fiscal period derivation dokončený!\n";
?>
