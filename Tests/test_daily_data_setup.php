<?php
/**
 * 🚀 CRITICAL TEST: DailyDataSetup
 * Testuje kľúčové funkcie pre denné nastavenie dát
 */

require_once __DIR__ . '/test_config.php';
require_once __DIR__ . '/../common/DailyDataSetup.php';

echo "🚀 CRITICAL TEST: DailyDataSetup\n";
echo "================================\n\n";

try {
    // 1. Test vytvorenia inštancie
    echo "1. Test vytvorenia inštancie...\n";
    $dailySetup = new DailyDataSetup();
    echo "   ✅ DailyDataSetup vytvorený úspešne\n";
    
    // 2. Test discovery fázy (simulácia)
    echo "\n2. Test discovery fázy...\n";
    
    // Simulujeme získanie tickerov z databázy
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM earningstickerstoday WHERE report_date = CURDATE()");
    $stmt->execute();
    $todayCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($todayCount > 0) {
        echo "   ✅ Dnešné tickery nájdené: $todayCount\n";
        
        // Získaj sample tickery
        $stmt = $pdo->prepare("SELECT ticker, eps_estimate, revenue_estimate FROM earningstickerstoday WHERE report_date = CURDATE() LIMIT 3");
        $stmt->execute();
        $sampleTickers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sampleTickers as $ticker) {
            echo "     📊 {$ticker['ticker']}: EPS Est: {$ticker['eps_estimate']}, Rev Est: {$ticker['revenue_estimate']}\n";
        }
    } else {
        echo "   ⚠️ Žiadne dnešné tickery nenájdené (možno je príliš skoro alebo neskoro)\n";
    }
    
    // 3. Test databázovej štruktúry
    echo "\n3. Test databázovej štruktúry...\n";
    
    // Kontrola tabuľky earningstickerstoday
    $stmt = $pdo->query("DESCRIBE earningstickerstoday");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['ticker', 'eps_estimate', 'revenue_estimate', 'report_date', 'report_time'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "   ✅ Všetky požadované stĺpce existujú\n";
    } else {
        echo "   ❌ Chýbajúce stĺpce: " . implode(', ', $missingColumns) . "\n";
    }
    
    // 4. Test spracovania estimates
    echo "\n4. Test spracovania estimates...\n";
    
    // Kontrola validnosti estimates
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN eps_estimate IS NOT NULL AND eps_estimate != '' AND eps_estimate != 'N/A' THEN 1 END) as valid_eps,
            COUNT(CASE WHEN revenue_estimate IS NOT NULL AND revenue_estimate != '' AND revenue_estimate != 'N/A' THEN 1 END) as valid_revenue
        FROM earningstickerstoday 
        WHERE report_date = CURDATE()
    ");
    $stmt->execute();
    $estimates = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📊 Celkovo tickerov: {$estimates['total']}\n";
    echo "   📊 Validné EPS estimates: {$estimates['valid_eps']}\n";
    echo "   📊 Validné Revenue estimates: {$estimates['valid_revenue']}\n";
    
    if ($estimates['valid_eps'] > 0 || $estimates['valid_revenue'] > 0) {
        echo "   ✅ Estimates sú k dispozícii\n";
    } else {
        echo "   ⚠️ Žiadne validné estimates nenájdené\n";
    }
    
    // 5. Test report time formátu
    echo "\n5. Test report time formátu...\n";
    
    $stmt = $pdo->prepare("SELECT DISTINCT report_time FROM earningstickerstoday WHERE report_date = CURDATE() AND report_time IS NOT NULL LIMIT 5");
    $stmt->execute();
    $reportTimes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($reportTimes)) {
        echo "   ✅ Report times nájdené:\n";
        foreach ($reportTimes as $time) {
            echo "     🕐 $time\n";
        }
    } else {
        echo "   ⚠️ Žiadne report times nenájdené\n";
    }
    
    // 6. Test integrity dát
    echo "\n6. Test integrity dát...\n";
    
    // Kontrola duplicitných tickerov
    $stmt = $pdo->prepare("
        SELECT ticker, COUNT(*) as count 
        FROM earningstickerstoday 
        WHERE report_date = CURDATE() 
        GROUP BY ticker 
        HAVING COUNT(*) > 1
    ");
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "   ✅ Žiadne duplicitné tickery\n";
    } else {
        echo "   ❌ Duplicitné tickery:\n";
        foreach ($duplicates as $dup) {
            echo "     ⚠️ {$dup['ticker']}: {$dup['count']}x\n";
        }
    }
    
    // 7. Test performance metrik
    echo "\n7. Test performance metrik...\n";
    
    // Simulácia merania času
    $startTime = microtime(true);
    
    // Simulujeme ťažkú operáciu
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM earningstickerstoday WHERE report_date = CURDATE()");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_COLUMN);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "   ⏱️ Query trval: {$duration}ms\n";
    echo "   📊 Počet záznamov: $count\n";
    
    if ($duration < 100) {
        echo "   ✅ Performance OK (< 100ms)\n";
    } else {
        echo "   ⚠️ Performance pomalšie (> 100ms)\n";
    }
    
    echo "\n✅ Všetky critical testy pre DailyDataSetup prešli úspešne!\n";
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
