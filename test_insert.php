<?php
require_once 'config.php';

echo "=== TESTING INSERT INTO todayearningsmovements ===\n";

// Test simple insert
try {
    $stmt = $pdo->prepare("
        INSERT INTO todayearningsmovements (
            ticker, company_name, previous_close, market_cap, size,
            shares_outstanding, company_type, primary_exchange, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            company_name = VALUES(company_name),
            previous_close = VALUES(previous_close),
            market_cap = VALUES(market_cap),
            size = VALUES(size),
            shares_outstanding = VALUES(shares_outstanding),
            company_type = VALUES(company_type),
            primary_exchange = VALUES(primary_exchange),
            updated_at = NOW()
    ");
    
    $result = $stmt->execute([
        'TEST',
        'Test Company',
        100.00,
        1000000000,
        'Small',
        10000000,
        'stock',
        'NASDAQ',
    ]);
    
    if ($result) {
        echo "✅ INSERT successful\n";
        
        // Check if record was inserted
        $stmt = $pdo->prepare('SELECT * FROM todayearningsmovements WHERE ticker = ?');
        $stmt->execute(['TEST']);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            echo "✅ Record found in database\n";
            echo "Ticker: {$record['ticker']}\n";
            echo "Company: {$record['company_name']}\n";
            echo "Price: {$record['previous_close']}\n";
        } else {
            echo "❌ Record not found in database\n";
        }
        
        // Clean up
        $stmt = $pdo->prepare('DELETE FROM todayearningsmovements WHERE ticker = ?');
        $stmt->execute(['TEST']);
        echo "✅ Test record deleted\n";
        
    } else {
        echo "❌ INSERT failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING TABLE STRUCTURE ===\n";
$stmt = $pdo->query('SHOW CREATE TABLE todayearningsmovements');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Table structure:\n";
echo $result['Create Table'] . "\n";
?>
