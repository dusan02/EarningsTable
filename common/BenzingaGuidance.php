<?php
/**
 * 🚀 BENZINGA CORPORATE GUIDANCE API
 * 
 * Trieda pre získavanie a spracovanie guidance dát z Benzinga Corporate Guidance API (cez Polygon)
 * - Fetch guidance data pre zadané tickery
 * - Mapovanie JSON polí na stĺpce tabuľky
 * - Ukladanie do benzinga_guidance tabuľky
 */

// Load config only if not already loaded
if (!defined('DB_NAME')) {
    require_once __DIR__ . '/../config.php';
}
require_once dirname(__DIR__) . '/common/UnifiedValidator.php';

class BenzingaGuidance {
    private $apiKey;
    private $baseUrl = 'https://api.polygon.io';
    private $pdo;
    private $date;
    private $timezone;
    
    public function __construct() {
        $this->apiKey = POLYGON_API_KEY ?? null;
        if (empty($this->apiKey)) {
            throw new Exception("POLYGON_API_KEY not configured in config.php");
        }
        
        $this->pdo = $GLOBALS['pdo'];
        $this->timezone = new DateTimeZone('America/New_York');
        $this->date = (new DateTime('now', $this->timezone))->format('Y-m-d');
    }
    
    /**
     * Získa guidance dáta pre zadané tickery - PARALELNÉ SPRAVANIE
     */
    public function fetchGuidanceData($tickers) {
        if (empty($tickers)) {
            echo "⚠️  No tickers provided for guidance fetch\n";
            return [];
        }
        
        echo "🔍 Fetching guidance data for " . count($tickers) . " tickers (PARALLEL)...\n";
        
        // Inicializácia curl_multi
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $tickerMap = []; // Mapovanie curl handle -> ticker
        
        // Vytvorenie curl handles pre všetky tickery
        foreach ($tickers as $ticker) {
            $url = $this->baseUrl . '/benzinga/v1/guidance';
            $params = [
                'apiKey' => $this->apiKey,
                'ticker' => $ticker,
                'limit' => 10,
                'sort' => 'date.desc'
            ];
            
            $fullUrl = $url . '?' . http_build_query($params);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'User-Agent: EarningsTable/1.0'
                ]
            ]);
            
            $curlHandles[] = $ch;
            $tickerMap[spl_object_hash($ch)] = $ticker;
            
            // Pridanie do multi handle
            curl_multi_add_handle($multiHandle, $ch);
        }
        
        echo "  🚀 Launched " . count($tickers) . " parallel API calls...\n";
        
        // Spustenie paralelného spracovania
        $active = null;
        $allGuidanceData = [];
        $startTime = microtime(true);
        
        do {
            $status = curl_multi_exec($multiHandle, $active);
            if ($active) {
                curl_multi_select($multiHandle);
            }
            
            // Spracovanie dokončených requestov
            while ($info = curl_multi_info_read($multiHandle)) {
                if ($info['msg'] == CURLMSG_DONE) {
                    $ch = $info['handle'];
                    $ticker = $tickerMap[spl_object_hash($ch)];
                    
                    $response = curl_multi_getcontent($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    
                    if ($error) {
                        echo "    ❌ {$ticker}: cURL error - " . $error . "\n";
                    } elseif ($httpCode !== 200) {
                        echo "    ❌ {$ticker}: HTTP {$httpCode}\n";
                    } else {
                        $data = json_decode($response, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($data['results'])) {
                            $guidanceCount = count($data['results']);
                            if ($guidanceCount > 0) {
                                echo "    ✅ {$ticker}: {$guidanceCount} guidance records\n";
                                // Procesovanie každého guidance záznamu
                                foreach ($data['results'] as $guidance) {
                                    $processed = $this->processGuidanceRecord($guidance);
                                    if ($processed) {
                                        $allGuidanceData[] = $processed;
                                    }
                                }
                            } else {
                                echo "    ⚠️  {$ticker}: No guidance data\n";
                            }
                        } else {
                            echo "    ❌ {$ticker}: Invalid JSON response\n";
                        }
                    }
                    
                    // Odstránenie handle z multi
                    curl_multi_remove_handle($multiHandle, $ch);
                    curl_close($ch);
                }
            }
        } while ($active && $status == CURLM_OK);
        
        // Cleanup
        curl_multi_close($multiHandle);
        
        $totalTime = round(microtime(true) - $startTime, 2);
        echo "✅ Parallel guidance fetch completed in {$totalTime}s\n";
        echo "📊 Total guidance records: " . count($allGuidanceData) . "\n";
        
        return $allGuidanceData;
    }
    

    
    /**
     * Spracuje jeden guidance záznam a mapuje ho na stĺpce tabuľky
     */
    private function processGuidanceRecord($guidance) {
        // Mapovanie Benzinga Corporate Guidance API JSON polí na stĺpce tabuľky
        $mapped = [
            'ticker' => $guidance['ticker'] ?? null,
            'company_name' => $guidance['company_name'] ?? null,
            'date' => $guidance['date'] ?? $this->date,
            'time' => $guidance['time'] ?? null,
            'fiscal_period' => $guidance['fiscal_period'] ?? null,
            'fiscal_year' => $this->parseNumeric($guidance['fiscal_year'] ?? null),
            'release_type' => $guidance['release_type'] ?? 'official',
            'positioning' => $guidance['positioning'] ?? 'primary',
            'importance' => $this->parseNumeric($guidance['importance'] ?? 1),
            
            // EPS Guidance s normalizáciou
            'estimated_eps_guidance' => $this->parseNumeric($guidance['estimated_eps_guidance'] ?? null),
            'min_eps_guidance' => $this->parseNumeric($guidance['min_eps_guidance'] ?? null),
            'max_eps_guidance' => $this->parseNumeric($guidance['max_eps_guidance'] ?? null),
            'eps_method' => $guidance['eps_method'] ?? 'gaap',
            
            // Revenue Guidance s normalizáciou jednotiek
            'estimated_revenue_guidance' => UnifiedValidator::normalizeRevenueUnits($guidance['estimated_revenue_guidance'] ?? null),
            'min_revenue_guidance' => UnifiedValidator::normalizeRevenueUnits($guidance['min_revenue_guidance'] ?? null),
            'max_revenue_guidance' => UnifiedValidator::normalizeRevenueUnits($guidance['max_revenue_guidance'] ?? null),
            'revenue_method' => $guidance['revenue_method'] ?? 'gaap',
            
            // Previous guidance
            'previous_min_eps_guidance' => $this->parseNumeric($guidance['previous_min_eps_guidance'] ?? null),
            'previous_max_eps_guidance' => $this->parseNumeric($guidance['previous_max_eps_guidance'] ?? null),
            'previous_min_revenue_guidance' => UnifiedValidator::normalizeRevenueUnits($guidance['previous_min_revenue_guidance'] ?? null),
            'previous_max_revenue_guidance' => UnifiedValidator::normalizeRevenueUnits($guidance['previous_max_revenue_guidance'] ?? null),
            
            // Consensus comparison
            'eps_guide_vs_consensus_pct' => $this->calculateEpsGuideVsConsensus($guidance),
            'revenue_guide_vs_consensus_pct' => $this->calculateRevenueGuideVsConsensus($guidance),
            
            // Metadata
            'currency' => $guidance['currency'] ?? 'USD',
            'notes' => $guidance['notes'] ?? null,
            'benzinga_id' => $guidance['id'] ?? null,
            'last_updated' => $guidance['last_updated'] ?? date('Y-m-d H:i:s'),
        ];
        
        // Validácia - musí mať aspoň ticker
        if (empty($mapped['ticker'])) {
            echo "      ⚠️  Skipping guidance without ticker\n";
            return null;
        }
        
        return $mapped;
    }
    
    /**
     * Parsuje numerické hodnoty
     */
    private function parseNumeric($value) {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (is_numeric($value)) {
            return $value;
        }
        
        // Skús vyčistiť string hodnoty
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($cleaned) ? $cleaned : null;
    }
    

    
    /**
     * Vypočíta rozdiel % medzi guidovaným EPS a konsenzom
     */
    private function calculateEpsGuideVsConsensus($guidance) {
        $epsGuide = $guidance['estimated_eps_guidance'] ?? null;
        $ticker = $guidance['ticker'] ?? null;
        
        if ($epsGuide && $ticker) {
            // Získaj analyst estimates z EarningsTickersToday
            $epsConsensus = $this->getEpsEstimateFromEarningsTickersToday($ticker);
            
            if ($epsConsensus && $epsConsensus != 0) {
                $difference = (($epsGuide - $epsConsensus) / abs($epsConsensus)) * 100;
                return round($difference, 4);
            }
        }
        
        return null;
    }
    
    /**
     * Vypočíta rozdiel % medzi guidovanými tržbami a konsenzom
     */
    private function calculateRevenueGuideVsConsensus($guidance) {
        $revenueGuide = $guidance['estimated_revenue_guidance'] ?? null;
        $ticker = $guidance['ticker'] ?? null;
        
        if ($revenueGuide && $ticker) {
            // Získaj analyst estimates z EarningsTickersToday
            $revenueConsensus = $this->getRevenueEstimateFromEarningsTickersToday($ticker);
            
            if ($revenueConsensus && $revenueConsensus != 0) {
                $difference = (($revenueGuide - $revenueConsensus) / abs($revenueConsensus)) * 100;
                return round($difference, 4);
            }
        }
        
        return null;
    }
    
    /**
     * Uloží guidance dáta do databázy - OPTIMALIZOVANÉ s batch INSERT
     */
    public function saveGuidanceData($guidanceData) {
        if (empty($guidanceData)) {
            echo "⚠️  No guidance data to save\n";
            return 0;
        }
        
        echo "💾 Saving " . count($guidanceData) . " guidance records using batch operation...\n";
        
        // Validate all data first
        $validData = [];
        $errors = 0;
        
        foreach ($guidanceData as $guidance) {
            try {
                $validationIssues = $this->validateGuidanceData($guidance);
                if (empty($validationIssues)) {
                    $validData[] = $guidance;
                } else {
                    echo "    ⚠️  Validation issues for {$guidance['ticker']}:\n";
                    foreach ($validationIssues as $issue) {
                        echo "       - {$issue}\n";
                    }
                    echo "    🚫 Skipping insertion due to validation issues\n";
                    
                    // Log rejected guidance to failures table
                    $this->logGuidanceFailure($guidance, implode('; ', $validationIssues));
                    $errors++;
                }
            } catch (Exception $e) {
                echo "    ❌ Failed to validate {$guidance['ticker']}: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
        
        if (empty($validData)) {
            echo "❌ No valid guidance data to save\n";
            return 0;
        }
        
        // Execute batch INSERT
        $savedCount = $this->executeBatchInsert($validData);
        
        echo "✅ Guidance data save completed: {$savedCount} saved, {$errors} errors\n";
        return $savedCount;
    }
    
    /**
     * Vykoná batch INSERT pre všetky guidance záznamy
     */
    private function executeBatchInsert($validData) {
        if (empty($validData)) return 0;
        
        global $pdo;
        
        // Build batch INSERT SQL - používať len stĺpce, ktoré existujú v tabuľke
        $columns = [
            'ticker', 'fiscal_period', 'fiscal_year', 'importance',
            'estimated_eps_guidance', 'min_eps_guidance', 'max_eps_guidance',
            'estimated_revenue_guidance', 'min_revenue_guidance', 'max_revenue_guidance',
            'notes', 'previous_min_eps_guidance', 'previous_max_eps_guidance',
            'previous_min_revenue_guidance', 'previous_max_revenue_guidance',
            'eps_guide_vs_consensus_pct', 'revenue_guide_vs_consensus_pct'
        ];
        
        $placeholders = [];
        $params = [];
        
        foreach ($validData as $guidance) {
            $rowPlaceholders = [];
            foreach ($columns as $column) {
                $rowPlaceholders[] = '?';
                $params[] = $guidance[$column] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }
        
        $sql = "INSERT INTO benzinga_guidance (" . implode(', ', $columns) . ") VALUES " . implode(', ', $placeholders) . "
                ON DUPLICATE KEY UPDATE
                importance = VALUES(importance),
                estimated_eps_guidance = VALUES(estimated_eps_guidance),
                min_eps_guidance = VALUES(min_eps_guidance),
                max_eps_guidance = VALUES(max_eps_guidance),
                estimated_revenue_guidance = VALUES(estimated_revenue_guidance),
                min_revenue_guidance = VALUES(min_revenue_guidance),
                max_revenue_guidance = VALUES(max_revenue_guidance),
                previous_min_eps_guidance = VALUES(previous_min_eps_guidance),
                previous_max_eps_guidance = VALUES(previous_max_eps_guidance),
                previous_min_revenue_guidance = VALUES(previous_min_revenue_guidance),
                previous_max_revenue_guidance = VALUES(previous_max_revenue_guidance),
                eps_guide_vs_consensus_pct = VALUES(eps_guide_vs_consensus_pct),
                revenue_guide_vs_consensus_pct = VALUES(revenue_guide_vs_consensus_pct),
                notes = VALUES(notes)";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $affectedRows = $stmt->rowCount();
            echo "  🚀 Batch INSERT executed with " . count($params) . " parameters\n";
            echo "  📊 Affected rows: {$affectedRows}\n";
            
            return $affectedRows;
            
        } catch (Exception $e) {
            echo "  ❌ Batch INSERT failed: " . $e->getMessage() . "\n";
            // Fallback to individual inserts if batch fails
            return $this->fallbackIndividualInserts($validData);
        }
    }
    
    /**
     * Fallback na individuálne INSERT ak batch zlyhá
     */
    private function fallbackIndividualInserts($validData) {
        echo "  🔄 Falling back to individual inserts...\n";
        
        $savedCount = 0;
        foreach ($validData as $guidance) {
            try {
                if ($this->saveGuidanceRecord($guidance)) {
                    $savedCount++;
                }
            } catch (Exception $e) {
                echo "    ❌ Failed to save {$guidance['ticker']}: " . $e->getMessage() . "\n";
            }
        }
        
        return $savedCount;
    }
    
    /**
     * Validuje guidance dáta pred vložením do databázy
     * Používa UnifiedValidator v lenient móde - bez hard dropov
     */
    private function validateGuidanceData($guidance) {
        // Použiť novú lenient validáciu
        [$ok, $result] = UnifiedValidator::validateGuidance($guidance, [
            'mode' => 'lenient',
            'tolerance' => 0.15 // 15% tolerancia
        ]);
        
        if (!$ok) {
            echo "    ❌ Hard validation failed for {$guidance['ticker']}: {$result}\n";
            return [$result]; // Hard failure
        }
        
        // Ak existujú varovania, zaloguj ako INFO, ale neodmietaj
        if (!empty($result['_warnings'])) {
            echo "    ⚠️  Soft warnings for {$guidance['ticker']}:\n";
            foreach ($result['_warnings'] as $warning) {
                echo "       - {$warning}\n";
            }
            // Log warning do failures table pre tracking
            $this->logGuidanceFailure($guidance, 'Soft warnings: ' . implode('; ', $result['_warnings']));
        }
        
        // Update guidance data with normalized values
        $guidance = array_merge($guidance, $result);
        unset($guidance['_warnings']); // Remove internal warnings
        
        return []; // No validation issues - proceed with insert
    }
    
    /**
     * Uloží jeden guidance záznam s validáciou
     */
    private function saveGuidanceRecord($guidance) {
        // Validácia dát pred vložením
        $validationIssues = $this->validateGuidanceData($guidance);
        
        if (!empty($validationIssues)) {
            echo "    ⚠️  Validation issues for {$guidance['ticker']}:\n";
            foreach ($validationIssues as $issue) {
                echo "       - {$issue}\n";
            }
            echo "    🚫 Skipping insertion due to validation issues\n";
            return false;
        }
        
        $sql = "INSERT INTO benzinga_guidance (
            ticker, estimated_eps_guidance, estimated_revenue_guidance,
            fiscal_period, fiscal_year, importance, max_eps_guidance,
            max_revenue_guidance, min_eps_guidance, min_revenue_guidance,
            notes, previous_max_eps_guidance, previous_max_revenue_guidance,
            previous_min_eps_guidance, previous_min_revenue_guidance,
            eps_guide_vs_consensus_pct, revenue_guide_vs_consensus_pct
        ) VALUES (
            :ticker, :estimated_eps_guidance, :estimated_revenue_guidance,
            :fiscal_period, :fiscal_year, :importance, :max_eps_guidance,
            :max_revenue_guidance, :min_eps_guidance, :min_revenue_guidance,
            :notes, :previous_max_eps_guidance, :previous_max_revenue_guidance,
            :previous_min_eps_guidance, :previous_min_revenue_guidance,
            :eps_guide_vs_consensus_pct, :revenue_guide_vs_consensus_pct
        ) ON DUPLICATE KEY UPDATE
            estimated_eps_guidance = VALUES(estimated_eps_guidance),
            estimated_revenue_guidance = VALUES(estimated_revenue_guidance),
            fiscal_period = VALUES(fiscal_period),
            fiscal_year = VALUES(fiscal_year),
            importance = VALUES(importance),
            max_eps_guidance = VALUES(max_eps_guidance),
            max_revenue_guidance = VALUES(max_revenue_guidance),
            min_eps_guidance = VALUES(min_eps_guidance),
            min_revenue_guidance = VALUES(min_revenue_guidance),
            notes = VALUES(notes),
            previous_max_eps_guidance = VALUES(previous_max_eps_guidance),
            previous_max_revenue_guidance = VALUES(previous_max_revenue_guidance),
            previous_min_eps_guidance = VALUES(previous_min_eps_guidance),
            previous_min_revenue_guidance = VALUES(previous_min_revenue_guidance),
            eps_guide_vs_consensus_pct = VALUES(eps_guide_vs_consensus_pct),
            revenue_guide_vs_consensus_pct = VALUES(revenue_guide_vs_consensus_pct),
            updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($guidance);
        return true;
    }
    
    /**
     * Loguje odmietnuté guidance dáta do failures tabuľky
     */
    private function logGuidanceFailure($guidance, $reason) {
        try {
            global $pdo;
            
            $sql = "INSERT INTO guidance_import_failures (ticker, payload, reason) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $guidance['ticker'] ?? 'UNKNOWN',
                json_encode($guidance),
                $reason
            ]);
            
            echo "      📝 Logged failure for {$guidance['ticker']}: {$reason}\n";
            
        } catch (Exception $e) {
            echo "      ⚠️  Failed to log guidance failure: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Získa tickery zo statického cronu (earnings calendar)
     */
    public function getTickersFromStaticCron() {
        $sql = "SELECT ticker FROM earningstickerstoday WHERE report_date = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->date]);
        
        $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "📊 Found " . count($tickers) . " tickers from static cron for date: {$this->date}\n";
        
        return $tickers;
    }
    
    /**
     * Hlavná metóda pre spustenie celého procesu
     */
    public function run() {
        echo "🚀 BENZINGA CORPORATE GUIDANCE PROCESS STARTED\n";
        echo "📅 Date: {$this->date}\n";
        echo "⏰ Time: " . (new DateTime('now', $this->timezone))->format('H:i:s') . " NY\n\n";
        
        try {
            // 1. Získa tickery zo statického cronu
            $tickers = $this->getTickersFromStaticCron();
            if (empty($tickers)) {
                echo "⚠️  No tickers found from static cron\n";
                return;
            }
            
            // 2. Získa guidance dáta z Benzinga Corporate Guidance API
            $guidanceData = $this->fetchGuidanceData($tickers);
            
            // 3. Uloží dáta do databázy
            if (!empty($guidanceData)) {
                $this->saveGuidanceData($guidanceData);
            }
            
            echo "\n✅ BENZINGA CORPORATE GUIDANCE PROCESS COMPLETED SUCCESSFULLY!\n";
            echo "🎯 Guidance data updated for " . count($tickers) . " tickers!\n";
            
        } catch (Exception $e) {
            echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Získaj EPS estimate z EarningsTickersToday tabuľky
     */
    private function getEpsEstimateFromEarningsTickersToday($ticker) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT eps_estimate 
                FROM earningstickerstoday 
                WHERE ticker = ? AND report_date = ?
            ");
            $stmt->execute([$ticker, $this->date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['eps_estimate'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Získaj Revenue estimate z EarningsTickersToday tabuľky
     */
    private function getRevenueEstimateFromEarningsTickersToday($ticker) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT revenue_estimate 
                FROM earningstickerstoday 
                WHERE ticker = ? AND report_date = ?
            ");
            $stmt->execute([$ticker, $this->date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['revenue_estimate'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
}
?>
