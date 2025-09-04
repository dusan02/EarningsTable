<?php
require_once 'config.php';

echo "Adding fiscal_period and fiscal_year to EarningsTickersToday...\n";

try {
    // Add columns
    $pdo->exec("ALTER TABLE earningstickerstoday 
                ADD COLUMN fiscal_period ENUM('Q1','Q2','Q3','Q4','FY','H1','H2') DEFAULT NULL,
                ADD COLUMN fiscal_year INT DEFAULT NULL");
    
    // Add index
    $pdo->exec("CREATE INDEX idx_ett_fiscal ON earningstickerstoday (ticker, fiscal_period, fiscal_year)");
    
    // Update existing records
    $pdo->exec("UPDATE earningstickerstoday 
                SET 
                    fiscal_period = CASE 
                        WHEN MONTH(report_date) IN (1,2,3) THEN 'Q1'
                        WHEN MONTH(report_date) IN (4,5,6) THEN 'Q2' 
                        WHEN MONTH(report_date) IN (7,8,9) THEN 'Q3'
                        WHEN MONTH(report_date) IN (10,11,12) THEN 'Q4'
                        ELSE 'FY'
                    END,
                    fiscal_year = YEAR(report_date)
                WHERE fiscal_period IS NULL");
    
    echo "✅ Fiscal periods added successfully!\n";
    
    // Verify
    $stmt = $pdo->query("SELECT ticker, report_date, fiscal_period, fiscal_year FROM earningstickerstoday LIMIT 5");
    echo "\nSample updated records:\n";
    while($row = $stmt->fetch()) {
        echo "Ticker: {$row['ticker']}, Date: {$row['report_date']}, Period: {$row['fiscal_period']}, Year: {$row['fiscal_year']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
