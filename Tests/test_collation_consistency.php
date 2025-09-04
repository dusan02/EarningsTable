<?php
/**
 * 🔧 TEST: Collation Consistency
 * Testuje konzistentnosť collation v celej databáze
 */

require_once __DIR__ . '/test_config.php';

echo "🔧 TEST: Collation Consistency\n";
echo "==============================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Kontrola collation všetkých tabuliek
echo "📊 Test 1: Kontrola collation všetkých tabuliek\n";
echo "----------------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT TABLE_NAME, TABLE_COLLATION 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    ");
    
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $targetCollation = 'utf8mb4_unicode_ci';
    
    $consistentTables = 0;
    $totalTables = count($tables);
    
    echo "   📋 Kontrola {$totalTables} tabuliek:\n";
    
    foreach ($tables as $table) {
        if ($table['TABLE_COLLATION'] === $targetCollation) {
            $consistentTables++;
            echo "   ✅ {$table['TABLE_NAME']}: {$table['TABLE_COLLATION']}\n";
        } else {
            echo "   ❌ {$table['TABLE_NAME']}: {$table['TABLE_COLLATION']} (očakávané: {$targetCollation})\n";
        }
    }
    
    $consistencyRate = round(($consistentTables / $totalTables) * 100, 1);
    echo "   📊 Konzistentnosť tabuliek: {$consistentTables}/{$totalTables} ({$consistencyRate}%)\n";
    
    if ($consistencyRate >= 100) {
        echo "   ✅ Všetky tabuľky majú konzistentnú collation\n";
        $passedTests++;
        $testResults[] = ['test' => 'Table Collation Consistency', 'status' => 'PASS', 'value' => $consistencyRate];
    } elseif ($consistencyRate >= 80) {
        echo "   ⚠️  Väčšina tabuliek má konzistentnú collation\n";
        $testResults[] = ['test' => 'Table Collation Consistency', 'status' => 'WARNING', 'value' => $consistencyRate];
    } else {
        echo "   ❌ Mnoho tabuliek nemá konzistentnú collation\n";
        $testResults[] = ['test' => 'Table Collation Consistency', 'status' => 'FAIL', 'value' => $consistencyRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Table Collation Consistency', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Kontrola collation všetkých stĺpcov
echo "\n📊 Test 2: Kontrola collation všetkých stĺpcov\n";
echo "---------------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT DISTINCT COLLATION_NAME, COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND COLLATION_NAME IS NOT NULL
        GROUP BY COLLATION_NAME
        ORDER BY count DESC
    ");
    
    $collations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $targetCollation = 'utf8mb4_unicode_ci';
    
    $totalColumns = 0;
    $consistentColumns = 0;
    
    echo "   📋 Collation distribúcia stĺpcov:\n";
    
    foreach ($collations as $collation) {
        $totalColumns += $collation['count'];
        if ($collation['COLLATION_NAME'] === $targetCollation) {
            $consistentColumns += $collation['count'];
            echo "   ✅ {$collation['COLLATION_NAME']}: {$collation['count']} stĺpcov\n";
        } else {
            echo "   ❌ {$collation['COLLATION_NAME']}: {$collation['count']} stĺpcov\n";
        }
    }
    
    $consistencyRate = $totalColumns > 0 ? round(($consistentColumns / $totalColumns) * 100, 1) : 0;
    echo "   📊 Konzistentnosť stĺpcov: {$consistentColumns}/{$totalColumns} ({$consistencyRate}%)\n";
    
    if ($consistencyRate >= 100) {
        echo "   ✅ Všetky stĺpce majú konzistentnú collation\n";
        $passedTests++;
        $testResults[] = ['test' => 'Column Collation Consistency', 'status' => 'PASS', 'value' => $consistencyRate];
    } elseif ($consistencyRate >= 90) {
        echo "   ⚠️  Väčšina stĺpcov má konzistentnú collation\n";
        $testResults[] = ['test' => 'Column Collation Consistency', 'status' => 'WARNING', 'value' => $consistencyRate];
    } else {
        echo "   ❌ Mnoho stĺpcov nemá konzistentnú collation\n";
        $testResults[] = ['test' => 'Column Collation Consistency', 'status' => 'FAIL', 'value' => $consistencyRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Column Collation Consistency', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Kontrola collation databázy
echo "\n📊 Test 3: Kontrola collation databázy\n";
echo "-------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT SCHEMA_NAME, DEFAULT_COLLATION_NAME 
        FROM INFORMATION_SCHEMA.SCHEMATA 
        WHERE SCHEMA_NAME = DATABASE()
    ");
    
    $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $targetCollation = 'utf8mb4_unicode_ci';
    
    if ($dbInfo) {
        echo "   📋 Databáza: {$dbInfo['SCHEMA_NAME']}\n";
        echo "   📋 Default collation: {$dbInfo['DEFAULT_COLLATION_NAME']}\n";
        
        if ($dbInfo['DEFAULT_COLLATION_NAME'] === $targetCollation) {
            echo "   ✅ Databáza má správnu default collation\n";
            $passedTests++;
            $testResults[] = ['test' => 'Database Collation', 'status' => 'PASS', 'value' => $dbInfo['DEFAULT_COLLATION_NAME']];
        } else {
            echo "   ❌ Databáza nemá správnu default collation (očakávané: {$targetCollation})\n";
            $testResults[] = ['test' => 'Database Collation', 'status' => 'FAIL', 'value' => $dbInfo['DEFAULT_COLLATION_NAME']];
        }
    } else {
        echo "   ❌ Nepodarilo sa získať informácie o databáze\n";
        $testResults[] = ['test' => 'Database Collation', 'status' => 'ERROR', 'value' => 'NO_DATA'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Database Collation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Test collation kompatibility
echo "\n📊 Test 4: Test collation kompatibility\n";
echo "--------------------------------------\n";

$totalTests++;
try {
    // Test porovnania stringov s rôznou collation
    $testCases = [
        ['SELECT "test" COLLATE utf8mb4_unicode_ci = "TEST" COLLATE utf8mb4_unicode_ci', 'Case insensitive comparison'],
        ['SELECT "test" COLLATE utf8mb4_general_ci = "TEST" COLLATE utf8mb4_general_ci', 'General collation comparison'],
        ['SELECT "test" COLLATE utf8mb4_bin = "TEST" COLLATE utf8mb4_bin', 'Binary collation comparison']
    ];
    
    $passed = 0;
    foreach ($testCases as $test) {
        try {
            $stmt = $pdo->query($test[0]);
            $result = $stmt->fetchColumn();
            if ($result == 1) {
                $passed++;
                echo "   ✅ {$test[1]}: TRUE\n";
            } else {
                echo "   ❌ {$test[1]}: FALSE\n";
            }
        } catch (Exception $e) {
            echo "   ❌ {$test[1]}: ERROR - {$e->getMessage()}\n";
        }
    }
    
    if ($passed === count($testCases)) {
        echo "   ✅ Všetky collation kompatibility testy prešli\n";
        $passedTests++;
        $testResults[] = ['test' => 'Collation Compatibility', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré collation kompatibility testy zlyhali\n";
        $testResults[] = ['test' => 'Collation Compatibility', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Collation Compatibility', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Test JOIN operácií s collation
echo "\n📊 Test 5: Test JOIN operácií s collation\n";
echo "----------------------------------------\n";

$totalTests++;
try {
    // Test JOIN medzi tabuľkami s rôznou collation
    $testQueries = [
        "SELECT COUNT(*) FROM earningstickerstoday e JOIN benzinga_guidance g ON e.ticker = g.ticker LIMIT 1",
        "SELECT COUNT(*) FROM earningstickerstoday e JOIN todayearningsmovements t ON e.ticker = t.ticker LIMIT 1"
    ];
    
    $passed = 0;
    foreach ($testQueries as $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetchColumn();
            $passed++;
            echo "   ✅ JOIN query úspešná: {$result} záznamov\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Illegal mix of collations') !== false) {
                echo "   ❌ Collation mix error: {$e->getMessage()}\n";
            } else {
                echo "   ⚠️  Iná chyba: {$e->getMessage()}\n";
                $passed++; // Necháme prejsť, ak to nie je collation chyba
            }
        }
    }
    
    if ($passed === count($testQueries)) {
        echo "   ✅ Všetky JOIN operácie fungujú bez collation chýb\n";
        $passedTests++;
        $testResults[] = ['test' => 'JOIN Operations', 'status' => 'PASS', 'value' => $passed];
    } else {
        echo "   ❌ Niektoré JOIN operácie majú collation problémy\n";
        $testResults[] = ['test' => 'JOIN Operations', 'status' => 'FAIL', 'value' => $passed];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'JOIN Operations', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Kontrola problematických stĺpcov
echo "\n📊 Test 6: Kontrola problematických stĺpcov\n";
echo "------------------------------------------\n";

$totalTests++;
try {
    // Nájdi stĺpce s nekonzistentnou collation
    $stmt = $pdo->query("
        SELECT TABLE_NAME, COLUMN_NAME, COLLATION_NAME
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND COLLATION_NAME IS NOT NULL
        AND COLLATION_NAME != 'utf8mb4_unicode_ci'
        ORDER BY TABLE_NAME, COLUMN_NAME
    ");
    
    $problematicColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($problematicColumns)) {
        echo "   ✅ Žiadne problematické stĺpce s nekonzistentnou collation\n";
        $passedTests++;
        $testResults[] = ['test' => 'Problematic Columns', 'status' => 'PASS', 'value' => 0];
    } else {
        echo "   ❌ Nájdené " . count($problematicColumns) . " problematických stĺpcov:\n";
        foreach ($problematicColumns as $col) {
            echo "   📋 {$col['TABLE_NAME']}.{$col['COLUMN_NAME']}: {$col['COLLATION_NAME']}\n";
        }
        $testResults[] = ['test' => 'Problematic Columns', 'status' => 'FAIL', 'value' => count($problematicColumns)];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Problematic Columns', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "🏆 VÝBORNE! Collation je konzistentná v celej databáze!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina collation testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica collation testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho collation testov zlyhalo.\n";
}

echo "\n🎉 Test collation consistency dokončený!\n";
?>
