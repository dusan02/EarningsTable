<?php
require_once 'config.php';

echo "=== FIXING DATABASE SCHEMA ===\n";

try {
    // Check current table structure
    echo "Checking current table structure...\n";
    $stmt = $pdo->query("DESCRIBE earningstickerstoday");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasDataSource = false;
    $hasSourcePriority = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'data_source') {
            $hasDataSource = true;
        }
        if ($column['Field'] === 'source_priority') {
            $hasSourcePriority = true;
        }
    }
    
    echo "Current columns: " . implode(', ', array_column($columns, 'Field')) . "\n";
    
    // Add data_source column if it doesn't exist
    if (!$hasDataSource) {
        echo "Adding data_source column...\n";
        $pdo->exec("
            ALTER TABLE earningstickerstoday 
            ADD COLUMN data_source ENUM('finnhub', 'yahoo_finance') NOT NULL DEFAULT 'finnhub'
        ");
        echo "✅ data_source column added\n";
    } else {
        echo "✅ data_source column already exists\n";
    }
    
    // Add source_priority column if it doesn't exist
    if (!$hasSourcePriority) {
        echo "Adding source_priority column...\n";
        $pdo->exec("
            ALTER TABLE earningstickerstoday 
            ADD COLUMN source_priority INT NOT NULL DEFAULT 1
        ");
        echo "✅ source_priority column added\n";
    } else {
        echo "✅ source_priority column already exists\n";
    }
    
    // Create index if it doesn't exist
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM information_schema.statistics 
        WHERE table_name = 'earningstickerstoday' 
        AND index_name = 'idx_data_source'
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "Creating data_source index...\n";
        $pdo->exec("CREATE INDEX idx_data_source ON earningstickerstoday(data_source)");
        echo "✅ data_source index created\n";
    } else {
        echo "✅ data_source index already exists\n";
    }
    
    // Show final structure
    echo "\nFinal table structure:\n";
    $stmt = $pdo->query("DESCRIBE earningstickerstoday");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "  {$column['Field']} - {$column['Type']}\n";
    }
    
    echo "\n=== DATABASE SCHEMA FIXED ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
