<?php
/**
 * Check Actual Data Test
 * Kontroluje eps_actual a revenue_actual dáta v databáze
 */

require_once 'config.php';

echo "=== ACTUAL DATA CHECK ===\n";
echo "Kontrola eps_actual a revenue_actual dát\n";
echo "=====================================\n\n";

try {
    // 1. Kontrola TodayEarningsMovements tabuľky
    echo "1. TodayEarningsMovements tabuľka:\n";
    echo "--------------------------------\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM TodayEarningsMovements");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Celkový počet záznamov: $total\n";
    
    // Kontrola eps_actual
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL AND eps_actual != ''");
    $epsActualCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Záznamov s eps_actual: $epsActualCount\n";
    
    // Kontrola revenue_actual
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE revenue_actual IS NOT NULL AND revenue_actual != ''");
    $revenueActualCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Záznamov s revenue_actual: $revenueActualCount\n";
    
    // Kontrola oboch hodnôt
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL AND eps_actual != '' AND revenue_actual IS NOT NULL AND revenue_actual != ''");
    $bothCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Záznamov s oboma hodnotami: $bothCount\n\n";
    
    // 2. Ukážkové dáta s actual hodnotami
    echo "2. Ukážkové dáta s actual hodnotami:\n";
    echo "-----------------------------------\n";
    
    $stmt = $pdo->query("
        SELECT ticker, eps_estimate, eps_actual, revenue_estimate, revenue_actual, report_time, updated_at 
        FROM TodayEarningsMovements 
        WHERE (eps_actual IS NOT NULL AND eps_actual != '') OR (revenue_actual IS NOT NULL AND revenue_actual != '')
        ORDER BY updated_at DESC 
        LIMIT 10
    ");
    
    $foundData = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $foundData = true;
        echo "Ticker: {$row['ticker']}\n";
        echo "  EPS Estimate: " . ($row['eps_estimate'] ?? 'NULL') . "\n";
        echo "  EPS Actual: " . ($row['eps_actual'] ?? 'NULL') . "\n";
        echo "  Revenue Estimate: " . ($row['revenue_estimate'] ?? 'NULL') . "\n";
        echo "  Revenue Actual: " . ($row['revenue_actual'] ?? 'NULL') . "\n";
        echo "  Report Time: " . ($row['report_time'] ?? 'NULL') . "\n";
        echo "  Updated: " . $row['updated_at'] . "\n";
        echo "  ---\n";
    }
    
    if (!$foundData) {
        echo "❌ Nenašli sa žiadne záznamy s actual hodnotami!\n\n";
    }
    
    // 3. Kontrola stĺpcov v tabuľke
    echo "3. Kontrola stĺpcov v tabuľke:\n";
    echo "-----------------------------\n";
    
    $stmt = $pdo->query("DESCRIBE TodayEarningsMovements");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    $hasEpsActual = in_array('eps_actual', $columns);
    $hasRevenueActual = in_array('revenue_actual', $columns);
    
    echo "Stĺpec eps_actual: " . ($hasEpsActual ? "✅ Existuje" : "❌ Chýba") . "\n";
    echo "Stĺpec revenue_actual: " . ($hasRevenueActual ? "✅ Existuje" : "❌ Chýba") . "\n\n";
    
    // 4. Kontrola Finnhub API response
    echo "4. Kontrola Finnhub API response:\n";
    echo "-------------------------------\n";
    
    if (class_exists('Finnhub')) {
        require_once 'common/Finnhub.php';
        
        // Získaj dnešný dátum
        $timezone = new DateTimeZone('America/New_York');
        $usDate = new DateTime('now', $timezone);
        $date = $usDate->format('Y-m-d');
        
        echo "Testujem Finnhub API pre dátum: $date\n";
        
        $finnhub = new Finnhub();
        $response = $finnhub->getEarningsCalendar('', $date, $date);
        
        if ($response && isset($response['earningsCalendar'])) {
            $earningsData = $response['earningsCalendar'];
            echo "✅ Finnhub API vrátil " . count($earningsData) . " earnings záznamov\n";
            
            // Kontrola prvých 3 záznamov
            $sampleCount = min(3, count($earningsData));
            echo "Ukážkové dáta z Finnhub API:\n";
            
            for ($i = 0; $i < $sampleCount; $i++) {
                $earning = $earningsData[$i];
                echo "  Ticker: " . ($earning['symbol'] ?? 'NULL') . "\n";
                echo "    EPS Estimate: " . ($earning['epsEstimate'] ?? 'NULL') . "\n";
                echo "    EPS Actual: " . ($earning['epsActual'] ?? 'NULL') . "\n";
                echo "    Revenue Estimate: " . ($earning['revenueEstimate'] ?? 'NULL') . "\n";
                echo "    Revenue Actual: " . ($earning['revenueActual'] ?? 'NULL') . "\n";
                echo "    ---\n";
            }
        } else {
            echo "❌ Finnhub API nevrátil dáta alebo chyba\n";
        }
    } else {
        echo "❌ Finnhub trieda nie je dostupná\n";
    }
    
    // 5. Odporúčania
    echo "\n5. Odporúčania:\n";
    echo "---------------\n";
    
    if ($epsActualCount == 0 && $revenueActualCount == 0) {
        echo "❌ Žiadne actual dáta nie sú v databáze!\n";
        echo "Možné príčiny:\n";
        echo "1. Finnhub API nevracia actual hodnoty\n";
        echo "2. Cron job sa nespustil\n";
        echo "3. Dáta sa neuložili správne\n";
        echo "4. Earnings ešte neprebehli (actual hodnoty sa objavujú po earnings)\n\n";
        
        echo "Riešenia:\n";
        echo "1. Spustite cron job: php cron/fetch_finnhub_earnings_today_tickers.php\n";
        echo "2. Skontrolujte Finnhub API response\n";
        echo "3. Počkajte na earnings reports\n";
    } else {
        echo "✅ Nájdené actual dáta!\n";
        echo "EPS Actual: $epsActualCount záznamov\n";
        echo "Revenue Actual: $revenueActualCount záznamov\n";
    }
    
} catch (Exception $e) {
    echo "❌ Chyba: " . $e->getMessage() . "\n";
}
?>
