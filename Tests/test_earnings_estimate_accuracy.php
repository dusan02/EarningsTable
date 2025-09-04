<?php
/**
 * 📊 TEST: Earnings Estimate Accuracy
 * Testuje presnosť earnings estimates
 */

require_once __DIR__ . '/test_config.php';

echo "📊 TEST: Earnings Estimate Accuracy\n";
echo "===================================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Kontrola integrity earnings estimates
echo "📊 Test 1: Kontrola integrity earnings estimates\n";
echo "-----------------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_records,
            COUNT(CASE WHEN eps_estimate IS NOT NULL AND eps_estimate != '' THEN 1 END) as eps_estimate_count,
            COUNT(CASE WHEN revenue_estimate IS NOT NULL AND revenue_estimate != '' THEN 1 END) as revenue_estimate_count,
            COUNT(CASE WHEN eps_actual IS NOT NULL AND eps_actual != '' THEN 1 END) as eps_actual_count,
            COUNT(CASE WHEN revenue_actual IS NOT NULL AND revenue_actual != '' THEN 1 END) as revenue_actual_count
        FROM earningstickerstoday
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📋 Earnings estimates štatistiky:\n";
    echo "   📊 Celkovo záznamov: {$stats['total_records']}\n";
    echo "   📊 EPS estimates: {$stats['eps_estimate_count']}\n";
    echo "   📊 Revenue estimates: {$stats['revenue_estimate_count']}\n";
    echo "   📊 EPS actuals: {$stats['eps_actual_count']}\n";
    echo "   📊 Revenue actuals: {$stats['revenue_actual_count']}\n";
    
    $integrityScore = 0;
    $totalChecks = 5;
    
    if ($stats['total_records'] > 0) $integrityScore++;
    if ($stats['eps_estimate_count'] > 0) $integrityScore++;
    if ($stats['revenue_estimate_count'] > 0) $integrityScore++;
    if ($stats['eps_actual_count'] > 0) $integrityScore++;
    if ($stats['revenue_actual_count'] > 0) $integrityScore++;
    
    $integrityRate = round(($integrityScore / $totalChecks) * 100, 1);
    echo "   📊 Integrity score: {$integrityScore}/{$totalChecks} ({$integrityRate}%)\n";
    
    if ($integrityRate >= 80) {
        echo "   ✅ Dobrá integrita earnings estimates\n";
        $passedTests++;
        $testResults[] = ['test' => 'Earnings Estimates Integrity', 'status' => 'PASS', 'value' => $integrityRate];
    } elseif ($integrityRate >= 60) {
        echo "   ⚠️  Priemerná integrita earnings estimates\n";
        $testResults[] = ['test' => 'Earnings Estimates Integrity', 'status' => 'WARNING', 'value' => $integrityRate];
    } else {
        echo "   ❌ Slabá integrita earnings estimates\n";
        $testResults[] = ['test' => 'Earnings Estimates Integrity', 'status' => 'FAIL', 'value' => $integrityRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Earnings Estimates Integrity', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Validácia numerických formátov estimates
echo "\n📊 Test 2: Validácia numerických formátov estimates\n";
echo "---------------------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            ticker,
            eps_estimate,
            revenue_estimate,
            eps_actual,
            revenue_actual
        FROM earningstickerstoday 
        WHERE (eps_estimate IS NOT NULL AND eps_estimate != '') 
        OR (revenue_estimate IS NOT NULL AND revenue_estimate != '')
        LIMIT 10
    ");
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $validNumericCount = 0;
    $totalNumericFields = 0;
    $invalidFormats = [];
    
    echo "   📋 Kontrola numerických formátov pre " . count($records) . " záznamov:\n";
    
    foreach ($records as $record) {
        $fields = ['eps_estimate', 'revenue_estimate', 'eps_actual', 'revenue_actual'];
        
        foreach ($fields as $field) {
            if (!empty($record[$field])) {
                $totalNumericFields++;
                
                // Kontrola, či je to platné číslo
                if (is_numeric($record[$field])) {
                    $validNumericCount++;
                } else {
                    $invalidFormats[] = "{$record['ticker']}.{$field}: '{$record[$field]}'";
                }
            }
        }
    }
    
    $validityRate = $totalNumericFields > 0 ? round(($validNumericCount / $totalNumericFields) * 100, 1) : 0;
    echo "   📊 Validity rate: {$validNumericCount}/{$totalNumericFields} ({$validityRate}%)\n";
    
    if (!empty($invalidFormats)) {
        echo "   ⚠️  Neplatné formáty (prvé 5):\n";
        foreach (array_slice($invalidFormats, 0, 5) as $invalid) {
            echo "   📋 {$invalid}\n";
        }
    }
    
    if ($validityRate >= 95) {
        echo "   ✅ Vysoká validita numerických formátov\n";
        $passedTests++;
        $testResults[] = ['test' => 'Numeric Format Validation', 'status' => 'PASS', 'value' => $validityRate];
    } elseif ($validityRate >= 85) {
        echo "   ⚠️  Priemerná validita numerických formátov\n";
        $testResults[] = ['test' => 'Numeric Format Validation', 'status' => 'WARNING', 'value' => $validityRate];
    } else {
        echo "   ❌ Nízka validita numerických formátov\n";
        $testResults[] = ['test' => 'Numeric Format Validation', 'status' => 'FAIL', 'value' => $validityRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Numeric Format Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Analýza accuracy estimates vs actuals
echo "\n📊 Test 3: Analýza accuracy estimates vs actuals\n";
echo "-----------------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            ticker,
            eps_estimate,
            eps_actual,
            revenue_estimate,
            revenue_actual,
            report_date
        FROM earningstickerstoday 
        WHERE eps_estimate IS NOT NULL 
        AND eps_actual IS NOT NULL 
        AND eps_estimate != '' 
        AND eps_actual != ''
        AND eps_estimate != 0
        LIMIT 20
    ");
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records) > 0) {
        $totalAccuracy = 0;
        $validComparisons = 0;
        $extremeErrors = 0;
        
        echo "   📋 Analýza accuracy pre " . count($records) . " záznamov:\n";
        
        foreach ($records as $record) {
            $epsEstimate = (float)$record['eps_estimate'];
            $epsActual = (float)$record['eps_actual'];
            
            if ($epsEstimate != 0) {
                $accuracy = abs(($epsActual - $epsEstimate) / $epsEstimate) * 100;
                $totalAccuracy += $accuracy;
                $validComparisons++;
                
                if ($accuracy > 50) { // >50% error
                    $extremeErrors++;
                }
                
                echo "   📊 {$record['ticker']}: Estimate={$epsEstimate}, Actual={$epsActual}, Error=" . round($accuracy, 1) . "%\n";
            }
        }
        
        if ($validComparisons > 0) {
            $averageAccuracy = round($totalAccuracy / $validComparisons, 1);
            $extremeErrorRate = round(($extremeErrors / $validComparisons) * 100, 1);
            
            echo "   📊 Priemerná chyba: {$averageAccuracy}%\n";
            echo "   📊 Extreme errors (>50%): {$extremeErrors}/{$validComparisons} ({$extremeErrorRate}%)\n";
            
            if ($averageAccuracy <= 20 && $extremeErrorRate <= 20) {
                echo "   ✅ Vysoká accuracy estimates\n";
                $passedTests++;
                $testResults[] = ['test' => 'Estimates vs Actuals Accuracy', 'status' => 'PASS', 'value' => $averageAccuracy];
            } elseif ($averageAccuracy <= 40 && $extremeErrorRate <= 40) {
                echo "   ⚠️  Priemerná accuracy estimates\n";
                $testResults[] = ['test' => 'Estimates vs Actuals Accuracy', 'status' => 'WARNING', 'value' => $averageAccuracy];
            } else {
                echo "   ❌ Nízka accuracy estimates\n";
                $testResults[] = ['test' => 'Estimates vs Actuals Accuracy', 'status' => 'FAIL', 'value' => $averageAccuracy];
            }
        } else {
            echo "   ⚠️  Žiadne platné porovnania estimates vs actuals\n";
            $testResults[] = ['test' => 'Estimates vs Actuals Accuracy', 'status' => 'SKIP', 'value' => 'NO_DATA'];
        }
    } else {
        echo "   ⚠️  Žiadne dáta s estimates a actuals na porovnanie\n";
        $testResults[] = ['test' => 'Estimates vs Actuals Accuracy', 'status' => 'SKIP', 'value' => 'NO_DATA'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Estimates vs Actuals Accuracy', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Kontrola rozsahu estimates
echo "\n📊 Test 4: Kontrola rozsahu estimates\n";
echo "------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            MIN(CAST(eps_estimate AS DECIMAL(10,4))) as min_eps_estimate,
            MAX(CAST(eps_estimate AS DECIMAL(10,4))) as max_eps_estimate,
            AVG(CAST(eps_estimate AS DECIMAL(10,4))) as avg_eps_estimate,
            MIN(CAST(revenue_estimate AS DECIMAL(15,2))) as min_revenue_estimate,
            MAX(CAST(revenue_estimate AS DECIMAL(15,2))) as max_revenue_estimate,
            AVG(CAST(revenue_estimate AS DECIMAL(15,2))) as avg_revenue_estimate
        FROM earningstickerstoday 
        WHERE eps_estimate IS NOT NULL 
        AND eps_estimate != '' 
        AND revenue_estimate IS NOT NULL 
        AND revenue_estimate != ''
    ");
    
    $ranges = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📋 Rozsah estimates:\n";
    echo "   📊 EPS: Min={$ranges['min_eps_estimate']}, Max={$ranges['max_eps_estimate']}, Avg=" . round($ranges['avg_eps_estimate'], 2) . "\n";
    echo "   📊 Revenue: Min={$ranges['min_revenue_estimate']}, Max={$ranges['max_revenue_estimate']}, Avg=" . round($ranges['avg_revenue_estimate'], 2) . "\n";
    
    $rangeScore = 0;
    $totalChecks = 4;
    
    // Kontrola rozumných rozsahov
    if ($ranges['min_eps_estimate'] >= -10 && $ranges['max_eps_estimate'] <= 50) $rangeScore++; // EPS rozsah
    if ($ranges['min_revenue_estimate'] >= 0 && $ranges['max_revenue_estimate'] <= 1000000) $rangeScore++; // Revenue rozsah (v miliónoch)
    if ($ranges['avg_eps_estimate'] >= -2 && $ranges['avg_eps_estimate'] <= 10) $rangeScore++; // Priemerný EPS
    if ($ranges['avg_revenue_estimate'] >= 10 && $ranges['avg_revenue_estimate'] <= 50000) $rangeScore++; // Priemerný Revenue
    
    $rangeRate = round(($rangeScore / $totalChecks) * 100, 1);
    echo "   📊 Range validity: {$rangeScore}/{$totalChecks} ({$rangeRate}%)\n";
    
    if ($rangeRate >= 75) {
        echo "   ✅ Rozumné rozsahy estimates\n";
        $passedTests++;
        $testResults[] = ['test' => 'Estimates Range Validation', 'status' => 'PASS', 'value' => $rangeRate];
    } else {
        echo "   ❌ Nerozumné rozsahy estimates\n";
        $testResults[] = ['test' => 'Estimates Range Validation', 'status' => 'FAIL', 'value' => $rangeRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Estimates Range Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Kontrola completeness estimates
echo "\n📊 Test 5: Kontrola completeness estimates\n";
echo "------------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_tickers,
            COUNT(CASE WHEN eps_estimate IS NOT NULL AND eps_estimate != '' THEN 1 END) as eps_complete,
            COUNT(CASE WHEN revenue_estimate IS NOT NULL AND revenue_estimate != '' THEN 1 END) as revenue_complete,
            COUNT(CASE WHEN eps_estimate IS NOT NULL AND eps_estimate != '' AND revenue_estimate IS NOT NULL AND revenue_estimate != '' THEN 1 END) as both_complete
        FROM earningstickerstoday
    ");
    
    $completeness = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $epsCompletenessRate = round(($completeness['eps_complete'] / $completeness['total_tickers']) * 100, 1);
    $revenueCompletenessRate = round(($completeness['revenue_complete'] / $completeness['total_tickers']) * 100, 1);
    $bothCompletenessRate = round(($completeness['both_complete'] / $completeness['total_tickers']) * 100, 1);
    
    echo "   📋 Completeness estimates:\n";
    echo "   📊 Celkovo tickerov: {$completeness['total_tickers']}\n";
    echo "   📊 EPS estimates: {$completeness['eps_complete']} ({$epsCompletenessRate}%)\n";
    echo "   📊 Revenue estimates: {$completeness['revenue_complete']} ({$revenueCompletenessRate}%)\n";
    echo "   📊 Oba estimates: {$completeness['both_complete']} ({$bothCompletenessRate}%)\n";
    
    $completenessScore = 0;
    if ($epsCompletenessRate >= 80) $completenessScore++;
    if ($revenueCompletenessRate >= 80) $completenessScore++;
    if ($bothCompletenessRate >= 70) $completenessScore++;
    
    $completenessRate = round(($completenessScore / 3) * 100, 1);
    echo "   📊 Completeness score: {$completenessScore}/3 ({$completenessRate}%)\n";
    
    if ($completenessRate >= 66) {
        echo "   ✅ Dobrá completeness estimates\n";
        $passedTests++;
        $testResults[] = ['test' => 'Estimates Completeness', 'status' => 'PASS', 'value' => $completenessRate];
    } else {
        echo "   ❌ Slabá completeness estimates\n";
        $testResults[] = ['test' => 'Estimates Completeness', 'status' => 'FAIL', 'value' => $completenessRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Estimates Completeness', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Kontrola freshness estimates
echo "\n📊 Test 6: Kontrola freshness estimates\n";
echo "---------------------------------------\n";

$totalTests++;
try {
    $stmt = $pdo->query("
        SELECT 
            MIN(report_date) as earliest_report,
            MAX(report_date) as latest_report,
            COUNT(CASE WHEN report_date >= CURDATE() - INTERVAL 7 DAY THEN 1 END) as recent_reports,
            COUNT(*) as total_reports
        FROM earningstickerstoday
    ");
    
    $freshness = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $recentRate = round(($freshness['recent_reports'] / $freshness['total_reports']) * 100, 1);
    
    echo "   📋 Freshness estimates:\n";
    echo "   📊 Najstarší report: {$freshness['earliest_report']}\n";
    echo "   📊 Najnovší report: {$freshness['latest_report']}\n";
    echo "   📊 Recent reports (7 dní): {$freshness['recent_reports']} ({$recentRate}%)\n";
    
    // Kontrola, či sú dáta aktuálne
    $latestDate = new DateTime($freshness['latest_report']);
    $now = new DateTime();
    $daysSinceLatest = $now->diff($latestDate)->days;
    
    echo "   📊 Dní od najnovšieho: {$daysSinceLatest}\n";
    
    $freshnessScore = 0;
    if ($recentRate >= 20) $freshnessScore++; // Aspoň 20% recent
    if ($daysSinceLatest <= 7) $freshnessScore++; // Najnovší do 7 dní
    if ($freshness['total_reports'] >= 10) $freshnessScore++; // Aspoň 10 záznamov
    
    $freshnessRate = round(($freshnessScore / 3) * 100, 1);
    echo "   📊 Freshness score: {$freshnessScore}/3 ({$freshnessRate}%)\n";
    
    if ($freshnessRate >= 66) {
        echo "   ✅ Dobrá freshness estimates\n";
        $passedTests++;
        $testResults[] = ['test' => 'Estimates Freshness', 'status' => 'PASS', 'value' => $freshnessRate];
    } else {
        echo "   ❌ Slabá freshness estimates\n";
        $testResults[] = ['test' => 'Estimates Freshness', 'status' => 'FAIL', 'value' => $freshnessRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Estimates Freshness', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "🏆 VÝBORNE! Earnings estimate accuracy je výborná!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina earnings estimate testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica earnings estimate testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho earnings estimate testov zlyhalo.\n";
}

echo "\n🎉 Test earnings estimate accuracy dokončený!\n";
?>
