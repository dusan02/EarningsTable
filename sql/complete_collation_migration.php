<?php
require_once 'config.php';

echo "🚀 Completing collation migration...\n";

try {
    // Get all tables that still need migration
    $stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'earnings_table' AND TABLE_TYPE = 'BASE TABLE'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "Migrating table: $table\n";
        $pdo->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    echo "✅ Migration completed!\n";
    
    // Check final status
    echo "\n🔍 Final collation status:\n";
    $stmt = $pdo->query("SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'earnings_table' ORDER BY TABLE_NAME");
    while($row = $stmt->fetch()) {
        echo "  {$row['TABLE_NAME']}: {$row['TABLE_COLLATION']}\n";
    }
    
    $stmt = $pdo->query("SELECT DISTINCT COLLATION_NAME, COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'earnings_table' AND COLLATION_NAME IS NOT NULL GROUP BY COLLATION_NAME");
    echo "\nColumn Collation Summary:\n";
    while($row = $stmt->fetch()) {
        echo "  {$row['COLLATION_NAME']}: {$row['count']} columns\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
