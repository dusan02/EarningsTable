<?php
require_once 'D:/xampp/htdocs/earnings-table/config.php';

echo "📊 EARNINGSTICKERSTODAY TABLE STATUS\n";
echo "====================================\n\n";

// Check table structure
$stmt = $pdo->query("DESCRIBE EarningsTickersToday");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📋 TABLE STRUCTURE:\n";
foreach ($columns as $column) {
    echo "  {$column['Field']} - {$column['Type']}\n";
}

echo "\n📊 RECORD COUNT:\n";
$countStmt = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday");
$count = $countStmt->fetchColumn();
echo "  Total records: {$count}\n";

// Show sample data
echo "\n📈 SAMPLE DATA (first 5 records):\n";
$sampleStmt = $pdo->query("SELECT * FROM EarningsTickersToday ORDER BY report_date DESC, ticker LIMIT 5");
$samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($samples as $sample) {
    echo "  {$sample['ticker']} - {$sample['report_date']} - {$sample['report_time']}\n";
}

// Check today's data
echo "\n📅 TODAY'S DATA:\n";
$todayStmt = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday WHERE report_date = CURDATE()");
$todayCount = $todayStmt->fetchColumn();
echo "  Today's records: {$todayCount}\n";

if ($todayCount > 0) {
    $todayDataStmt = $pdo->query("SELECT ticker, report_time FROM EarningsTickersToday WHERE report_date = CURDATE() ORDER BY ticker LIMIT 10");
    $todayData = $todayDataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  Sample today's tickers:\n";
    foreach ($todayData as $data) {
        echo "    {$data['ticker']} ({$data['report_time']})\n";
    }
}
?> 