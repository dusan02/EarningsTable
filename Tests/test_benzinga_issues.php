<?php
/**
 * 🚀 CRITICAL TEST: Benzinga Issues Resolution
 * 
 * Testuje konkrétne problémy, ktoré sme riešili:
 * 1. Ticker count (35 instead of 13)
 * 2. CRM guidance data display
 * 3. No "tickers in groups" (each ticker only once)
 * 4. Proper JOIN logic (earnings → guidance)
 * 
 * @author GPT Assistant
 * @date 2025-09-04
 */

require_once 'test_config.php';

class BenzingaIssuesTest {
    private $pdo;
    private $testResults = [];
    private $apiUrl = 'http://localhost:8000/api/earnings-tickers-today.php';
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Spusti všetky testy
     */
    public function runAllTests() {
        echo "🚀 SPÚŠŤAM BENZINGA ISSUES RESOLUTION TEST\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $this->testTickerCount();
        $this->testCrmGuidance();
        $this->testNoGroupedTickers();
        $this->testJoinLogic();
        
        $this->printResults();
        return $this->testResults;
    }
    
    /**
     * Test 1: Ticker Count (35 instead of 13)
     */
    private function testTickerCount() {
        echo "📊 Test 1: Kontrola počtu tickerov (35 namiesto 13)...\n";
        
        try {
            // Kontrola API response
            $response = file_get_contents($this->apiUrl);
            if ($response === false) {
                throw new Exception("Nepodarilo sa načítať API endpoint");
            }
            
            $data = json_decode($response, true);
            if ($data === null) {
                throw new Exception("Neplatný JSON response");
            }
            
            $actualCount = $data['total'] ?? 0;
            $expectedCount = 35;
            $correctCount = ($actualCount == $expectedCount);
            
            // Kontrola databázy
            $stmt = $this->pdo->query('SELECT COUNT(DISTINCT ticker) as count FROM EarningsTickersToday');
            $dbCount = $stmt->fetch()['count'];
            
            $this->testResults['ticker_count'] = [
                'api_count' => $actualCount,
                'db_count' => $dbCount,
                'expected_count' => $expectedCount,
                'api_correct' => $correctCount,
                'db_correct' => ($dbCount == $expectedCount),
                'passed' => ($correctCount && $dbCount == $expectedCount)
            ];
            
            if ($this->testResults['ticker_count']['passed']) {
                echo "   ✅ API vracia správne 35 tickerov\n";
                echo "   ✅ Databáza má správne 35 tickerov\n";
            } else {
                echo "   ❌ API: {$actualCount}/{$expectedCount}, DB: {$dbCount}/{$expectedCount}\n";
            }
            
        } catch (Exception $e) {
            $this->testResults['ticker_count'] = [
                'error' => $e->getMessage(),
                'passed' => false
            ];
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test 2: CRM Guidance Data
     */
    private function testCrmGuidance() {
        echo "\n🎯 Test 2: Kontrola CRM guidance dát...\n";
        
        try {
            // Kontrola či CRM má guidance v databáze
            $stmt = $this->pdo->prepare("
                SELECT 
                    ticker,
                    estimated_eps_guidance,
                    estimated_revenue_guidance,
                    fiscal_period,
                    fiscal_year,
                    release_type,
                    last_updated
                FROM benzinga_guidance 
                WHERE ticker = 'CRM'
                ORDER BY 
                    CASE WHEN release_type = 'final' THEN 1 ELSE 2 END,
                    last_updated DESC
                LIMIT 1
            ");
            $stmt->execute();
            $crmGuidance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Kontrola či CRM sa zobrazuje v API
            $response = file_get_contents($this->apiUrl);
            $data = json_decode($response, true);
            $crmInApi = false;
            $crmHasGuidance = false;
            
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $ticker) {
                    if ($ticker['ticker'] === 'CRM') {
                        $crmInApi = true;
                        $crmHasGuidance = !empty($ticker['eps_guide']) || !empty($ticker['revenue_guide']);
                        break;
                    }
                }
            }
            
            $this->testResults['crm_guidance'] = [
                'crm_in_database' => !empty($crmGuidance),
                'crm_in_api' => $crmInApi,
                'crm_has_guidance_in_api' => $crmHasGuidance,
                'crm_guidance_data' => $crmGuidance,
                'passed' => ($crmInApi && $crmHasGuidance)
            ];
            
            if ($this->testResults['crm_guidance']['passed']) {
                echo "   ✅ CRM sa zobrazuje v API s guidance dátami\n";
                if (!empty($crmGuidance)) {
                    echo "   📊 Guidance: {$crmGuidance['fiscal_period']}/{$crmGuidance['fiscal_year']}\n";
                }
            } else {
                echo "   ❌ CRM sa nezobrazuje správne v API\n";
                if (!empty($crmGuidance)) {
                    echo "   📊 CRM má guidance v DB: {$crmGuidance['fiscal_period']}/{$crmGuidance['fiscal_year']}\n";
                }
            }
            
        } catch (Exception $e) {
            $this->testResults['crm_guidance'] = [
                'error' => $e->getMessage(),
                'passed' => false
            ];
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test 3: No Grouped Tickers
     */
    private function testNoGroupedTickers() {
        echo "\n🔍 Test 3: Kontrola, či sa tickery nezobrazujú v skupinách...\n";
        
        try {
            $response = file_get_contents($this->apiUrl);
            $data = json_decode($response, true);
            
            if (!isset($data['data']) || !is_array($data['data'])) {
                throw new Exception("Neplatné API dáta");
            }
            
            // Kontrola duplicitných tickerov
            $tickers = [];
            $duplicates = [];
            
            foreach ($data['data'] as $ticker) {
                $tickerSymbol = $ticker['ticker'];
                if (isset($tickers[$tickerSymbol])) {
                    $duplicates[] = $tickerSymbol;
                } else {
                    $tickers[$tickerSymbol] = 1;
                }
            }
            
            $hasDuplicates = !empty($duplicates);
            $uniqueCount = count($tickers);
            $totalCount = count($data['data']);
            
            $this->testResults['no_grouped_tickers'] = [
                'has_duplicates' => $hasDuplicates,
                'duplicate_tickers' => $duplicates,
                'unique_count' => $uniqueCount,
                'total_count' => $totalCount,
                'passed' => (!$hasDuplicates && $uniqueCount == $totalCount)
            ];
            
            if ($this->testResults['no_grouped_tickers']['passed']) {
                echo "   ✅ Žiadne duplicitné tickery - každý sa zobrazuje len raz\n";
                echo "   📊 Unikátnych tickerov: {$uniqueCount}\n";
            } else {
                echo "   ❌ Nájdené duplicitné tickery: " . implode(', ', $duplicates) . "\n";
                echo "   📊 Unikátnych: {$uniqueCount}, Celkovo: {$totalCount}\n";
            }
            
        } catch (Exception $e) {
            $this->testResults['no_grouped_tickers'] = [
                'error' => $e->getMessage(),
                'passed' => false
            ];
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test 4: JOIN Logic
     */
    private function testJoinLogic() {
        echo "\n🔗 Test 4: Kontrola JOIN logiky (earnings → guidance)...\n";
        
        try {
            // Test aktuálnej JOIN logiky z API
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.ticker,
                    e.eps_estimate,
                    e.revenue_estimate,
                    t.company_name,
                    t.market_cap,
                    g.estimated_eps_guidance as eps_guide,
                    g.estimated_revenue_guidance as revenue_guide
                FROM EarningsTickersToday e
                LEFT JOIN TodayEarningsMovements t ON e.ticker = t.ticker
                LEFT JOIN (
                    SELECT 
                        ticker COLLATE utf8mb4_general_ci as ticker,
                        estimated_eps_guidance,
                        estimated_revenue_guidance,
                        ROW_NUMBER() OVER (PARTITION BY ticker ORDER BY 
                            CASE WHEN release_type = 'final' THEN 1 ELSE 2 END,
                            last_updated DESC
                        ) as rn
                    FROM benzinga_guidance g1
                    WHERE g1.fiscal_period IN ('Q1','Q2','Q3','Q4','FY','2H','3Q','1H','4Q')
                    AND g1.fiscal_year IN (2024, 2025, 2026)
                    AND (
                        (g1.estimated_eps_guidance != '' AND g1.estimated_eps_guidance IS NOT NULL)
                        OR 
                        (g1.estimated_revenue_guidance != '' AND g1.estimated_revenue_guidance IS NOT NULL)
                    )
                ) g ON e.ticker = g.ticker AND g.rn = 1
                WHERE e.report_date = ?
                ORDER BY e.ticker
                LIMIT 5
            ");
            
            $today = date('Y-m-d');
            $stmt->execute([$today]);
            $joinResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Kontrola či JOIN funguje
            $joinWorks = !empty($joinResults);
            $hasGuidance = false;
            
            if ($joinWorks) {
                foreach ($joinResults as $result) {
                    if (!empty($result['eps_guide']) || !empty($result['revenue_guide'])) {
                        $hasGuidance = true;
                        break;
                    }
                }
            }
            
            $this->testResults['join_logic'] = [
                'join_works' => $joinWorks,
                'has_guidance_data' => $hasGuidance,
                'sample_results' => $joinResults,
                'passed' => ($joinWorks && $hasGuidance)
            ];
            
            if ($this->testResults['join_logic']['passed']) {
                echo "   ✅ JOIN logika funguje správne\n";
                echo "   ✅ Nájdené guidance dáta\n";
                echo "   📊 Vzorové výsledky: " . count($joinResults) . " tickerov\n";
            } else {
                echo "   ❌ JOIN logika nefunguje správne\n";
                if ($joinWorks) {
                    echo "   📊 JOIN funguje, ale žiadne guidance dáta\n";
                } else {
                    echo "   📊 JOIN nefunguje\n";
                }
            }
            
        } catch (Exception $e) {
            $this->testResults['join_logic'] = [
                'error' => $e->getMessage(),
                'passed' => false
            ];
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Výpis výsledkov
     */
    private function printResults() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📋 VÝSLEDKY BENZINGA ISSUES RESOLUTION TESTOV\n";
        echo str_repeat("=", 60) . "\n";
        
        $totalTests = count($this->testResults);
        $passedTests = 0;
        
        foreach ($this->testResults as $testName => $result) {
            $status = $result['passed'] ? "✅ PASS" : "❌ FAIL";
            echo sprintf("%-25s %s\n", ucfirst(str_replace('_', ' ', $testName)), $status);
            
            if ($result['passed']) {
                $passedTests++;
            }
        }
        
        echo str_repeat("=", 60) . "\n";
        echo "📊 CELKOVÝ VÝSLEDOK: {$passedTests}/{$totalTests} testov prešlo\n";
        
        if ($passedTests == $totalTests) {
            echo "🎉 VŠETKY TESTS PREŠLI! Všetky problémy sú vyriešené.\n";
        } else {
            echo "⚠️  Niektoré testy zlyhali. Skontrolujte výsledky vyššie.\n";
            echo "\n💡 TIP: Ak zlyhá Ticker Count, skontrolujte API JOIN logiku.\n";
            echo "💡 TIP: Ak zlyhá CRM Guidance, skontrolujte guidance filter.\n";
            echo "💡 TIP: Ak zlyhá No Grouped Tickers, skontrolujte ROW_NUMBER().\n";
        }
        
        echo str_repeat("=", 60) . "\n";
    }
}

// Spusti test ak je súbor spustený priamo
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $test = new BenzingaIssuesTest($pdo);
        $results = $test->runAllTests();
        
        // Return exit code pre CI/CD
        $allPassed = true;
        foreach ($results as $result) {
            if (!$result['passed']) {
                $allPassed = false;
                break;
            }
        }
        
        exit($allPassed ? 0 : 1);
        
    } catch (Exception $e) {
        echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
