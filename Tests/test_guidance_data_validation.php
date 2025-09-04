<?php
/**
 * 📊 TEST: Guidance Data Validation
 * Testuje validáciu guidance dát
 */

require_once __DIR__ . '/test_config.php';

echo "📊 TEST: Guidance Data Validation\n";
echo "=================================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Kontrola integrity guidance dát
echo "📊 Test 1: Kontrola integrity guidance dát\n";
echo "------------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_records,
            COUNT(DISTINCT ticker) as unique_tickers,
            COUNT(CASE WHEN estimated_eps_guidance IS NOT NULL THEN 1 END) as eps_guidance_count,
            COUNT(CASE WHEN estimated_revenue_guidance IS NOT NULL THEN 1 END) as revenue_guidance_count,
            COUNT(CASE WHEN fiscal_period IS NOT NULL THEN 1 END) as period_count,
            COUNT(CASE WHEN fiscal_year IS NOT NULL THEN 1 END) as year_count
        FROM benzinga_guidance
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📋 Guidance data štatistiky:\n";
    echo "   📊 Celkovo záznamov: {$stats['total_records']}\n";
    echo "   📊 Unikátnych tickerov: {$stats['unique_tickers']}\n";
    echo "   📊 EPS guidance záznamov: {$stats['eps_guidance_count']}\n";
    echo "   📊 Revenue guidance záznamov: {$stats['revenue_guidance_count']}\n";
    echo "   📊 Fiscal period záznamov: {$stats['period_count']}\n";
    echo "   📊 Fiscal year záznamov: {$stats['year_count']}\n";
    
    $integrityScore = 0;
    $totalChecks = 6;
    
    // Kontrola, či máme dáta
    if ($stats['total_records'] > 0) $integrityScore++;
    if ($stats['unique_tickers'] > 0) $integrityScore++;
    if ($stats['eps_guidance_count'] > 0) $integrityScore++;
    if ($stats['revenue_guidance_count'] > 0) $integrityScore++;
    if ($stats['period_count'] > 0) $integrityScore++;
    if ($stats['year_count'] > 0) $integrityScore++;
    
    $integrityRate = round(($integrityScore / $totalChecks) * 100, 1);
    echo "   📊 Integrity score: {$integrityScore}/{$totalChecks} ({$integrityRate}%)\n";
    
    if ($integrityRate >= 90) {
        echo "   ✅ Vysoká integrita guidance dát\n";
        $passedTests++;
        $testResults[] = ['test' => 'Guidance Data Integrity', 'status' => 'PASS', 'value' => $integrityRate];
    } elseif ($integrityRate >= 70) {
        echo "   ⚠️  Priemerná integrita guidance dát\n";
        $testResults[] = ['test' => 'Guidance Data Integrity', 'status' => 'WARNING', 'value' => $integrityRate];
    } else {
        echo "   ❌ Nízka integrita guidance dát\n";
        $testResults[] = ['test' => 'Guidance Data Integrity', 'status' => 'FAIL', 'value' => $integrityRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Guidance Data Integrity', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Validácia fiscal period formátov
echo "\n📊 Test 2: Validácia fiscal period formátov\n";
echo "------------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT DISTINCT fiscal_period, COUNT(*) as count
        FROM benzinga_guidance 
        WHERE fiscal_period IS NOT NULL
        GROUP BY fiscal_period
        ORDER BY count DESC
    ");
    
    $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $validPeriods = ['Q1', 'Q2', 'Q3', 'Q4', 'FY', 'H1', 'H2'];
    
    $validPeriodCount = 0;
    $totalPeriodRecords = 0;
    $invalidPeriods = [];
    
    echo "   📋 Fiscal period distribúcia:\n";
    
    foreach ($periods as $period) {
        $totalPeriodRecords += $period['count'];
        if (in_array($period['fiscal_period'], $validPeriods)) {
            $validPeriodCount += $period['count'];
            echo "   ✅ {$period['fiscal_period']}: {$period['count']} záznamov\n";
        } else {
            $invalidPeriods[] = $period['fiscal_period'];
            echo "   ❌ {$period['fiscal_period']}: {$period['count']} záznamov (neplatný)\n";
        }
    }
    
    $validityRate = $totalPeriodRecords > 0 ? round(($validPeriodCount / $totalPeriodRecords) * 100, 1) : 0;
    echo "   📊 Validity rate: {$validPeriodCount}/{$totalPeriodRecords} ({$validityRate}%)\n";
    
    if (!empty($invalidPeriods)) {
        echo "   ⚠️  Neplatné periody: " . implode(', ', $invalidPeriods) . "\n";
    }
    
    if ($validityRate >= 95) {
        echo "   ✅ Vysoká validita fiscal period formátov\n";
        $passedTests++;
        $testResults[] = ['test' => 'Fiscal Period Validation', 'status' => 'PASS', 'value' => $validityRate];
    } elseif ($validityRate >= 80) {
        echo "   ⚠️  Priemerná validita fiscal period formátov\n";
        $testResults[] = ['test' => 'Fiscal Period Validation', 'status' => 'WARNING', 'value' => $validityRate];
    } else {
        echo "   ❌ Nízka validita fiscal period formátov\n";
        $testResults[] = ['test' => 'Fiscal Period Validation', 'status' => 'FAIL', 'value' => $validityRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Fiscal Period Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Validácia fiscal year rozsahu
echo "\n📊 Test 3: Validácia fiscal year rozsahu\n";
echo "---------------------------------------\n";

$totalTests++;
try {
    $currentYear = (int)date('Y');
    $minYear = $currentYear - 2;
    $maxYear = $currentYear + 3;
    
    $stmt = $pdo->query("
        SELECT 
            MIN(fiscal_year) as min_year,
            MAX(fiscal_year) as max_year,
            COUNT(CASE WHEN fiscal_year < {$minYear} OR fiscal_year > {$maxYear} THEN 1 END) as invalid_years,
            COUNT(CASE WHEN fiscal_year IS NOT NULL THEN 1 END) as total_years
        FROM benzinga_guidance
    ");
    
    $yearStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📋 Fiscal year štatistiky:\n";
    echo "   📊 Min year: {$yearStats['min_year']}\n";
    echo "   📊 Max year: {$yearStats['max_year']}\n";
    echo "   📊 Invalid years: {$yearStats['invalid_years']}\n";
    echo "   📊 Total years: {$yearStats['total_years']}\n";
    echo "   📊 Valid range: {$minYear} - {$maxYear}\n";
    
    $validYearRate = $yearStats['total_years'] > 0 ? 
        round((($yearStats['total_years'] - $yearStats['invalid_years']) / $yearStats['total_years']) * 100, 1) : 0;
    
    echo "   📊 Validity rate: {$validYearRate}%\n";
    
    if ($validYearRate >= 95) {
        echo "   ✅ Vysoká validita fiscal year rozsahu\n";
        $passedTests++;
        $testResults[] = ['test' => 'Fiscal Year Validation', 'status' => 'PASS', 'value' => $validYearRate];
    } elseif ($validYearRate >= 80) {
        echo "   ⚠️  Priemerná validita fiscal year rozsahu\n";
        $testResults[] = ['test' => 'Fiscal Year Validation', 'status' => 'WARNING', 'value' => $validYearRate];
    } else {
        echo "   ❌ Nízka validita fiscal year rozsahu\n";
        $testResults[] = ['test' => 'Fiscal Year Validation', 'status' => 'FAIL', 'value' => $validYearRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Fiscal Year Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Validácia numerických hodnôt
echo "\n📊 Test 4: Validácia numerických hodnôt\n";
echo "--------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN estimated_eps_guidance IS NOT NULL AND estimated_eps_guidance != '' THEN 1 END) as eps_count,
            COUNT(CASE WHEN estimated_revenue_guidance IS NOT NULL AND estimated_revenue_guidance != '' THEN 1 END) as revenue_count,
            COUNT(CASE WHEN eps_guide_vs_consensus_pct IS NOT NULL THEN 1 END) as eps_surprise_count,
            COUNT(CASE WHEN revenue_guide_vs_consensus_pct IS NOT NULL THEN 1 END) as revenue_surprise_count
        FROM benzinga_guidance
    ");
    
    $numericStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📋 Numerické hodnoty štatistiky:\n";
    echo "   📊 EPS guidance: {$numericStats['eps_count']}\n";
    echo "   📊 Revenue guidance: {$numericStats['revenue_count']}\n";
    echo "   📊 EPS surprise: {$numericStats['eps_surprise_count']}\n";
    echo "   📊 Revenue surprise: {$numericStats['revenue_surprise_count']}\n";
    
    // Test extreme values
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN ABS(eps_guide_vs_consensus_pct) > 1000 THEN 1 END) as extreme_eps,
            COUNT(CASE WHEN ABS(revenue_guide_vs_consensus_pct) > 1000 THEN 1 END) as extreme_revenue
        FROM benzinga_guidance
    ");
    
    $extremeStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📊 Extreme EPS values (>1000%): {$extremeStats['extreme_eps']}\n";
    echo "   📊 Extreme Revenue values (>1000%): {$extremeStats['extreme_revenue']}\n";
    
    $validationScore = 0;
    $totalChecks = 4;
    
    if ($numericStats['eps_count'] > 0) $validationScore++;
    if ($numericStats['revenue_count'] > 0) $validationScore++;
    if ($numericStats['eps_surprise_count'] > 0) $validationScore++;
    if ($numericStats['revenue_surprise_count'] > 0) $validationScore++;
    
    $validationRate = round(($validationScore / $totalChecks) * 100, 1);
    echo "   📊 Validation score: {$validationScore}/{$totalChecks} ({$validationRate}%)\n";
    
    if ($validationRate >= 75) {
        echo "   ✅ Dobrá validácia numerických hodnôt\n";
        $passedTests++;
        $testResults[] = ['test' => 'Numeric Values Validation', 'status' => 'PASS', 'value' => $validationRate];
    } else {
        echo "   ❌ Slabá validácia numerických hodnôt\n";
        $testResults[] = ['test' => 'Numeric Values Validation', 'status' => 'FAIL', 'value' => $validationRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Numeric Values Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Validácia duplicitných záznamov
echo "\n📊 Test 5: Validácia duplicitných záznamov\n";
echo "-----------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            ticker, fiscal_period, fiscal_year, COUNT(*) as duplicate_count
        FROM benzinga_guidance 
        WHERE ticker IS NOT NULL 
        AND fiscal_period IS NOT NULL 
        AND fiscal_year IS NOT NULL
        GROUP BY ticker, fiscal_period, fiscal_year
        HAVING COUNT(*) > 1
        ORDER BY duplicate_count DESC
        LIMIT 10
    ");
    
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "   ✅ Žiadne duplicitné záznamy\n";
        $passedTests++;
        $testResults[] = ['test' => 'Duplicate Records Validation', 'status' => 'PASS', 'value' => 0];
    } else {
        echo "   ⚠️  Nájdené duplicitné záznamy:\n";
        $totalDuplicates = 0;
        foreach ($duplicates as $dup) {
            echo "   📋 {$dup['ticker']} ({$dup['fiscal_period']}/{$dup['fiscal_year']}): {$dup['duplicate_count']} záznamov\n";
            $totalDuplicates += $dup['duplicate_count'] - 1; // -1 pretože jeden je legitímny
        }
        
        if ($totalDuplicates <= 10) {
            echo "   ⚠️  Prijateľný počet duplicít: {$totalDuplicates}\n";
            $testResults[] = ['test' => 'Duplicate Records Validation', 'status' => 'WARNING', 'value' => $totalDuplicates];
        } else {
            echo "   ❌ Príliš veľa duplicít: {$totalDuplicates}\n";
            $testResults[] = ['test' => 'Duplicate Records Validation', 'status' => 'FAIL', 'value' => $totalDuplicates];
        }
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Duplicate Records Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Validácia timestamp integrity
echo "\n📊 Test 6: Validácia timestamp integrity\n";
echo "---------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_records,
            COUNT(CASE WHEN last_updated IS NOT NULL THEN 1 END) as updated_count,
            MIN(last_updated) as earliest_update,
            MAX(last_updated) as latest_update
        FROM benzinga_guidance
    ");
    
    $timestampStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📋 Timestamp štatistiky:\n";
    echo "   📊 Celkovo záznamov: {$timestampStats['total_records']}\n";
    echo "   📊 Záznamov s timestamp: {$timestampStats['updated_count']}\n";
    echo "   📊 Najstarší update: {$timestampStats['earliest_update']}\n";
    echo "   📊 Najnovší update: {$timestampStats['latest_update']}\n";
    
    $timestampRate = $timestampStats['total_records'] > 0 ? 
        round(($timestampStats['updated_count'] / $timestampStats['total_records']) * 100, 1) : 0;
    
    echo "   📊 Timestamp coverage: {$timestampRate}%\n";
    
    // Kontrola, či sú timestamps rozumné
    $earliestDate = new DateTime($timestampStats['earliest_update']);
    $latestDate = new DateTime($timestampStats['latest_update']);
    $now = new DateTime();
    
    $daysSinceEarliest = $now->diff($earliestDate)->days;
    $daysSinceLatest = $now->diff($latestDate)->days;
    
    echo "   📊 Dní od najstaršieho: {$daysSinceEarliest}\n";
    echo "   📊 Dní od najnovšieho: {$daysSinceLatest}\n";
    
    if ($timestampRate >= 90 && $daysSinceLatest <= 30) {
        echo "   ✅ Dobrá timestamp integrity\n";
        $passedTests++;
        $testResults[] = ['test' => 'Timestamp Integrity', 'status' => 'PASS', 'value' => $timestampRate];
    } elseif ($timestampRate >= 70 && $daysSinceLatest <= 90) {
        echo "   ⚠️  Priemerná timestamp integrity\n";
        $testResults[] = ['test' => 'Timestamp Integrity', 'status' => 'WARNING', 'value' => $timestampRate];
    } else {
        echo "   ❌ Slabá timestamp integrity\n";
        $testResults[] = ['test' => 'Timestamp Integrity', 'status' => 'FAIL', 'value' => $timestampRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Timestamp Integrity', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "🏆 VÝBORNE! Guidance data validation je výborná!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina guidance validation testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica guidance validation testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho guidance validation testov zlyhalo.\n";
}

echo "\n🎉 Test guidance data validation dokončený!\n";
?>
