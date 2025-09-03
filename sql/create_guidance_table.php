<?php
// Create benzinga_guidance table
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
    
    echo "🔧 Creating benzinga_guidance table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS benzinga_guidance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticker CHAR(10) NOT NULL,
        company_name VARCHAR(255) NULL,
        date DATE NOT NULL,
        time TIME NULL,
        fiscal_period ENUM('Q1','Q2','Q3','Q4','FY') NOT NULL,
        fiscal_year INT NOT NULL,
        release_type ENUM('official','unofficial','preliminary','final') DEFAULT 'official',
        positioning ENUM('primary','secondary') DEFAULT 'primary',
        importance INT DEFAULT 1,
        
        estimated_eps_guidance DECIMAL(10,4) NULL,
        min_eps_guidance DECIMAL(10,4) NULL,
        max_eps_guidance DECIMAL(10,4) NULL,
        previous_min_eps_guidance DECIMAL(10,4) NULL,
        previous_max_eps_guidance DECIMAL(10,4) NULL,
        eps_method ENUM('gaap','adj','non-gaap') DEFAULT 'gaap',
        
        estimated_revenue_guidance BIGINT NULL,
        min_revenue_guidance BIGINT NULL,
        max_revenue_guidance BIGINT NULL,
        previous_min_revenue_guidance BIGINT NULL,
        previous_max_revenue_guidance BIGINT NULL,
        revenue_method ENUM('gaap','adj','non-gaap') DEFAULT 'gaap',
        
        eps_guide_vs_consensus_pct DECIMAL(8,4) NULL,
        revenue_guide_vs_consensus_pct DECIMAL(8,4) NULL,
        
        currency CHAR(3) DEFAULT 'USD',
        notes TEXT NULL,
        benzinga_id VARCHAR(100) NULL,
        last_updated TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_ticker (ticker),
        INDEX idx_date (date),
        INDEX idx_fiscal_period (fiscal_period),
        INDEX idx_fiscal_year (fiscal_year),
        INDEX idx_importance (importance),
        INDEX idx_eps_guidance (estimated_eps_guidance),
        INDEX idx_revenue_guidance (estimated_revenue_guidance),
        UNIQUE KEY uniq_ticker_fiscal (ticker, fiscal_period, fiscal_year, release_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "✅ benzinga_guidance table created successfully!\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'benzinga_guidance'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table verification successful\n";
    } else {
        echo "❌ Table verification failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
