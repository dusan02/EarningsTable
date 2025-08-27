<?php
require_once 'D:/xampp/htdocs/earnings-table/config.php';

echo "🔍 ANALYZING NULL VALUES IN EARNINGSTICKERSTODAY\n";
echo "================================================\n\n";

// Check total records
$totalStmt = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday WHERE report_date = CURDATE()");
$totalRecords = $totalStmt->fetchColumn();
echo "📊 TOTAL RECORDS TODAY: {$totalRecords}\n\n";

// Check NULL values in each column
$columns = ['eps_actual', 'eps_estimate', 'revenue_actual', 'revenue_estimate'];

foreach ($columns as $column) {
    $nullStmt = $pdo->prepare("SELECT COUNT(*) FROM EarningsTickersToday WHERE report_date = CURDATE() AND {$column} IS NULL");
    $nullStmt->execute();
    $nullCount = $nullStmt->fetchColumn();
    $percentage = round(($nullCount / $totalRecords) * 100, 1);
    
    echo "📈 {$column}: {$nullCount} NULL values ({$percentage}%)\n";
}

// Show sample records with actual data
echo "\n📋 SAMPLE RECORDS WITH ACTUAL DATA:\n";
$sampleStmt = $pdo->query("
    SELECT ticker, report_time, eps_actual, eps_estimate, revenue_actual, revenue_estimate 
    FROM EarningsTickersToday 
    WHERE report_date = CURDATE() 
    AND (eps_actual IS NOT NULL OR eps_estimate IS NOT NULL OR revenue_actual IS NOT NULL OR revenue_estimate IS NOT NULL)
    ORDER BY ticker 
    LIMIT 10
");
$samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($samples)) {
    echo "  ❌ No records with actual data found\n";
} else {
    foreach ($samples as $sample) {
        echo "  {$sample['ticker']} ({$sample['report_time']}): ";
        echo "EPS: " . ($sample['eps_actual'] ?? 'NULL') . "/" . ($sample['eps_estimate'] ?? 'NULL') . " | ";
        echo "Revenue: " . ($sample['revenue_actual'] ?? 'NULL') . "/" . ($sample['revenue_estimate'] ?? 'NULL') . "\n";
    }
}

// Check specific tickers mentioned in the image
echo "\n🎯 CHECKING SPECIFIC TICKERS:\n";
$specificTickers = ['LLY', 'MSI', 'FLUT', 'LNG', 'VST', 'PH', 'GILD'];

foreach ($specificTickers as $ticker) {
    $tickerStmt = $pdo->prepare("
        SELECT ticker, report_time, eps_actual, eps_estimate, revenue_actual, revenue_estimate 
        FROM EarningsTickersToday 
        WHERE ticker = ? AND report_date = CURDATE()
    ");
    $tickerStmt->execute([$ticker]);
    $tickerData = $tickerStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tickerData) {
        echo "  {$ticker} ({$tickerData['report_time']}): ";
        echo "EPS: " . ($tickerData['eps_actual'] ?? 'NULL') . "/" . ($tickerData['eps_estimate'] ?? 'NULL') . " | ";
        echo "Revenue: " . ($tickerData['revenue_actual'] ?? 'NULL') . "/" . ($tickerData['revenue_estimate'] ?? 'NULL') . "\n";
    } else {
        echo "  {$ticker}: Not found in today's data\n";
    }
}

echo "\n📝 EXPLANATION:\n";
echo "  • NULL values are normal for earnings data that hasn't been reported yet\n";
echo "  • Actual EPS/Revenue are only available after the earnings call\n";
echo "  • Estimates may not exist for all companies\n";
echo "  • Data is fetched daily at 02:15 CET for US Eastern Time compatibility\n";
?> 