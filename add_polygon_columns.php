<?php
require_once 'config.php';

echo "=== ADDING POLYGON STATIC COLUMNS ===\n";

try {
    // Add new columns for Polygon static data
    $sql = "
    ALTER TABLE todayearningsmovements 
    ADD COLUMN shares_outstanding BIGINT NULL COMMENT 'Number of shares outstanding from Polygon',
    ADD COLUMN company_type VARCHAR(10) NULL COMMENT 'Company type from Polygon (CS, ETF, etc.)',
    ADD COLUMN primary_exchange VARCHAR(20) NULL COMMENT 'Primary exchange from Polygon'
    ";
    
    $pdo->exec($sql);
    echo "✅ Added new columns to todayearningsmovements table\n";
    
    // Add indexes for better performance
    $indexes = [
        "CREATE INDEX idx_shares_outstanding ON todayearningsmovements(shares_outstanding)",
        "CREATE INDEX idx_company_type ON todayearningsmovements(company_type)",
        "CREATE INDEX idx_primary_exchange ON todayearningsmovements(primary_exchange)"
    ];
    
    foreach ($indexes as $indexSql) {
        $pdo->exec($indexSql);
    }
    echo "✅ Added indexes for better performance\n";
    
    echo "\n✅ DATABASE SCHEMA UPDATED SUCCESSFULLY!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
