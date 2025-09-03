<?php
/**
 * Detailná analýza earnings dát pre tickery s actual hodnotami
 */

require_once __DIR__ . '/test_config.php';

echo "🔍 Detailed Earnings Analysis - Actual vs Estimates\n";
echo "==================================================\n\n";

// 1. Získaj všetky tickery s actual EPS
echo "1. Tickers with Actual EPS Data:\n";
echo "================================\n";

try {
    $stmt = $pdo->query("
        SELECT 
            e.ticker,
            e.eps_estimate,
            e.revenue_estimate,
            m.eps_actual,
            m.revenue_actual,
            m.current_price,
            m.market_cap,
            e.report_date
        FROM earningstickerstoday e
        INNER JOIN todayearningsmovements m ON e.ticker = m.ticker
        WHERE m.eps_actual IS NOT NULL 
          AND m.eps_actual != '' 
          AND m.eps_actual != 'N/A'
        ORDER BY e.ticker
    ");
    
    $epsRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($epsRecords)) {
        foreach ($epsRecords as $record) {
            echo "📊 " . $record['ticker'] . ":\n";
            echo "   📅 Date: " . $record['report_date'] . "\n";
            echo "   💰 Price: $" . number_format($record['current_price'], 2) . "\n";
            echo "   🏢 Market Cap: $" . number_format($record['market_cap'], 0) . "\n";
            echo "   📈 EPS Estimate: " . ($record['eps_estimate'] ?: 'N/A') . "\n";
            echo "   ✅ EPS Actual: " . $record['eps_actual'] . "\n";
            
            if ($record['eps_estimate'] && $record['eps_estimate'] != 'N/A') {
                $epsDiff = $record['eps_actual'] - $record['eps_estimate'];
                $epsPercent = ($epsDiff / $record['eps_estimate']) * 100;
                echo "   📊 EPS Difference: " . number_format($epsDiff, 4) . " (" . number_format($epsPercent, 1) . "%)\n";
            }
            
            if ($record['revenue_actual'] && $record['revenue_actual'] != 'N/A') {
                echo "   💵 Revenue Actual: $" . number_format($record['revenue_actual'], 0) . "\n";
            }
            
            echo "\n";
        }
    } else {
        echo "   ⚠️ No records with actual EPS data found\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Získaj všetky tickery s actual Revenue
echo "2. Tickers with Actual Revenue Data:\n";
echo "====================================\n";

try {
    $stmt = $pdo->query("
        SELECT 
            e.ticker,
            e.eps_estimate,
            e.revenue_estimate,
            m.eps_actual,
            m.revenue_actual,
            m.current_price,
            m.market_cap,
            e.report_date
        FROM earningstickerstoday e
        INNER JOIN todayearningsmovements m ON e.ticker = m.ticker
        WHERE m.revenue_actual IS NOT NULL 
          AND m.revenue_actual != '' 
          AND m.revenue_actual != 'N/A'
        ORDER BY e.ticker
    ");
    
    $revenueRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($revenueRecords)) {
        foreach ($revenueRecords as $record) {
            echo "💰 " . $record['ticker'] . ":\n";
            echo "   📅 Date: " . $record['report_date'] . "\n";
            echo "   💰 Price: $" . number_format($record['current_price'], 2) . "\n";
            echo "   🏢 Market Cap: $" . number_format($record['market_cap'], 0) . "\n";
            echo "   📈 Revenue Estimate: $" . ($record['revenue_estimate'] ? number_format($record['revenue_estimate'], 0) : 'N/A') . "\n";
            echo "   ✅ Revenue Actual: $" . number_format($record['revenue_actual'], 0) . "\n";
            
            if ($record['revenue_estimate'] && $record['revenue_estimate'] != 'N/A') {
                $revenueDiff = $record['revenue_actual'] - $record['revenue_estimate'];
                $revenuePercent = ($revenueDiff / $record['revenue_estimate']) * 100;
                echo "   📊 Revenue Difference: $" . number_format($revenueDiff, 0) . " (" . number_format($revenuePercent, 1) . "%)\n";
            }
            
            if ($record['eps_actual'] && $record['eps_actual'] != 'N/A') {
                echo "   📈 EPS Actual: " . $record['eps_actual'] . "\n";
            }
            
            echo "\n";
        }
    } else {
        echo "   ⚠️ No records with actual revenue data found\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Štatistiky
echo "3. Statistics Summary:\n";
echo "=====================\n";

try {
    // Počet tickerov s actual EPS
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ticker) as count FROM todayearningsmovements WHERE eps_actual IS NOT NULL AND eps_actual != '' AND eps_actual != 'N/A'");
    $epsTickerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Počet tickerov s actual Revenue
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ticker) as count FROM todayearningsmovements WHERE revenue_actual IS NOT NULL AND revenue_actual != '' AND revenue_actual != 'N/A'");
    $revenueTickerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Celkový počet tickerov
    $stmt = $pdo->query("SELECT COUNT(DISTINCT ticker) as count FROM earningstickerstoday");
    $totalTickers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "📊 Total Tickers: $totalTickers\n";
    echo "📊 Tickers with Actual EPS: $epsTickerCount (" . round(($epsTickerCount / $totalTickers) * 100, 1) . "%)\n";
    echo "📊 Tickers with Actual Revenue: $revenueTickerCount (" . round(($revenueTickerCount / $totalTickers) * 100, 1) . "%)\n";
    
    // Počet tickerov s oboma actual hodnotami
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT ticker) as count 
        FROM todayearningsmovements 
        WHERE eps_actual IS NOT NULL AND eps_actual != '' AND eps_actual != 'N/A'
          AND revenue_actual IS NOT NULL AND revenue_actual != '' AND revenue_actual != 'N/A'
    ");
    $bothActualCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 Tickers with Both Actual Values: $bothActualCount (" . round(($bothActualCount / $totalTickers) * 100, 1) . "%)\n";
    
} catch (Exception $e) {
    echo "   ❌ Error in statistics: " . $e->getMessage() . "\n";
}

echo "\n✅ Detailed earnings analysis completed!\n";
?>
