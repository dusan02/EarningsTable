<?php
// Create guidance_import_failures table for debugging
$dbHost = 'localhost';
$dbName = 'earnings_db';
$dbUser = 'root';
$dbPass = '';

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "🔧 Creating guidance_import_failures table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS guidance_import_failures (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        ticker VARCHAR(16),
        payload JSON,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ticker (ticker),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "✅ guidance_import_failures table created successfully!\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'guidance_import_failures'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table verification successful\n";
    } else {
        echo "❌ Table verification failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
