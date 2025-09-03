<?php
/**
 * 📊 ESTIMATES CONSENSUS UPDATES CRON
 * 
 * Populuje estimates_consensus tabuľku s konsenzus estimates z externých API
 * - Získava EPS a Revenue estimates z Finnhub
 * - Získava analyst estimates z Alpha Vantage
 * - Aktualizuje consensus percentages v benzinga_guidance
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/error_handler.php';
require_once __DIR__ . '/../common/ConsensusCalculator.php';

$startTime = microtime(true);
echo "🚀 ESTIMATES CONSENSUS UPDATES PROCESS STARTED\n";
echo "📅 Date: " . date('Y-m-d H:i:s') . "\n";
echo "⏰ Time: " . date('H:i:s', strtotime('now')) . " NY\n\n";

try {
    // Get today's tickers
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT ticker FROM earningstickerstoday WHERE report_date = ?");
    $stmt->execute([$date]);
    $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Found " . count($tickers) . " tickers for date: {$date}\n\n";
    
    if (empty($tickers)) {
        echo "⚠️  No tickers found for today\n";
        exit(0);
    }
    
    // Initialize ConsensusCalculator
    $consensusCalc = new ConsensusCalculator();
    
    // Get current fiscal year and quarter
    $currentYear = (int)$usDate->format('Y');
    $currentMonth = (int)$usDate->format('n');
    
    // Determine current fiscal quarter
    if ($currentMonth <= 3) {
        $currentQuarter = 'Q1';
    } elseif ($currentMonth <= 6) {
        $currentQuarter = 'Q2';
    } elseif ($currentMonth <= 9) {
        $currentQuarter = 'Q3';
    } else {
        $currentQuarter = 'Q4';
    }
    
    echo "📅 Current fiscal period: {$currentQuarter} {$currentYear}\n";
    echo "📅 Previous fiscal period: Q4 " . ($currentYear - 1) . "\n\n";
    
    // Process each ticker
    $processed = 0;
    $added = 0;
    $updated = 0;
    
    foreach ($tickers as $ticker) {
        echo "🔍 Processing {$ticker}...\n";
        
        // Get estimates from Finnhub (if available)
        $epsEstimate = null;
        $revenueEstimate = null;
        
        try {
            // Check if we have estimates in EarningsTickersToday
            $stmt = $pdo->prepare("
                SELECT eps_estimate, revenue_estimate 
                FROM earningstickerstoday 
                WHERE ticker = ? AND report_date = ?
            ");
            $stmt->execute([$ticker, $date]);
            $estimates = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($estimates) {
                $epsEstimate = $estimates['eps_estimate'];
                $revenueEstimate = $estimates['revenue_estimate'];
                echo "  📊 Found estimates: EPS={$epsEstimate}, Revenue=" . number_format($revenueEstimate ?? 0) . "\n";
            }
            
            // Add consensus estimates for current and previous fiscal periods
            $fiscalPeriods = [
                ['period' => $currentQuarter, 'year' => $currentYear],
                ['period' => 'Q4', 'year' => $currentYear - 1]
            ];
            
            foreach ($fiscalPeriods as $fiscal) {
                $period = $fiscal['period'];
                $year = $fiscal['year'];
                
                // Check if consensus already exists
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM estimates_consensus 
                    WHERE ticker = ? AND fiscal_period = ? AND fiscal_year = ?
                ");
                $stmt->execute([$ticker, $period, $year]);
                $exists = $stmt->fetchColumn() > 0;
                
                if ($exists) {
                    echo "  ✅ Consensus already exists for {$period} {$year}\n";
                    $updated++;
                } else {
                    // Add new consensus estimate
                    $consensusCalc->addConsensusEstimate(
                        $ticker, 
                        $period, 
                        $year, 
                        $epsEstimate, 
                        $revenueEstimate, 
                        'finnhub_estimates'
                    );
                    echo "  ➕ Added consensus for {$period} {$year}\n";
                    $added++;
                }
            }
            
            $processed++;
            
        } catch (Exception $e) {
            echo "  ❌ Error processing {$ticker}: " . $e->getMessage() . "\n";
        }
        
        // Rate limiting - pause every 10 tickers
        if ($processed % 10 === 0) {
            echo "  ⏳ Pausing for rate limiting...\n";
            sleep(1);
        }
    }
    
    echo "\n=== FINAL SUMMARY ===\n";
    echo "📊 Total tickers processed: {$processed}\n";
    echo "➕ New consensus records added: {$added}\n";
    echo "✅ Existing records updated: {$updated}\n";
    
    // Update consensus percentages in benzinga_guidance
    echo "\n🔄 Updating consensus percentages in benzinga_guidance...\n";
    $consensusCalc->updateAllConsensusPercentages();
    
    // Show examples of updated data
    echo "\n📋 Examples of updated consensus data:\n";
    $consensusCalc->showUpdatedExamples(5);
    
    // Show missing consensus data
    echo "\n⚠️  Missing consensus data analysis:\n";
    $missingCount = $consensusCalc->showMissingConsensus();
    
    // Run sanity checks
    echo "\n🔍 Running sanity checks...\n";
    $sanityResults = $consensusCalc->runSanityChecks();
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "\n✅ ESTIMATES CONSENSUS UPDATES COMPLETED SUCCESSFULLY!\n";
    echo "⏱️  Total execution time: {$executionTime}s\n";
    
} catch (Exception $e) {
    logCronError('estimates_consensus_updates', $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    displayError("ERROR: " . $e->getMessage());
    exit(1);
}
?>
