<?php
require_once 'config.php';

echo "=== EXISTING TABLES ===\n";

// Get all tables
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "Table: {$table}\n";
}

echo "\n=== TABLE STRUCTURES ===\n";

foreach ($tables as $table) {
    echo "\n--- {$table} ---\n";
    $stmt = $pdo->query("DESCRIBE `{$table}`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}\n";
    }
}
?>
