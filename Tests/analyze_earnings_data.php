<?php
/**
 * Analýza earnings dát v databáze
 * Kontroluje eps_actual a revenue_actual
 */

require_once __DIR__ . '/test_config.php';

echo "🔍 Analyzing Earnings Data in Database\n";
echo "=====================================\n\n";

// 1. Analýza earningstickerstoday
echo "1. Analysis of earningstickerstoday table:\n";
echo "==========================================\n";

try {
    // Celkový počet záznamov
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM earningstickerstoday");
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total records: $totalRecords\n";
    
    // Počet záznamov podľa dátumu
    $stmt = $pdo->query("SELECT report_date, COUNT(*) as count FROM earningstickerstoday GROUP BY report_date ORDER BY report_date DESC");
    $dateCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Records by date:\n";
    foreach ($dateCounts as $row) {
        echo "     " . $row['report_date'] . ": " . $row['count'] . " records\n";
    }
    
    // Počet záznamov s EPS estimate
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM earningstickerstoday WHERE eps_estimate IS NOT NULL AND eps_estimate != '' AND eps_estimate != 'N/A'");
    $epsEstimateCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Records with EPS Estimate: $epsEstimateCount\n";
    
    // Počet záznamov s Revenue estimate
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM earningstickerstoday WHERE revenue_estimate IS NOT NULL AND revenue_estimate != '' AND revenue_estimate != 'N/A'");
    $revenueEstimateCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Records with Revenue Estimate: $revenueEstimateCount\n";
    
} catch (Exception $e) {
    echo "   ❌ Error analyzing earningstickerstoday: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Analýza todayearningsmovements
echo "2. Analysis of todayearningsmovements table:\n";
echo "============================================\n";

try {
    // Celkový počet záznamov
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM todayearningsmovements");
    $totalMovements = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total records: $totalMovements\n";
    
    // Počet záznamov s EPS actual
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM todayearningsmovements WHERE eps_actual IS NOT NULL AND eps_actual != '' AND eps_actual != 'N/A'");
    $epsActualCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Records with EPS Actual: $epsActualCount\n";
    
    // Počet záznamov s Revenue actual
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM todayearningsmovements WHERE revenue_actual IS NOT NULL AND revenue_actual != '' AND revenue_actual != 'N/A'");
    $revenueActualCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Records with Revenue Actual: $revenueActualCount\n";
    
    // Počet záznamov s current price
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM todayearningsmovements WHERE current_price IS NOT NULL AND current_price > 0");
    $priceCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Records with Current Price: $priceCount\n";
    
    // Počet záznamov s market cap
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM todayearningsmovements WHERE market_cap IS NOT NULL AND market_cap > 0");
    $marketCapCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Records with Market Cap: $marketCapCount\n";
    
} catch (Exception $e) {
    echo "   ❌ Error analyzing todayearningsmovements: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Detailná analýza EPS a Revenue
echo "3. Detailed EPS and Revenue Analysis:\n";
echo "=====================================\n";

try {
    // Sample záznamov s EPS actual
    if ($epsActualCount > 0) {
        echo "   Sample records with EPS Actual:\n";
        $stmt = $pdo->query("SELECT ticker, eps_actual, current_price FROM todayearningsmovements WHERE eps_actual IS NOT NULL AND eps_actual != '' AND eps_actual != 'N/A' LIMIT 5");
        $epsRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($epsRecords as $record) {
            echo "     " . $record['ticker'] . ": EPS " . $record['eps_actual'] . " @ $" . number_format($record['current_price'], 2) . "\n";
        }
    } else {
        echo "   ⚠️ No records with EPS Actual found\n";
    }
    
    // Sample záznamov s Revenue actual
    if ($revenueActualCount > 0) {
        echo "   Sample records with Revenue Actual:\n";
        $stmt = $pdo->query("SELECT ticker, revenue_actual, current_price FROM todayearningsmovements WHERE revenue_actual IS NOT NULL AND revenue_actual != '' AND revenue_actual != 'N/A' LIMIT 5");
        $revenueRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($revenueRecords as $record) {
            echo "     " . $record['ticker'] . ": Revenue " . $record['revenue_actual'] . " @ $" . number_format($record['current_price'], 2) . "\n";
        }
    } else {
        echo "   ⚠️ No records with Revenue Actual found\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error in detailed analysis: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Porovnanie estimates vs actual
echo "4. Estimates vs Actual Comparison:\n";
echo "==================================\n";

try {
    // JOIN oboch tabuliek pre porovnanie
    $stmt = $pdo->query("
        SELECT 
            e.ticker,
            e.eps_estimate,
            e.revenue_estimate,
            m.eps_actual,
            m.revenue_actual,
            m.current_price
        FROM earningstickerstoday e
        LEFT JOIN todayearningsmovements m ON e.ticker = m.ticker
        WHERE (e.eps_estimate IS NOT NULL AND e.eps_estimate != '' AND e.eps_estimate != 'N/A')
           OR (e.revenue_estimate IS NOT NULL AND e.revenue_estimate != '' AND e.revenue_estimate != 'N/A')
        LIMIT 10
    ");
    
    $comparisonRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($comparisonRecords)) {
        echo "   Sample comparison records:\n";
        foreach ($comparisonRecords as $record) {
            echo "     " . $record['ticker'] . ":\n";
            if ($record['eps_estimate']) {
                echo "       EPS Est: " . $record['eps_estimate'] . " | Actual: " . ($record['eps_actual'] ?: 'N/A') . "\n";
            }
            if ($record['revenue_estimate']) {
                echo "       Rev Est: " . $record['revenue_estimate'] . " | Actual: " . ($record['revenue_actual'] ?: 'N/A') . "\n";
            }
            if ($record['current_price']) {
                echo "       Price: $" . number_format($record['current_price'], 2) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "   ⚠️ No comparison records found\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error in comparison: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Záver
echo "5. Summary:\n";
echo "===========\n";
echo "📊 Total Earnings Records: $totalRecords\n";
echo "📊 Total Movement Records: $totalMovements\n";
echo "📊 Records with EPS Actual: $epsActualCount\n";
echo "📊 Records with Revenue Actual: $revenueActualCount\n";
echo "📊 Records with EPS Estimate: $epsEstimateCount\n";
echo "📊 Records with Revenue Estimate: $revenueEstimateCount\n";

if ($epsActualCount > 0 || $revenueActualCount > 0) {
    echo "\n✅ Found actual earnings data!\n";
} else {
    echo "\n⚠️ No actual earnings data found - all data is estimates\n";
}

echo "\n✅ Earnings data analysis completed!\n";
?>
