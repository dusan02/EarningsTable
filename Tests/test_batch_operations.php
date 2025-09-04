<?php
/**
 * 📊 TEST: Batch Operations
 * Testuje batch operácie v databáze
 */

require_once __DIR__ . '/test_config.php';

echo "📊 TEST: Batch Operations\n";
echo "========================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Rýchlosť batch INSERT operácií
echo "📊 Test 1: Rýchlosť batch INSERT operácií\n";
echo "----------------------------------------\n";

$totalTests++;
try {
    // Vytvorenie testovacej tabuľky
    $pdo->exec("
        CREATE TEMPORARY TABLE test_batch_insert (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticker VARCHAR(10),
            value1 DECIMAL(10,4),
            value2 DECIMAL(15,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $batchSizes = [10, 50, 100, 500];
    $insertResults = [];
    
    echo "   📋 Testovanie rôznych batch veľkostí:\n";
    
    foreach ($batchSizes as $batchSize) {
        $startTime = microtime(true);
        
        // Batch INSERT
        $values = [];
        for ($i = 0; $i < $batchSize; $i++) {
            $values[] = "('TEST" . str_pad($i, 3, '0', STR_PAD_LEFT) . "', " . (rand(1, 100) / 10) . ", " . rand(1000000, 10000000) . ")";
        }
        
        $sql = "INSERT INTO test_batch_insert (ticker, value1, value2) VALUES " . implode(', ', $values);
        $pdo->exec($sql);
        
        $endTime = microtime(true);
        $insertTime = ($endTime - $startTime) * 1000;
        $timePerRecord = round($insertTime / $batchSize, 4);
        
        $insertResults[] = [
            'batch_size' => $batchSize,
            'total_time' => $insertTime,
            'time_per_record' => $timePerRecord
        ];
        
        echo "   📊 Batch {$batchSize}: {$insertTime}ms ({$timePerRecord}ms/record)\n";
        
        // Vyčistenie pre ďalší test
        $pdo->exec("TRUNCATE TABLE test_batch_insert");
    }
    
    // Analýza výsledkov
    $avgTimePerRecord = array_sum(array_column($insertResults, 'time_per_record')) / count($insertResults);
    
    if ($avgTimePerRecord <= 0.1) { // 0.1ms per record
        echo "   ✅ Veľmi rýchle batch INSERT operácie\n";
        $passedTests++;
        $testResults[] = ['test' => 'Batch INSERT Speed', 'status' => 'PASS', 'value' => $avgTimePerRecord];
    } elseif ($avgTimePerRecord <= 1.0) { // 1ms per record
        echo "   ✅ Rýchle batch INSERT operácie\n";
        $passedTests++;
        $testResults[] = ['test' => 'Batch INSERT Speed', 'status' => 'PASS', 'value' => $avgTimePerRecord];
    } elseif ($avgTimePerRecord <= 5.0) { // 5ms per record
        echo "   ⚠️  Priemerné batch INSERT operácie\n";
        $testResults[] = ['test' => 'Batch INSERT Speed', 'status' => 'WARNING', 'value' => $avgTimePerRecord];
    } else {
        echo "   ❌ Pomalé batch INSERT operácie\n";
        $testResults[] = ['test' => 'Batch INSERT Speed', 'status' => 'FAIL', 'value' => $avgTimePerRecord];
    }
    
    // Vyčistenie
    $pdo->exec("DROP TEMPORARY TABLE test_batch_insert");
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Batch INSERT Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Rýchlosť batch UPDATE operácií
echo "\n📊 Test 2: Rýchlosť batch UPDATE operácií\n";
echo "----------------------------------------\n";

$totalTests++;
try {
    // Vytvorenie testovacej tabuľky s dátami
    $pdo->exec("
        CREATE TEMPORARY TABLE test_batch_update (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticker VARCHAR(10),
            value1 DECIMAL(10,4),
            value2 DECIMAL(15,2),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Vloženie testovacích dát
    $values = [];
    for ($i = 0; $i < 200; $i++) {
        $values[] = "('TEST" . str_pad($i, 3, '0', STR_PAD_LEFT) . "', " . (rand(1, 100) / 10) . ", " . rand(1000000, 10000000) . ")";
    }
    $sql = "INSERT INTO test_batch_update (ticker, value1, value2) VALUES " . implode(', ', $values);
    $pdo->exec($sql);
    
    $batchSizes = [10, 25, 50, 100];
    $updateResults = [];
    
    echo "   📋 Testovanie rôznych batch UPDATE veľkostí:\n";
    
    foreach ($batchSizes as $batchSize) {
        $startTime = microtime(true);
        
        // Batch UPDATE pomocou CASE WHEN
        $caseValues1 = [];
        $caseValues2 = [];
        $ids = [];
        
        for ($i = 0; $i < $batchSize; $i++) {
            $id = $i + 1;
            $newValue1 = rand(1, 100) / 10;
            $newValue2 = rand(1000000, 10000000);
            
            $caseValues1[] = "WHEN id = {$id} THEN {$newValue1}";
            $caseValues2[] = "WHEN id = {$id} THEN {$newValue2}";
            $ids[] = $id;
        }
        
        $sql = "
            UPDATE test_batch_update 
            SET 
                value1 = CASE " . implode(' ', $caseValues1) . " END,
                value2 = CASE " . implode(' ', $caseValues2) . " END
            WHERE id IN (" . implode(',', $ids) . ")
        ";
        
        $pdo->exec($sql);
        
        $endTime = microtime(true);
        $updateTime = ($endTime - $startTime) * 1000;
        $timePerRecord = round($updateTime / $batchSize, 4);
        
        $updateResults[] = [
            'batch_size' => $batchSize,
            'total_time' => $updateTime,
            'time_per_record' => $timePerRecord
        ];
        
        echo "   📊 Batch {$batchSize}: {$updateTime}ms ({$timePerRecord}ms/record)\n";
    }
    
    // Analýza výsledkov
    $avgTimePerRecord = array_sum(array_column($updateResults, 'time_per_record')) / count($updateResults);
    
    if ($avgTimePerRecord <= 0.5) { // 0.5ms per record
        echo "   ✅ Veľmi rýchle batch UPDATE operácie\n";
        $passedTests++;
        $testResults[] = ['test' => 'Batch UPDATE Speed', 'status' => 'PASS', 'value' => $avgTimePerRecord];
    } elseif ($avgTimePerRecord <= 2.0) { // 2ms per record
        echo "   ✅ Rýchle batch UPDATE operácie\n";
        $passedTests++;
        $testResults[] = ['test' => 'Batch UPDATE Speed', 'status' => 'PASS', 'value' => $avgTimePerRecord];
    } elseif ($avgTimePerRecord <= 10.0) { // 10ms per record
        echo "   ⚠️  Priemerné batch UPDATE operácie\n";
        $testResults[] = ['test' => 'Batch UPDATE Speed', 'status' => 'WARNING', 'value' => $avgTimePerRecord];
    } else {
        echo "   ❌ Pomalé batch UPDATE operácie\n";
        $testResults[] = ['test' => 'Batch UPDATE Speed', 'status' => 'FAIL', 'value' => $avgTimePerRecord];
    }
    
    // Vyčistenie
    $pdo->exec("DROP TEMPORARY TABLE test_batch_update");
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Batch UPDATE Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Rýchlosť batch SELECT operácií
echo "\n📊 Test 3: Rýchlosť batch SELECT operácií\n";
echo "-----------------------------------------\n";

$totalTests++;
try {
    $iterations = 5;
    $totalTime = 0;
    
    echo "   📋 Testovanie {$iterations} iterácií batch SELECT:\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $startTime = microtime(true);
        
        // Komplexný batch SELECT s JOIN
        $stmt = $pdo->query("
            SELECT 
                e.ticker,
                e.company,
                e.eps_estimate,
                e.revenue_estimate,
                g.estimated_eps_guidance,
                g.estimated_revenue_guidance,
                g.eps_guide_vs_consensus_pct,
                g.revenue_guide_vs_consensus_pct
            FROM earningstickerstoday e
            LEFT JOIN benzinga_guidance g ON e.ticker = g.ticker 
                AND e.fiscal_period = g.fiscal_period 
                AND e.fiscal_year = g.fiscal_year
            WHERE e.report_date >= CURDATE() - INTERVAL 30 DAY
            ORDER BY e.ticker, g.last_updated DESC
        ");
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $selectTime = ($endTime - $startTime) * 1000;
        $totalTime += $selectTime;
        
        echo "   📊 Iterácia {$i}: {$selectTime}ms (" . count($results) . " záznamov)\n";
    }
    
    $averageTime = round($totalTime / $iterations, 2);
    echo "   📊 Priemerný čas batch SELECT: {$averageTime}ms\n";
    
    if ($averageTime <= 100) { // 100ms
        echo "   ✅ Veľmi rýchle batch SELECT operácie\n";
        $passedTests++;
        $testResults[] = ['test' => 'Batch SELECT Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 500) { // 500ms
        echo "   ✅ Rýchle batch SELECT operácie\n";
        $passedTests++;
        $testResults[] = ['test' => 'Batch SELECT Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 1000) { // 1000ms
        echo "   ⚠️  Priemerné batch SELECT operácie\n";
        $testResults[] = ['test' => 'Batch SELECT Speed', 'status' => 'WARNING', 'value' => $averageTime];
    } else {
        echo "   ❌ Pomalé batch SELECT operácie\n";
        $testResults[] = ['test' => 'Batch SELECT Speed', 'status' => 'FAIL', 'value' => $averageTime];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Batch SELECT Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Rýchlosť batch DELETE operácií
echo "\n📊 Test 4: Rýchlosť batch DELETE operácií\n";
echo "-----------------------------------------\n";

$totalTests++;
try {
    // Vytvorenie testovacej tabuľky s dátami
    $pdo->exec("
        CREATE TEMPORARY TABLE test_batch_delete (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticker VARCHAR(10),
            value1 DECIMAL(10,4),
            value2 DECIMAL(15,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $batchSizes = [50, 100, 200, 500];
    $deleteResults = [];
    
    echo "   📋 Testovanie rôznych batch DELETE veľkostí:\n";
    
    foreach ($batchSizes as $batchSize) {
        // Vloženie testovacích dát
        $values = [];
        for ($i = 0; $i < $batchSize; $i++) {
            $values[] = "('TEST" . str_pad($i, 3, '0', STR_PAD_LEFT) . "', " . (rand(1, 100) / 10) . ", " . rand(1000000, 10000000) . ")";
        }
        $sql = "INSERT INTO test_batch_delete (ticker, value1, value2) VALUES " . implode(', ', $values);
        $pdo->exec($sql);
        
        $startTime = microtime(true);
        
        // Batch DELETE
        $pdo->exec("DELETE FROM test_batch_delete WHERE ticker LIKE 'TEST%'");
        
        $endTime = microtime(true);
        $deleteTime = ($endTime - $startTime) * 1000;
        $timePerRecord = round($deleteTime / $batchSize, 4);
        
        $deleteResults[] = [
            'batch_size' => $batchSize,
            'total_time' => $deleteTime,
            'time_per_record' => $timePerRecord
        ];
        
        echo "   📊 Batch {$batchSize}: {$deleteTime}ms ({$timePerRecord}ms/record)\n";
    }
    
    // Analýza výsledkov
    $avgTimePerRecord = array_sum(array_column($deleteResults, 'time_per_record')) / count($deleteResults);
    
    if ($avgTimePerRecord <= 0.1) { // 0.1ms per record
        echo "   ✅ Veľmi rýchle batch DELETE operácie\n";
        $passedTests++;
        $testResults[] = ['test' => 'Batch DELETE Speed', 'status' => 'PASS', 'value' => $avgTimePerRecord];
    } elseif ($avgTimePerRecord <= 0.5) { // 0.5ms per record
        echo "   ✅ Rýchle batch DELETE operácie\n";
        $passedTests++;
        $testResults[] = ['test' => 'Batch DELETE Speed', 'status' => 'PASS', 'value' => $avgTimePerRecord];
    } elseif ($avgTimePerRecord <= 2.0) { // 2ms per record
        echo "   ⚠️  Priemerné batch DELETE operácie\n";
        $testResults[] = ['test' => 'Batch DELETE Speed', 'status' => 'WARNING', 'value' => $avgTimePerRecord];
    } else {
        echo "   ❌ Pomalé batch DELETE operácie\n";
        $testResults[] = ['test' => 'Batch DELETE Speed', 'status' => 'FAIL', 'value' => $avgTimePerRecord];
    }
    
    // Vyčistenie
    $pdo->exec("DROP TEMPORARY TABLE test_batch_delete");
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Batch DELETE Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Rýchlosť transakcií
echo "\n📊 Test 5: Rýchlosť transakcií\n";
echo "-----------------------------\n";

$totalTests++;
try {
    $iterations = 10;
    $totalTime = 0;
    $successfulTransactions = 0;
    
    echo "   📋 Testovanie {$iterations} iterácií transakcií:\n";
    
    for ($i = 1; $i <= $iterations; $i++) {
        $startTime = microtime(true);
        
        try {
            $pdo->beginTransaction();
            
            // Vytvorenie testovacej tabuľky
            $pdo->exec("
                CREATE TEMPORARY TABLE test_transaction (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ticker VARCHAR(10),
                    value1 DECIMAL(10,4),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Batch INSERT v transakcii
            $values = [];
            for ($j = 0; $j < 50; $j++) {
                $values[] = "('TEST" . str_pad($j, 3, '0', STR_PAD_LEFT) . "', " . (rand(1, 100) / 10) . ")";
            }
            $sql = "INSERT INTO test_transaction (ticker, value1) VALUES " . implode(', ', $values);
            $pdo->exec($sql);
            
            // UPDATE v transakcii
            $pdo->exec("UPDATE test_transaction SET value1 = value1 * 1.1 WHERE ticker LIKE 'TEST%'");
            
            // SELECT v transakcii
            $stmt = $pdo->query("SELECT COUNT(*) FROM test_transaction");
            $count = $stmt->fetchColumn();
            
            $pdo->commit();
            $successfulTransactions++;
            
            $endTime = microtime(true);
            $transactionTime = ($endTime - $startTime) * 1000;
            $totalTime += $transactionTime;
            
            echo "   📊 Iterácia {$i}: {$transactionTime}ms (50 records)\n";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "   ❌ Iterácia {$i}: Transaction failed - " . $e->getMessage() . "\n";
        }
    }
    
    $averageTime = round($totalTime / $iterations, 2);
    $successRate = round(($successfulTransactions / $iterations) * 100, 1);
    echo "   📊 Priemerný čas transakcie: {$averageTime}ms\n";
    echo "   📊 Úspešnosť transakcií: {$successfulTransactions}/{$iterations} ({$successRate}%)\n";
    
    if ($averageTime <= 50 && $successRate >= 90) { // 50ms a 90% success
        echo "   ✅ Rýchle a spoľahlivé transakcie\n";
        $passedTests++;
        $testResults[] = ['test' => 'Transaction Speed', 'status' => 'PASS', 'value' => $averageTime];
    } elseif ($averageTime <= 200 && $successRate >= 80) { // 200ms a 80% success
        echo "   ⚠️  Priemerné transakcie\n";
        $testResults[] = ['test' => 'Transaction Speed', 'status' => 'WARNING', 'value' => $averageTime];
    } else {
        echo "   ❌ Pomalé alebo nespolehlivé transakcie\n";
        $testResults[] = ['test' => 'Transaction Speed', 'status' => 'FAIL', 'value' => $averageTime];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Transaction Speed', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Memory usage pri batch operáciách
echo "\n📊 Test 6: Memory usage pri batch operáciách\n";
echo "-------------------------------------------\n";

$totalTests++;
try {
    $initialMemory = memory_get_usage(true);
    
    // Veľký batch SELECT
    $stmt = $pdo->query("
        SELECT 
            e.*,
            g.*
        FROM earningstickerstoday e
        LEFT JOIN benzinga_guidance g ON e.ticker = g.ticker
        LIMIT 1000
    ");
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $memoryAfterSelect = memory_get_usage(true);
    
    // Batch processing
    $processedData = [];
    foreach ($results as $row) {
        $processedData[] = [
            'ticker' => $row['ticker'],
            'eps_surprise' => isset($row['eps_estimate']) && $row['eps_estimate'] != 0 ? 
                (($row['estimated_eps_guidance'] - $row['eps_estimate']) / $row['eps_estimate']) * 100 : null,
            'revenue_surprise' => isset($row['revenue_estimate']) && $row['revenue_estimate'] != 0 ? 
                (($row['estimated_revenue_guidance'] - $row['revenue_estimate']) / $row['revenue_estimate']) * 100 : null
        ];
    }
    
    $finalMemory = memory_get_usage(true);
    
    $selectMemoryUsage = round(($memoryAfterSelect - $initialMemory) / 1024 / 1024, 2); // MB
    $totalMemoryUsage = round(($finalMemory - $initialMemory) / 1024 / 1024, 2); // MB
    $processingMemoryUsage = round(($finalMemory - $memoryAfterSelect) / 1024 / 1024, 2); // MB
    
    echo "   📋 Memory usage štatistiky:\n";
    echo "   📊 Počiatočná memory: " . round($initialMemory / 1024 / 1024, 2) . "MB\n";
    echo "   📊 Memory po SELECT: " . round($memoryAfterSelect / 1024 / 1024, 2) . "MB\n";
    echo "   📊 Finálna memory: " . round($finalMemory / 1024 / 1024, 2) . "MB\n";
    echo "   📊 Memory pre SELECT: {$selectMemoryUsage}MB\n";
    echo "   📊 Memory pre processing: {$processingMemoryUsage}MB\n";
    echo "   📊 Celková memory usage: {$totalMemoryUsage}MB\n";
    echo "   📊 Zpracovaných záznamov: " . count($processedData) . "\n";
    
    if ($totalMemoryUsage <= 20) { // 20MB
        echo "   ✅ Nízka memory usage pri batch operáciách\n";
        $passedTests++;
        $testResults[] = ['test' => 'Batch Memory Usage', 'status' => 'PASS', 'value' => $totalMemoryUsage];
    } elseif ($totalMemoryUsage <= 100) { // 100MB
        echo "   ⚠️  Priemerná memory usage pri batch operáciách\n";
        $testResults[] = ['test' => 'Batch Memory Usage', 'status' => 'WARNING', 'value' => $totalMemoryUsage];
    } else {
        echo "   ❌ Vysoká memory usage pri batch operáciách\n";
        $testResults[] = ['test' => 'Batch Memory Usage', 'status' => 'FAIL', 'value' => $totalMemoryUsage];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Batch Memory Usage', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "🏆 VÝBORNE! Batch operations performance je výborná!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina batch operations testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica batch operations testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho batch operations testov zlyhalo.\n";
}

echo "\n🎉 Test batch operations dokončený!\n";
?>
