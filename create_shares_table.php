<?php
require_once 'config.php';

echo "Creating SharesOutstanding table...\n";

$sql = "
CREATE TABLE IF NOT EXISTS SharesOutstanding (
    ticker CHAR(10) NOT NULL PRIMARY KEY,
    shares_outstanding BIGINT NOT NULL,
    fetched_on DATE NOT NULL,
    INDEX idx_fetched_on (fetched_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $pdo->exec($sql);
    echo "✅ SharesOutstanding table created successfully!\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'SharesOutstanding'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table verification: SharesOutstanding exists\n";
    } else {
        echo "❌ Table verification failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
}
?>
