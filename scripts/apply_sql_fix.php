<?php
require_once 'config.php';
require_once __DIR__ . '/../common/error_handler.php';

echo "=== APPLYING SQL FIXES ===\n\n";

try {
    // Fix 1: Use previous close as current price when current_price is 0
    $stmt = $pdo->prepare("
        UPDATE TodayEarningsMovements 
        SET current_price = previous_close, 
            price_change_percent = NULL 
        WHERE current_price = 0 AND previous_close > 0
    ");
    $stmt->execute();
    echo "✅ Fixed 0.00 prices using previous close\n";
    
    // Fix 2: Set price_change_percent to NULL when either price is 0
    $stmt = $pdo->prepare("
        UPDATE TodayEarningsMovements 
        SET price_change_percent = NULL 
        WHERE previous_close = 0 OR current_price = 0
    ");
    $stmt->execute();
    echo "✅ Fixed -100% price changes\n";
    
    // Fix 3: Set market_cap to NULL when we don't have valid data
    $stmt = $pdo->prepare("
        UPDATE TodayEarningsMovements 
        SET market_cap = NULL 
        WHERE (shares_outstanding IS NULL OR shares_outstanding <= 0) OR current_price = 0
    ");
    $stmt->execute();
    echo "✅ Fixed invalid market caps\n";
    
    // Show results after fix
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN current_price > 0 THEN 1 ELSE 0 END) as with_price,
            SUM(CASE WHEN market_cap > 0 THEN 1 ELSE 0 END) as with_mc,
            SUM(CASE WHEN price_change_percent IS NOT NULL THEN 1 ELSE 0 END) as with_change_pct
        FROM TodayEarningsMovements
    ");
    $result = $stmt->fetch();
    
    echo "\n📊 FINAL RESULTS AFTER FIX:\n";
    echo "   Total records: " . $result['total'] . "\n";
    echo "   With price: " . $result['with_price'] . "\n";
    echo "   With market cap: " . $result['with_mc'] . "\n";
    echo "   With price change %: " . $result['with_change_pct'] . "\n";
    
    echo "\n✅ ALL FIXES APPLIED SUCCESSFULLY!\n";
    
} catch (Exception $e) {
    logDatabaseError('apply_sql_fix', 'UPDATE statements', [], $e->getMessage(), [
        'operation' => 'sql_fixes'
    ]);
    displayError("Error: " . $e->getMessage());
}
?>
