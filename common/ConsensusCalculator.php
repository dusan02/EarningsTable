<?php
/**
 * 🚀 CONSENSUS CALCULATOR - ENHANCED VERSION
 * 
 * Trieda pre výpočet rozdielov % medzi guidance a konsenzus estimates
 * - Automatické mapovanie ticker + fiscal_period + fiscal_year
 * - Bezpečné výpočty s kontrolou delenia nulou
 * - Logovanie metodických nezhôd
 * - Vylepšená validácia a sanity checks
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/UnifiedValidator.php';

class ConsensusCalculator {
    private $pdo;
    
    public function __construct() {
        $this->pdo = $GLOBALS['pdo'];
    }
    
    /**
     * Vypočíta rozdiel % medzi guidance a konsenzom
     * Používa UnifiedValidator pre konzistentné výpočty
     */
    private function calculateDeltaPercent($guide, $consensus) {
        return UnifiedValidator::calculateDeltaPercent($guide, $consensus);
    }
    
    /**
     * Aktualizuje consensus percentages pre všetky záznamy
     */
    public function updateAllConsensusPercentages() {
        echo "🔄 Updating consensus percentages for all records...\n";
        
        // Kontrola pred update
        $before = $this->getConsensusStatus();
        echo "📊 BEFORE: EPS {$before['eps_filled']}/{$before['total']}, Revenue {$before['rev_filled']}/{$before['total']}\n";
        
        // SQL UPDATE s JOIN - vylepšená verzia s metodickými nezhodami
        $updateSql = "
            UPDATE benzinga_guidance g
            JOIN estimates_consensus e
              ON e.ticker = g.ticker
             AND e.fiscal_period = g.fiscal_period
             AND e.fiscal_year = g.fiscal_year
            SET
              g.eps_guide_vs_consensus_pct =
                CASE
                  WHEN e.consensus_eps IS NULL OR e.consensus_eps = 0 OR g.estimated_eps_guidance IS NULL
                    THEN NULL
                  ELSE ((g.estimated_eps_guidance - e.consensus_eps) / e.consensus_eps) * 100
                END,
              g.revenue_guide_vs_consensus_pct =
                CASE
                  WHEN e.consensus_revenue IS NULL OR e.consensus_revenue = 0 OR g.estimated_revenue_guidance IS NULL
                    THEN NULL
                  ELSE ((g.estimated_revenue_guidance - e.consensus_revenue) / e.consensus_revenue) * 100
                END,
              g.method_mismatch_eps = CASE
                WHEN g.eps_method IS NULL OR e.eps_method IS NULL THEN NULL
                WHEN g.eps_method <> e.eps_method THEN 1 ELSE 0 END,
              g.method_mismatch_rev = CASE
                WHEN g.revenue_method IS NULL OR e.revenue_method IS NULL THEN NULL
                WHEN g.revenue_method <> e.revenue_method THEN 1 ELSE 0 END
        ";
        
        $stmt = $this->pdo->prepare($updateSql);
        $stmt->execute();
        $affectedRows = $stmt->rowCount();
        
        echo "✅ UPDATE completed! Affected rows: {$affectedRows}\n";
        
        // Kontrola po update
        $after = $this->getConsensusStatus();
        echo "📊 AFTER: EPS {$after['eps_filled']}/{$after['total']}, Revenue {$after['rev_filled']}/{$after['total']}\n";
        
        return [
            'affected_rows' => $affectedRows,
            'before' => $before,
            'after' => $after
        ];
    }
    
    /**
     * Získa aktuálny stav consensus percentages
     */
    private function getConsensusStatus() {
        $stmt = $this->pdo->query("
            SELECT 
                SUM(eps_guide_vs_consensus_pct IS NOT NULL) AS eps_filled,
                SUM(revenue_guide_vs_consensus_pct IS NOT NULL) AS rev_filled,
                COUNT(*) AS total
            FROM benzinga_guidance
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Zobrazí príklady aktualizovaných dát
     */
    public function showUpdatedExamples($limit = 5) {
        echo "\n🔍 Examples of updated data:\n";
        
        $stmt = $this->pdo->query("
            SELECT 
                g.ticker,
                g.fiscal_period,
                g.fiscal_year,
                g.estimated_eps_guidance,
                g.estimated_revenue_guidance,
                g.eps_guide_vs_consensus_pct,
                g.revenue_guide_vs_consensus_pct,
                g.eps_method,
                g.revenue_method,
                g.method_mismatch_eps,
                g.method_mismatch_rev,
                e.consensus_eps,
                e.consensus_revenue
            FROM benzinga_guidance g
            LEFT JOIN estimates_consensus e
              ON e.ticker = g.ticker
             AND e.fiscal_period = g.fiscal_period
             AND e.fiscal_year = g.fiscal_year
            WHERE g.eps_guide_vs_consensus_pct IS NOT NULL 
               OR g.revenue_guide_vs_consensus_pct IS NOT NULL
            LIMIT {$limit}
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  📋 {$row['ticker']} ({$row['fiscal_period']} {$row['fiscal_year']}):\n";
            if ($row['eps_guide_vs_consensus_pct'] !== null) {
                echo "    EPS: {$row['estimated_eps_guidance']} vs {$row['consensus_eps']} = {$row['eps_guide_vs_consensus_pct']}%";
                if ($row['method_mismatch_eps']) echo " ⚠️ METHOD MISMATCH";
                echo " (method: {$row['eps_method']})\n";
            }
            if ($row['revenue_guide_vs_consensus_pct'] !== null) {
                echo "    Revenue: " . number_format($row['estimated_revenue_guidance']) . " vs " . number_format($row['consensus_revenue']) . " = {$row['revenue_guide_vs_consensus_pct']}%";
                if ($row['method_mismatch_rev']) echo " ⚠️ METHOD MISMATCH";
                echo " (method: {$row['revenue_method']})\n";
            }
            echo "\n";
        }
    }
    
    /**
     * Zobrazí chýbajúce consensus dáta
     */
    public function showMissingConsensus() {
        echo "⚠️  Missing consensus data:\n";
        
        $stmt = $this->pdo->query("
            SELECT g.ticker, g.fiscal_period, g.fiscal_year
            FROM benzinga_guidance g
            LEFT JOIN estimates_consensus e
              ON e.ticker = g.ticker
             AND e.fiscal_period = g.fiscal_period
             AND e.fiscal_year = g.fiscal_year
            WHERE e.ticker IS NULL
        ");
        
        $missingCount = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ❌ {$row['ticker']} {$row['fiscal_period']} {$row['fiscal_year']}\n";
            $missingCount++;
        }
        
        if ($missingCount === 0) {
            echo "  ✅ All tickers have consensus data!\n";
        } else {
            echo "  📊 Total missing: {$missingCount}\n";
        }
        
        return $missingCount;
    }
    
    /**
     * Spustí sanity checks
     */
    public function runSanityChecks() {
        echo "\n🔍 RUNNING SANITY CHECKS:\n";
        echo "=========================\n";
        
        // 1. Extrémne hodnoty
        echo "\n📊 1) Extreme consensus percentage values (>50%):\n";
        $stmt = $this->pdo->query("
            SELECT ticker, fiscal_period, fiscal_year, 
                   eps_guide_vs_consensus_pct, revenue_guide_vs_consensus_pct
            FROM benzinga_guidance
            WHERE ABS(eps_guide_vs_consensus_pct) > 50
               OR ABS(revenue_guide_vs_consensus_pct) > 50
        ");
        
        $extremeCount = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ⚠️  {$row['ticker']} {$row['fiscal_period']} {$row['fiscal_year']}: ";
            if ($row['eps_guide_vs_consensus_pct'] !== null) {
                echo "EPS {$row['eps_guide_vs_consensus_pct']}% ";
            }
            if ($row['revenue_guide_vs_consensus_pct'] !== null) {
                echo "Revenue {$row['revenue_guide_vs_consensus_pct']}%";
            }
            echo "\n";
            $extremeCount++;
        }
        
        if ($extremeCount === 0) {
            echo "  ✅ No extreme values found!\n";
        } else {
            echo "  📊 Total extreme values: {$extremeCount}\n";
        }
        
        // 2. Metodické nesúlady
        echo "\n📊 2) Methodology mismatches:\n";
        $stmt = $this->pdo->query("
            SELECT 
                SUM(method_mismatch_eps = 1) as eps_mismatches,
                SUM(method_mismatch_rev = 1) as revenue_mismatches,
                COUNT(*) as total
            FROM benzinga_guidance
        ");
        $mismatchStats = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  EPS methodology mismatches: {$mismatchStats['eps_mismatches']}/{$mismatchStats['total']}\n";
        echo "  Revenue methodology mismatches: {$mismatchStats['revenue_mismatches']}/{$mismatchStats['total']}\n";
        
        // 3. Guidance bez konsenzu
        echo "\n📊 3) Guidance without consensus data:\n";
        $stmt = $this->pdo->query("
            SELECT g.ticker, g.fiscal_period, g.fiscal_year
            FROM benzinga_guidance g
            LEFT JOIN estimates_consensus e
              ON e.ticker = g.ticker 
             AND e.fiscal_period = g.fiscal_period 
             AND e.fiscal_year = g.fiscal_year
            WHERE (g.estimated_eps_guidance IS NOT NULL OR g.estimated_revenue_guidance IS NOT NULL)
              AND (e.ticker IS NULL)
        ");
        
        $missingCount = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ❌ {$row['ticker']} {$row['fiscal_period']} {$row['fiscal_year']}\n";
            $missingCount++;
        }
        
        if ($missingCount === 0) {
            echo "  ✅ All guidance records have consensus data!\n";
        } else {
            echo "  📊 Total missing consensus: {$missingCount}\n";
        }
        
        return [
            'extreme_values' => $extremeCount,
            'methodology_mismatches' => $mismatchStats['eps_mismatches'] + $mismatchStats['revenue_mismatches'],
            'missing_consensus' => $missingCount
        ];
    }
    
    /**
     * Pridá nový consensus estimate
     */
    public function addConsensusEstimate($ticker, $fiscalPeriod, $fiscalYear, $consensusEps = null, $consensusRevenue = null, $source = 'manual') {
        $sql = "
            INSERT INTO estimates_consensus (ticker, fiscal_period, fiscal_year, consensus_eps, consensus_revenue, source)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            consensus_eps = VALUES(consensus_eps),
            consensus_revenue = VALUES(consensus_revenue),
            source = VALUES(source),
            updated_at = CURRENT_TIMESTAMP
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$ticker, $fiscalPeriod, $fiscalYear, $consensusEps, $consensusRevenue, $source]);
        
        echo "✅ Added/Updated consensus for {$ticker} {$fiscalPeriod} {$fiscalYear}\n";
        return $stmt->rowCount();
    }
    
    /**
     * Spustí kompletný proces aktualizácie
     */
    public function run() {
        echo "🚀 CONSENSUS CALCULATOR PROCESS STARTED - ENHANCED VERSION\n";
        echo "========================================================\n\n";
        
        try {
            // 1. Aktualizácia consensus percentages
            $result = $this->updateAllConsensusPercentages();
            
            // 2. Zobrazenie príkladov
            $this->showUpdatedExamples();
            
            // 3. Kontrola chýbajúcich dát
            $missingCount = $this->showMissingConsensus();
            
            // 4. Sanity checks
            $sanityResults = $this->runSanityChecks();
            
            echo "\n✅ CONSENSUS CALCULATOR PROCESS COMPLETED!\n";
            echo "🎯 Updated {$result['affected_rows']} records\n";
            echo "📊 Missing consensus data: {$missingCount} tickers\n";
            echo "🔍 Sanity checks: {$sanityResults['extreme_values']} extreme values, {$sanityResults['methodology_mismatches']} method mismatches\n";
            
            return array_merge($result, ['sanity' => $sanityResults]);
            
        } catch (Exception $e) {
            echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    /**
     * Batch processing pre estimates consensus updates
     */
    public function processBatchEstimates($tickers, $fiscalPeriods) {
        echo "🔄 Processing batch estimates for " . count($tickers) . " tickers...\n";
        
        $batchSize = 10; // Process 10 tickers at a time
        $chunks = array_chunk($tickers, $batchSize);
        $totalProcessed = 0;
        $totalAdded = 0;
        $totalUpdated = 0;
        
        foreach ($chunks as $index => $chunk) {
            echo "  Processing chunk " . ($index + 1) . "/" . count($chunks) . " (" . count($chunk) . " tickers)...\n";
            
            $chunkStart = microtime(true);
            $chunkResults = $this->processChunkEstimates($chunk, $fiscalPeriods);
            
            $totalProcessed += $chunkResults['processed'];
            $totalAdded += $chunkResults['added'];
            $totalUpdated += $chunkResults['updated'];
            
            $chunkTime = round(microtime(true) - $chunkStart, 2);
            echo "    ✅ Chunk completed in {$chunkTime}s\n";
            
            // Rate limiting between chunks
            if ($index < count($chunks) - 1) {
                usleep(100000); // 100ms delay
            }
        }
        
        echo "✅ Batch processing completed: {$totalProcessed} processed, {$totalAdded} added, {$totalUpdated} updated\n";
        
        return [
            'total_processed' => $totalProcessed,
            'total_added' => $totalAdded,
            'total_updated' => $totalUpdated
        ];
    }
    
    /**
     * Spracovanie jedného chunku tickerov
     */
    private function processChunkEstimates($tickers, $fiscalPeriods) {
        $processed = 0;
        $added = 0;
        $updated = 0;
        
        foreach ($tickers as $ticker) {
            $result = $this->processTickerEstimates($ticker, $fiscalPeriods);
            
            if ($result['processed']) {
                $processed++;
                if ($result['action'] === 'added') {
                    $added++;
                } elseif ($result['action'] === 'updated') {
                    $updated++;
                }
            }
        }
        
        return [
            'processed' => $processed,
            'added' => $added,
            'updated' => $updated
        ];
    }
}
?>
