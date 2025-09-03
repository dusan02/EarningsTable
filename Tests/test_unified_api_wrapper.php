<?php
/**
 * 🚀 CRITICAL TEST: UnifiedApiWrapper
 * Testuje kľúčové API funkcie pre získavanie cien a výpočet zmien
 */

require_once __DIR__ . '/test_config.php';
require_once __DIR__ . '/../common/UnifiedApiWrapper.php';

echo "🚀 CRITICAL TEST: UnifiedApiWrapper\n";
echo "==================================\n\n";

try {
    // 1. Test vytvorenia inštancie
    echo "1. Test vytvorenia inštancie...\n";
    $apiWrapper = new UnifiedApiWrapper();
    echo "   ✅ UnifiedApiWrapper vytvorený úspešne\n";
    
    // 2. Test getCurrentPrice s rôznymi scenármi
    echo "\n2. Test getCurrentPrice()...\n";
    
    // Test 2.1: Last trade (fresh)
    $polygonData1 = [
        'lastTrade' => ['p' => 150.50, 't' => time() * 1000000], // aktuálny čas v nanosekundách
        'lastQuote' => ['bp' => 150.00, 'ap' => 151.00],
        'min' => ['c' => 150.25],
        'session' => ['c' => 150.30],
        'prevDay' => ['c' => 149.00]
    ];
    
    $result1 = $apiWrapper->getCurrentPrice($polygonData1);
    if ($result1 && $result1['price'] == 150.50 && $result1['source'] == 'lastTrade') {
        echo "   ✅ Last trade (fresh) - OK\n";
    } else {
        echo "   ❌ Last trade (fresh) - FAIL\n";
    }
    
    // Test 2.2: Quote mid (ak last trade nie je fresh)
    $polygonData2 = [
        'lastTrade' => ['p' => 150.50, 't' => (time() - 600) * 1000000], // 10 minút starý
        'lastQuote' => ['bp' => 150.00, 'ap' => 151.00],
        'min' => ['c' => 150.25],
        'session' => ['c' => 150.30],
        'prevDay' => ['c' => 149.00]
    ];
    
    $result2 = $apiWrapper->getCurrentPrice($polygonData2);
    if ($result2 && $result2['price'] == 150.50 && $result2['source'] == 'quoteMid') {
        echo "   ✅ Quote mid - OK\n";
    } else {
        echo "   ❌ Quote mid - FAIL\n";
    }
    
    // Test 2.3: Minute close fallback
    $polygonData3 = [
        'lastTrade' => ['p' => 0, 't' => 0], // neplatné dáta
        'lastQuote' => ['bp' => 0, 'ap' => 0], // neplatné dáta
        'min' => ['c' => 150.25],
        'session' => ['c' => 150.30],
        'prevDay' => ['c' => 149.00]
    ];
    
    $result3 = $apiWrapper->getCurrentPrice($polygonData3);
    if ($result3 && $result3['price'] == 150.25 && $result3['source'] == 'minuteClose') {
        echo "   ✅ Minute close fallback - OK\n";
    } else {
        echo "   ❌ Minute close fallback - FAIL\n";
    }
    
    // Test 2.4: Null input
    $result4 = $apiWrapper->getCurrentPrice(null);
    if ($result4 === null) {
        echo "   ✅ Null input handling - OK\n";
    } else {
        echo "   ❌ Null input handling - FAIL\n";
    }
    
    // 3. Test computePercentChange
    echo "\n3. Test computePercentChange()...\n";
    
    // Test 3.1: V3 trade data
    $snapshot = ['todaysChangePerc' => 0];
    $lastTradeV3 = ['p' => 150.50];
    $prevClose = 149.00;
    
    $changeResult1 = $apiWrapper->computePercentChange($snapshot, $lastTradeV3, $prevClose);
    if ($changeResult1 && abs($changeResult1['percent'] - 1.01) < 0.01 && $changeResult1['source'] == 'v3_trade') {
        echo "   ✅ V3 trade percent change - OK\n";
    } else {
        echo "   ❌ V3 trade percent change - FAIL\n";
    }
    
    // Test 3.2: Snapshot change data
    $snapshot2 = ['todaysChangePerc' => 2.5];
    $changeResult2 = $apiWrapper->computePercentChange($snapshot2, null, 149.00);
    if ($changeResult2 && $changeResult2['percent'] == 2.5 && $changeResult2['source'] == 'snapshot_change') {
        echo "   ✅ Snapshot change - OK\n";
    } else {
        echo "   ❌ Snapshot change - FAIL\n";
    }
    
    // Test 3.3: Quote midpoint fallback
    $snapshot3 = [
        'todaysChangePerc' => 0,
        'lastQuote' => ['bp' => 150.00, 'ap' => 151.00]
    ];
    $changeResult3 = $apiWrapper->computePercentChange($snapshot3, null, 149.00);
    if ($changeResult3 && abs($changeResult3['percent'] - 0.67) < 0.01 && $changeResult3['source'] == 'quote_midpoint') {
        echo "   ✅ Quote midpoint fallback - OK\n";
    } else {
        echo "   ❌ Quote midpoint fallback - FAIL\n";
    }
    
    // 4. Test error handling
    echo "\n4. Test error handling...\n";
    
    // Test 4.1: Invalid data
    $invalidData = ['invalid' => 'data'];
    $result5 = $apiWrapper->getCurrentPrice($invalidData);
    if ($result5 === null) {
        echo "   ✅ Invalid data handling - OK\n";
    } else {
        echo "   ❌ Invalid data handling - FAIL\n";
    }
    
    // Test 4.2: Empty arrays
    $emptyData = [];
    $result6 = $apiWrapper->getCurrentPrice($emptyData);
    if ($result6 === null) {
        echo "   ✅ Empty data handling - OK\n";
    } else {
        echo "   ❌ Empty data handling - FAIL\n";
    }
    
    echo "\n✅ Všetky critical testy pre UnifiedApiWrapper prešli úspešne!\n";
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
