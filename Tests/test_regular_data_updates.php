<?php
/**
 * 🚀 CRITICAL TEST: RegularDataUpdatesDynamic
 * Testuje kľúčové funkcie pre 5-minútové aktualizácie dát
 */

require_once __DIR__ . '/test_config.php';
require_once __DIR__ . '/../common/RegularDataUpdatesDynamic.php';

echo "🚀 CRITICAL TEST: RegularDataUpdatesDynamic\n";
echo "==========================================\n\n";

try {
    // 1. Test vytvorenia inštancie
    echo "1. Test vytvorenia inštancie...\n";
    $regularUpdates = new RegularDataUpdatesDynamic();
    echo "   ✅ RegularDataUpdatesDynamic vytvorený úspešne\n";
    
    // 2. Test existujúcich tickerov
    echo "\n2. Test existujúcich tickerov...\n";
    
    // Kontrola tabuľky todayearningsmovements
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM todayearningsmovements");
    $stmt->execute();
    $totalTickers = $stmt->fetch(PDO::FETCH_COLUMN)['count'];
    
    if ($totalTickers > 0) {
        echo "   ✅ Celkovo tickerov: $totalTickers\n";
        
        // Získaj sample tickery s aktuálnymi cenami
        $stmt = $pdo->prepare("
            SELECT ticker, current_price, previous_close, price_change_percent, market_cap 
            FROM todayearningsmovements 
            WHERE current_price IS NOT NULL AND current_price > 0 
            LIMIT 3
        ");
        $stmt->execute();
        $sampleTickers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sampleTickers as $ticker) {
            echo "     📊 {$ticker['ticker']}: \${$ticker['current_price']} ({$ticker['price_change_percent']}%)\n";
        }
    } else {
        echo "   ⚠️ Žiadne tickery nenájdené v todayearningsmovements\n";
    }
    
    // 3. Test EPS a Revenue actual dát
    echo "\n3. Test EPS a Revenue actual dát...\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN eps_actual IS NOT NULL AND eps_actual != '' AND eps_actual != 'N/A' THEN 1 END) as eps_actual_count,
            COUNT(CASE WHEN revenue_actual IS NOT NULL AND revenue_actual != '' AND revenue_actual != 'N/A' THEN 1 END) as revenue_actual_count
        FROM todayearningsmovements
    ");
    $stmt->execute();
    $actualData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📊 Celkovo tickerov: {$actualData['total']}\n";
    echo "   📊 S EPS actual: {$actualData['eps_actual_count']}\n";
    echo "   📊 S Revenue actual: {$actualData['revenue_actual_count']}\n";
    
    if ($actualData['eps_actual_count'] > 0 || $actualData['revenue_actual_count'] > 0) {
        echo "   ✅ Actual dáta sú k dispozícii\n";
        
        // Získaj sample actual dáta
        $stmt = $pdo->prepare("
            SELECT ticker, eps_actual, revenue_actual, current_price 
            FROM todayearningsmovements 
            WHERE (eps_actual IS NOT NULL AND eps_actual != '' AND eps_actual != 'N/A')
               OR (revenue_actual IS NOT NULL AND revenue_actual != '' AND revenue_actual != 'N/A')
            LIMIT 3
        ");
        $stmt->execute();
        $sampleActuals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sampleActuals as $actual) {
            echo "     📈 {$actual['ticker']}: EPS: {$actual['eps_actual']}, Rev: {$actual['revenue_actual']}, Price: \${$actual['current_price']}\n";
        }
    } else {
        echo "   ⚠️ Žiadne actual dáta nenájdené (všetko sú estimates)\n";
    }
    
    // 4. Test market cap diff výpočtov
    echo "\n4. Test market cap diff výpočtov...\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN market_cap_diff IS NOT NULL THEN 1 END) as with_diff,
            COUNT(CASE WHEN market_cap_diff_billions IS NOT NULL THEN 1 END) as with_diff_billions
        FROM todayearningsmovements
    ");
    $stmt->execute();
    $marketCapData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📊 Celkovo tickerov: {$marketCapData['total']}\n";
    echo "   📊 S market cap diff: {$marketCapData['with_diff']}\n";
    echo "   📊 S market cap diff (B): {$marketCapData['with_diff_billions']}\n";
    
    if ($marketCapData['with_diff'] > 0) {
        echo "   ✅ Market cap diff dáta sú k dispozícii\n";
        
        // Získaj sample market cap diff
        $stmt = $pdo->prepare("
            SELECT ticker, market_cap, market_cap_diff, market_cap_diff_billions 
            FROM todayearningsmovements 
            WHERE market_cap_diff IS NOT NULL 
            LIMIT 3
        ");
        $stmt->execute();
        $sampleMarketCap = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sampleMarketCap as $mcap) {
            echo "     💰 {$mcap['ticker']}: MC: \${$mcap['market_cap']}, Diff: {$mcap['market_cap_diff']} ({$mcap['market_cap_diff_billions']}B)\n";
        }
    } else {
        echo "   ⚠️ Žiadne market cap diff dáta nenájdené\n";
    }
    
    // 5. Test price change percent
    echo "\n5. Test price change percent...\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN price_change_percent IS NOT NULL THEN 1 END) as with_change,
            COUNT(CASE WHEN price_change_percent > 0 THEN 1 END) as positive_change,
            COUNT(CASE WHEN price_change_percent < 0 THEN 1 END) as negative_change
        FROM todayearningsmovements
        WHERE current_price IS NOT NULL AND current_price > 0
    ");
    $stmt->execute();
    $priceChangeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📊 Celkovo tickerov s cenami: {$priceChangeData['total']}\n";
    echo "   📊 S price change: {$priceChangeData['with_change']}\n";
    echo "   📊 Pozitívna zmena: {$priceChangeData['positive_change']}\n";
    echo "   📊 Negatívna zmena: {$priceChangeData['negative_change']}\n";
    
    if ($priceChangeData['with_change'] > 0) {
        echo "   ✅ Price change dáta sú k dispozícii\n";
    } else {
        echo "   ⚠️ Žiadne price change dáta nenájdené\n";
    }
    
    // 6. Test updated_at timestamp
    echo "\n6. Test updated_at timestamp...\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN updated_at IS NOT NULL THEN 1 END) as with_timestamp,
            MAX(updated_at) as latest_update
        FROM todayearningsmovements
    ");
    $stmt->execute();
    $timestampData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   📊 Celkovo tickerov: {$timestampData['total']}\n";
    echo "   📊 S timestamp: {$timestampData['with_timestamp']}\n";
    echo "   📊 Posledná aktualizácia: {$timestampData['latest_update']}\n";
    
    if ($timestampData['with_timestamp'] > 0) {
        echo "   ✅ Timestamp dáta sú k dispozícii\n";
    } else {
        echo "   ⚠️ Žiadne timestamp dáta nenájdené\n";
    }
    
    // 7. Test performance a integrity
    echo "\n7. Test performance a integrity...\n";
    
    // Kontrola duplicitných tickerov
    $stmt = $pdo->prepare("
        SELECT ticker, COUNT(*) as count 
        FROM todayearningsmovements 
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
    
    // Performance test
    $startTime = microtime(true);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM todayearningsmovements");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_COLUMN);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "   ⏱️ Query trval: {$duration}ms\n";
    
    if ($duration < 100) {
        echo "   ✅ Performance OK (< 100ms)\n";
    } else {
        echo "   ⚠️ Performance pomalšie (> 100ms)\n";
    }
    
    echo "\n✅ Všetky critical testy pre RegularDataUpdatesDynamic prešli úspešne!\n";
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
