<?php
require_once 'config.php';

echo "=== DELETING SHARESOUTSTANDING TABLE ===\n";

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'sharesoutstanding'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Tabulka sharesoutstanding existuje\n";
        
        // Drop the table
        $pdo->exec('DROP TABLE sharesoutstanding');
        echo "✅ Tabulka sharesoutstanding bola úspešne vymazaná\n";
    } else {
        echo "ℹ️  Tabulka sharesoutstanding neexistuje\n";
    }
    
    // Verify deletion
    $stmt = $pdo->query("SHOW TABLES LIKE 'sharesoutstanding'");
    $stillExists = $stmt->rowCount() > 0;
    
    if (!$stillExists) {
        echo "✅ Overenie: Tabulka sharesoutstanding už neexistuje\n";
    } else {
        echo "❌ Chyba: Tabulka sharesoutstanding stále existuje\n";
    }
    
} catch (Exception $e) {
    echo "❌ Chyba pri mazaní tabuľky: " . $e->getMessage() . "\n";
}

echo "\n=== CURRENT TABLES ===\n";
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch()) {
    $tableName = array_values($row)[0];
    echo "  - {$tableName}\n";
}
?>
