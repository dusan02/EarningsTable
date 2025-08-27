<?php
require_once 'config.php';

echo "=== ADDING MISSING COLUMNS ===\n";

try {
    // Add missing columns
    $sql = "
    ALTER TABLE todayearningsmovements 
    ADD COLUMN company_type VARCHAR(10) NULL COMMENT 'Company type from Polygon (CS, ETF, etc.)',
    ADD COLUMN primary_exchange VARCHAR(20) NULL COMMENT 'Primary exchange from Polygon'
    ";
    
    $pdo->exec($sql);
    echo "✅ Added missing columns to todayearningsmovements table\n";
    
    // Add indexes
    $indexes = [
        "CREATE INDEX idx_company_type ON todayearningsmovements(company_type)",
        "CREATE INDEX idx_primary_exchange ON todayearningsmovements(primary_exchange)"
    ];
    
    foreach ($indexes as $indexSql) {
        $pdo->exec($indexSql);
    }
    echo "✅ Added indexes for better performance\n";
    
    echo "\n✅ MISSING COLUMNS ADDED SUCCESSFULLY!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
