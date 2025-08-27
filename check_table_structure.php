<?php
require_once 'config.php';

echo "=== CHECKING TABLE STRUCTURE ===\n";

try {
    $stmt = $pdo->query("DESCRIBE todayearningsmovements");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in todayearningsmovements table:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
