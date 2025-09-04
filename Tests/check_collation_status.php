<?php
require_once 'config.php';

echo "🔍 Checking collation status...\n";

try {
    // Check table collations
    $stmt = $pdo->query("SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'earnings_table' ORDER BY TABLE_NAME");
    echo "Table Collations:\n";
    while($row = $stmt->fetch()) {
        echo "  {$row['TABLE_NAME']}: {$row['TABLE_COLLATION']}\n";
    }
    
    // Check column collations
    $stmt = $pdo->query("SELECT DISTINCT COLLATION_NAME, COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'earnings_table' AND COLLATION_NAME IS NOT NULL GROUP BY COLLATION_NAME");
    echo "\nColumn Collation Summary:\n";
    while($row = $stmt->fetch()) {
        echo "  {$row['COLLATION_NAME']}: {$row['count']} columns\n";
    }
    
    // Check database collation
    $stmt = $pdo->query("SELECT SCHEMA_NAME, DEFAULT_COLLATION_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'earnings_table'");
    $row = $stmt->fetch();
    echo "\nDatabase Collation: {$row['DEFAULT_COLLATION_NAME']}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
