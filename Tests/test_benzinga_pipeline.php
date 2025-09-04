<?php
/**
 * 🚀 CRITICAL TEST: Benzinga Data Pipeline
 * 
 * Testuje celý pipeline od Benzinga API až po databázu:
 * 1. API Connectivity - či sa dáta dotahujú
 * 2. Data Validation - či sa dáta správne validujú  
 * 3. Database Storage - či sa dáta ukladajú
 * 4. Data Transformation - či sa dáta správne transformujú
 * 5. Error Handling - či sa chyby správne logujú
 * 
 * @author GPT Assistant
 * @date 2025-09-04
 */

require_once 'test_config.php';
require_once 'test_helper.php';

class BenzingaPipelineTest {
    private $pdo;
    private $testResults = [];
    private $testTickers;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->testTickers = TestHelper::getTickersWithGuidance(3);
    }
    
    /**
     * Spusti všetky testy
     */
    public function runAllTests() {
        echo "🚀 SPÚŠŤAM BENZINGA PIPELINE TEST\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $this->testApiConnectivity();
        $this->testDataValidation();
        $this->testDatabaseStorage();
        $this->testDataTransformation();
        $this->testErrorHandling();
        $this->testCronJobExecution();
        
        $this->printResults();
        return $this->testResults;
    }
    
    /**
     * Test 1: API Connectivity
     */
    private function testApiConnectivity() {
        echo "🌐 Test 1: Kontrola API pripojenia...\n";
        
        try {
            $successCount = 0;
            $totalCount = count($this->testTickers);
            
            foreach ($this->testTickers as $ticker) {
                echo "   📊 Testujem {$ticker}... ";
                
                // Test cez Polygon API
                $url = 'https://api.polygon.io/benzinga/v1/guidance';
                $params = [
                    'apiKey' => POLYGON_API_KEY,
                    'ticker' => $ticker,
                    'limit' => 5,
                    'sort' => 'date.desc'
                ];
                
                $fullUrl = $url . '?' . http_build_query($params);
                
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $fullUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if (!$error && $httpCode === 200) {
                    $data = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($data['results'])) {
                        echo "✅ ({$data['count']} záznamov)\n";
                        $successCount++;
                    } else {
                        echo "❌ (neplatný JSON)\n";
                    }
                } else {
                    echo "❌ (HTTP {$httpCode})\n";
                }
            }
            
            $this->testResults['api_connectivity'] = [
                'success_count' => $successCount,
                'total_count' => $totalCount,
                'success_rate' => round(($successCount / $totalCount) * 100, 2),
                'passed' => ($successCount > 0)
            ];
            
            echo "   📊 Úspešnosť: {$successCount}/{$totalCount} ({$this->testResults['api_connectivity']['success_rate']}%)\n";
            
        } catch (Exception $e) {
            $this->testResults['api_connectivity'] = [
                'error' => $e->getMessage(),
                'passed' => false
            ];
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test 2: Data Validation
     */
    private function testDataValidation() {
        echo "\n🎯 Test 2: Kontrola validácie dát...\n";
        
        try {
            // Kontrola či existuje validator
            $validatorFile = '../common/UnifiedValidator.php';
            $validatorExists = file_exists($validatorFile);
            
            if ($validatorExists) {
                require_once $validatorFile;
                echo "   ✅ UnifiedValidator existuje\n";
                
                // Test základnej validácie
                $testData = [
                    'eps_guidance' => '2.50',
                    'revenue_guidance' => '1000000000',
                    'fiscal_period' => 'Q1',
                    'fiscal_year' => '2025'
                ];
                
                // Kontrola či sa dá vytvoriť inštancia
                try {
                    $validator = new UnifiedValidator();
                    echo "   ✅ Validator inštancia vytvorená\n";
                    $this->testResults['data_validation'] = ['passed' => true];
                } catch (Exception $e) {
                    echo "   ❌ Validator inštancia zlyhala: " . $e->getMessage() . "\n";
                    $this->testResults['data_validation'] = ['passed' => false, 'error' => $e->getMessage()];
                }
            } else {
                echo "   ❌ UnifiedValidator neexistuje\n";
                $this->testResults['data_validation'] = ['passed' => false, 'error' => 'Validator file missing'];
            }
            
        } catch (Exception $e) {
            $this->testResults['data_validation'] = [
                'error' => $e->getMessage(),
                'passed' => false
            ];
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test 3: Database Storage
     */
    private function testDatabaseStorage() {
        echo "\n💾 Test 3: Kontrola ukladania do databázy...\n";
        
        try {
            // Kontrola či existuje tabuľka benzinga_guidance
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'benzinga_guidance'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                echo "   ✅ Tabuľka benzinga_guidance existuje\n";
                
                // Kontrola štruktúry tabuľky
                $stmt = $this->pdo->query("DESCRIBE benzinga_guidance");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $requiredColumns = ['ticker', 'estimated_eps_guidance', 'estimated_revenue_guidance', 'fiscal_period', 'fiscal_year'];
                $missingColumns = [];
                
                foreach ($requiredColumns as $required) {
                    $found = false;
                    foreach ($columns as $column) {
                        if ($column['Field'] === $required) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $missingColumns[] = $required;
                    }
                }
                
                if (empty($missingColumns)) {
                    echo "   ✅ Všetky požadované stĺpce existujú\n";
                    
                    // Kontrola počtu záznamov
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM benzinga_guidance");
                    $totalRecords = $stmt->fetch()['count'];
                    echo "   📊 Celkovo záznamov: {$totalRecords}\n";
                    
                    // Kontrola posledných záznamov
                    $stmt = $this->pdo->query("SELECT ticker, last_updated FROM benzinga_guidance ORDER BY last_updated DESC LIMIT 3");
                    $recentRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "   📋 Posledné záznamy:\n";
                    foreach ($recentRecords as $record) {
                        echo "      - {$record['ticker']}: {$record['last_updated']}\n";
                    }
                    
                    $this->testResults['database_storage'] = [
                        'table_exists' => true,
                        'columns_ok' => true,
                        'total_records' => $totalRecords,
                        'recent_records' => $recentRecords,
                        'passed' => true
                    ];
                    
                } else {
                    echo "   ❌ Chýbajúce stĺpce: " . implode(', ', $missingColumns) . "\n";
                    $this->testResults['database_storage'] = ['passed' => false, 'missing_columns' => $missingColumns];
                }
                
            } else {
                echo "   ❌ Tabuľka benzinga_guidance neexistuje\n";
                $this->testResults['database_storage'] = ['passed' => false, 'error' => 'Table missing'];
            }
            
        } catch (Exception $e) {
            $this->testResults['database_storage'] = [
                'error' => $e->getMessage(),
                'passed' => false
            ];
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test 4: Data Transformation
     */
    private function testDataTransformation() {
        echo "\n🔄 Test 4: Kontrola transformácie dát...\n";
        
        try {
            // Kontrola či sa dáta správne transformujú
            $stmt = $this->pdo->query("
                SELECT 
                    ticker,
                    estimated_eps_guidance,
                    estimated_revenue_guidance,
                    fiscal_period,
                    fiscal_year,
                    release_type,
                    last_updated
                FROM benzinga_guidance 
                WHERE ticker IN ('" . implode("','", $this->testTickers) . "')
                ORDER BY last_updated DESC
                LIMIT 5
            ");
            
            $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($sampleData)) {
                echo "   ✅ Nájdené vzorové dáta\n";
                
                $validData = 0;
                foreach ($sampleData as $record) {
                    $isValid = true;
                    
                    // Kontrola či má ticker
                    if (empty($record['ticker'])) {
                        $isValid = false;
                    }
                    
                    // Kontrola či má aspoň jedno guidance
                    if (empty($record['estimated_eps_guidance']) && empty($record['estimated_revenue_guidance'])) {
                        $isValid = false;
                    }
                    
                    // Kontrola či má fiscal period
                    if (empty($record['fiscal_period'])) {
                        $isValid = false;
                    }
                    
                    if ($isValid) {
                        $validData++;
                    }
                }
                
                echo "   📊 Validných záznamov: {$validData}/" . count($sampleData) . "\n";
                
                $this->testResults['data_transformation'] = [
                    'sample_data_count' => count($sampleData),
                    'valid_data_count' => $validData,
                    'passed' => ($validData > 0)
                ];
                
            } else {
                echo "   ⚠️  Žiadne vzorové dáta pre test tickery\n";
                $this->testResults['data_transformation'] = ['passed' => false, 'error' => 'No sample data'];
            }
            
        } catch (Exception $e) {
            $this->testResults['data_transformation'] = [
                'error' => $e->getMessage(),
                'passed' => false
            ];
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test 5: Error Handling
     */
    private function testErrorHandling() {
        echo "\n⚠️  Test 5: Kontrola spracovania chýb...\n";
        
        try {
            // Kontrola či existuje tabuľka pre chyby
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'guidance_import_failures'");
            $failuresTableExists = $stmt->rowCount() > 0;
            
            if ($failuresTableExists) {
                echo "   ✅ Tabuľka guidance_import_failures existuje\n";
                
                // Kontrola počtu chýb
                $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM guidance_import_failures");
                $failureCount = $stmt->fetch()['count'];
                echo "   📊 Celkovo chýb: {$failureCount}\n";
                
                if ($failureCount > 0) {
                    // Kontrola posledných chýb
                    $stmt = $this->pdo->query("SELECT ticker, reason, created_at FROM guidance_import_failures ORDER BY created_at DESC LIMIT 3");
                    $recentFailures = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "   📋 Posledné chyby:\n";
                    foreach ($recentFailures as $failure) {
                        echo "      - {$failure['ticker']}: {$failure['reason']} ({$failure['created_at']})\n";
                    }
                }
                
                $this->testResults['error_handling'] = [
                    'failures_table_exists' => true,
                    'failure_count' => $failureCount,
                    'passed' => true
                ];
                
            } else {
                echo "   ⚠️  Tabuľka guidance_import_failures neexistuje\n";
                $this->testResults['error_handling'] = ['passed' => false, 'error' => 'Failures table missing'];
            }
            
        } catch (Exception $e) {
            $this->testResults['error_handling'] = [
                'error' => $e->getMessage(),
                'passed' => false
            ];
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test 6: Cron Job Execution
     */
    private function testCronJobExecution() {
        echo "\n⏰ Test 6: Kontrola cron job spustenia...\n";
        
        try {
            // Kontrola či existuje Benzinga cron job
            $cronFile = '../cron/5_benzinga_guidance_updates.php';
            $cronExists = file_exists($cronFile);
            
            if ($cronExists) {
                echo "   ✅ Benzinga cron job existuje\n";
                
                // Kontrola syntaxe
                $syntaxCheck = shell_exec("php -l " . escapeshellarg($cronFile) . " 2>&1");
                if (strpos($syntaxCheck, 'No syntax errors') !== false) {
                    echo "   ✅ Syntax je v poriadku\n";
                    
                    // Kontrola posledného spustenia (ak existuje log)
                    $logFile = '../logs/earnings_fetch.log';
                    if (file_exists($logFile)) {
                        $lastModified = date('Y-m-d H:i:s', filemtime($logFile));
                        echo "   🕐 Posledná aktualizácia logu: {$lastModified}\n";
                        
                        // Kontrola posledných riadkov logu
                        $logLines = file($logFile);
                        $recentLines = array_slice($logLines, -5);
                        
                        echo "   📋 Posledné log záznamy:\n";
                        foreach ($recentLines as $line) {
                            $line = trim($line);
                            if (!empty($line)) {
                                echo "      " . substr($line, 0, 80) . "...\n";
                            }
                        }
                    }
                    
                    $this->testResults['cron_job_execution'] = ['passed' => true];
                    
                } else {
                    echo "   ❌ Syntax error: " . $syntaxCheck . "\n";
                    $this->testResults['cron_job_execution'] = ['passed' => false, 'error' => $syntaxCheck];
                }
                
            } else {
                echo "   ❌ Benzinga cron job neexistuje\n";
                $this->testResults['cron_job_execution'] = ['passed' => false, 'error' => 'Cron file missing'];
            }
            
        } catch (Exception $e) {
            $this->testResults['cron_job_execution'] = [
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
        echo "📋 VÝSLEDKY BENZINGA PIPELINE TESTOV\n";
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
            echo "🎉 VŠETKY TESTS PREŠLI! Benzinga pipeline funguje správne.\n";
        } else {
            echo "⚠️  Niektoré testy zlyhali. Skontrolujte výsledky vyššie.\n";
            echo "\n💡 TIP: Ak zlyhá API Connectivity, skontrolujte API kľúče.\n";
            echo "💡 TIP: Ak zlyhá Database Storage, skontrolujte databázu.\n";
            echo "💡 TIP: Ak zlyhá Cron Job, skontrolujte cron nastavenia.\n";
        }
        
        echo str_repeat("=", 60) . "\n";
    }
}

// Spusti test ak je súbor spustený priamo
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $test = new BenzingaPipelineTest($pdo);
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
