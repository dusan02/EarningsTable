<?php
/**
 * 🚀 DAILY DATA SETUP - STATIC
 * 
 * Trieda pre spracovanie denných statických dát
 * - Finnhub: EPS/Revenue estimates, Report time
 * - Polygon: Market cap, Company name, Shares outstanding, Previous close
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Finnhub.php';
require_once __DIR__ . '/api_functions.php';
require_once __DIR__ . '/HistoricalDataManager.php';

class DailyDataSetup {
    private $date;
    private $timezone;
    private $startTime;
    private $metrics;
    
    // Data storage
    private $todayTickers = [];
    private $finnhubStaticData = [];
    private $polygonBatchData = [];
    private $polygonDetailsData = [];
    private $processedData = [];
    
    // Performance tracking
    private $phaseTimes = [];
    
    public function __construct() {
        $this->timezone = new DateTimeZone('America/New_York');
        $this->date = (new DateTime('now', $this->timezone))->format('Y-m-d');
        $this->startTime = microtime(true);
        $this->metrics = new MetricsCollector();
    }
    
    /**
     * Hlavná metóda pre spustenie celého procesu
     */
    public function run() {
        echo "🚀 DAILY DATA SETUP - STATIC STARTED\n";
        echo "📅 Date: {$this->date}\n";
        echo "⏰ Time: " . (new DateTime('now', $this->timezone))->format('H:i:s') . " NY\n\n";
        
        try {
            $this->discovery();
            $this->dataFetching();
            $this->dataProcessing();
            $this->databaseSaving();
            $this->finalSummary();
            
            echo "\n✅ DAILY DATA SETUP - STATIC COMPLETED SUCCESSFULLY!\n";
            echo "🎯 All static data is now ready for 5-minute dynamic updates!\n";
            echo "📈 Ready to process " . count($this->processedData) . " tickers for dynamic updates!\n";
            
        } catch (Exception $e) {
            echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Fáza 1: Discovery - Získanie tickerov ktoré reportujú dnes
     */
    private function discovery() {
        $phaseStart = microtime(true);
        echo "=== PHASE 1: DISCOVERY - GETTING TODAY'S EARNINGS TICKERS ===\n";
        
        try {
            $finnhub = new Finnhub();
            $response = $finnhub->getEarningsCalendar('', $this->date, $this->date);
            $earningsCalendar = $response['earningsCalendar'] ?? [];
            
            if (empty($earningsCalendar)) {
                throw new Exception("No earnings found for today ({$this->date})");
            }
            
            // Process each earning and extract ticker + static data
            foreach ($earningsCalendar as $earning) {
                $ticker = $earning['symbol'] ?? '';
                if (empty($ticker)) {
                    echo "⚠️  Skipping earning with empty symbol\n";
                    continue;
                }
                
                $this->todayTickers[] = $ticker;
                $this->finnhubStaticData[$ticker] = [
                    'eps_estimate' => $earning['epsEstimate'] ?? null,
                    'revenue_estimate' => $earning['revenueEstimate'] ?? null,
                    'report_time' => $earning['time'] ?? 'TNS'
                ];
            }
            
            echo "✅ Discovery completed: " . count($this->todayTickers) . " tickers found\n";
            echo "📊 Tickers: " . implode(', ', array_slice($this->todayTickers, 0, 10));
            if (count($this->todayTickers) > 10) {
                echo " ... and " . (count($this->todayTickers) - 10) . " more";
            }
            echo "\n";
            
            $this->phaseTimes['discovery'] = round(microtime(true) - $phaseStart, 2);
            
        } catch (Exception $e) {
            throw new Exception("Discovery failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fáza 2: Data Fetching - Získanie všetkých statických dát
     */
    private function dataFetching() {
        echo "\n=== PHASE 2: DATA FETCHING ===\n";
        
        // Get Polygon batch data with retry logic
        echo "🔍 Fetching Polygon batch data for " . count($this->todayTickers) . " tickers...\n";
        
        $polygonStart = microtime(true);
        $polygonBatchData = $this->retryOperation(
            fn() => getPolygonBatchQuote($this->todayTickers),
            maxAttempts: 3,
            delay: 2000
        );
        
        if (empty($polygonBatchData)) {
            echo "⚠️  Polygon API failed, using fallback mechanism...\n";
            $this->processPolygonFallback();
        } else {
            echo "✅ Polygon batch data fetched successfully\n";
            $this->polygonBatchData = $polygonBatchData;
        }
        
        $polygonDuration = round(microtime(true) - $polygonStart, 2);
        echo "⏱️  Polygon API time: {$polygonDuration}s\n";
        
        $this->fetchPolygonDetailsData();
    }
    
    /**
     * 2.1: Získanie Polygon batch quote dát (previous close)
     */
    private function fetchPolygonBatchData() {
        $phaseStart = microtime(true);
        echo "--- 2.1: Polygon Batch Quote (Previous Close) ---\n";
        
        $this->polygonBatchData = $this->retryOperation(
            fn() => getPolygonBatchQuote($this->todayTickers),
            maxAttempts: 3,
            delay: 1000
        );
        
        if (!$this->polygonBatchData) {
            throw new Exception("Polygon batch quote failed after retries");
        }
        
        $this->phaseTimes['polygon_batch'] = round(microtime(true) - $phaseStart, 2);
        echo "✅ Polygon batch quote completed in {$this->phaseTimes['polygon_batch']}s\n";
        echo "✅ Found batch data for " . count($this->polygonBatchData) . " tickers\n";
    }
    
    /**
     * 2.2: Získanie Polygon ticker details (market cap, company info) - PARALLEL
     */
    private function fetchPolygonDetailsData() {
        $phaseStart = microtime(true);
        echo "\n--- 2.2: Polygon Ticker Details (Market Cap, Company Info) - PARALLEL ---\n";
        
        // Prepare URLs for all tickers
        $urls = [];
        foreach ($this->todayTickers as $ticker) {
            $url = POLYGON_BASE_URL . '/v3/reference/tickers/' . urlencode($ticker);
            $url .= '?apikey=' . POLYGON_API_KEY;
            $urls[$ticker] = $url;
        }
        
        echo "  🔄 Executing " . count($urls) . " parallel requests...\n";
        
        // Execute parallel requests with better error handling
        $results = executeParallelRequests($urls);
        
        // Process results
        $successfulDetails = 0;
        $failedDetails = 0;
        
        foreach ($results as $ticker => $result) {
            if ($result['success']) {
                $data = json_decode($result['response'], true);
                if (isset($data['results'])) {
                    $this->polygonDetailsData[$ticker] = $data['results'];
                    $successfulDetails++;
                } else {
                    $failedDetails++;
                    echo "  ⚠️  No results for {$ticker}\n";
                }
            } else {
                $failedDetails++;
                echo "  ❌ Failed to get details for {$ticker}: " . $result['error'] . "\n";
            }
        }
        
        $this->phaseTimes['polygon_details'] = round(microtime(true) - $phaseStart, 2);
        echo "✅ Polygon ticker details completed in {$this->phaseTimes['polygon_details']}s (PARALLEL)\n";
        echo "✅ Successful: {$successfulDetails}, Failed: {$failedDetails}\n";
        
        // If too many failed, use fallback
        if ($failedDetails > count($this->todayTickers) * 0.5) { // More than 50% failed
            echo "⚠️  High failure rate, using fallback for failed tickers...\n";
            $this->processDetailsFallback();
        }
    }
    
    /**
     * Fallback mechanism keď Polygon API zlyhá
     */
    private function processPolygonFallback() {
        echo "🔄 Processing Polygon fallback data...\n";
        
        // Create empty data structures for fallback
        $this->polygonBatchData = [];
        $this->polygonDetailsData = [];
        
        // For each ticker, create minimal fallback data
        foreach ($this->todayTickers as $ticker) {
            // Fallback batch data (minimal)
            $this->polygonBatchData[$ticker] = [
                'prevDay' => ['c' => null], // Will be filled from database later
                'lastTrade' => ['p' => null, 't' => null],
                'lastQuote' => ['bp' => null, 'ap' => null],
                'min' => ['c' => null],
                'session' => ['c' => null]
            ];
            
            // Fallback details data (minimal)
            $this->polygonDetailsData[$ticker] = [
                'market_cap' => null,
                'name' => $ticker, // Use ticker as fallback name
                'shares_outstanding' => null
            ];
        }
        
        echo "✅ Polygon fallback data created for " . count($this->todayTickers) . " tickers\n";
        echo "⚠️  Note: Some data will be filled from database or historical sources\n";
    }

    /**
     * Fallback mechanism pre zlyhanie získavania detailov pre tickery
     */
    private function processDetailsFallback() {
        echo "🔄 Processing Polygon details fallback data...\n";
        
        // Create empty data structures for fallback
        $this->polygonDetailsData = [];
        
        // For each ticker, create minimal fallback data
        foreach ($this->todayTickers as $ticker) {
            $this->polygonDetailsData[$ticker] = [
                'market_cap' => null,
                'name' => $ticker, // Use ticker as fallback name
                'shares_outstanding' => null
            ];
        }
        
        echo "✅ Polygon details fallback data created for " . count($this->todayTickers) . " tickers\n";
        echo "⚠️  Note: Some data will be filled from database or historical sources\n";
    }
    
    /**
     * Fáza 3: Data Processing - Spracovanie dát pre databázu
     */
    private function dataProcessing() {
        $phaseStart = microtime(true);
        echo "\n=== PHASE 3: DATA PROCESSING ===\n";
        
        // Find valid tickers that have data from all sources
        $validTickers = array_intersect(
            array_keys($this->finnhubStaticData),
            array_keys($this->polygonBatchData),
            array_keys($this->polygonDetailsData)
        );
        
        $errors = [];
        
        foreach ($validTickers as $ticker) {
            $processed = $this->processTickerData($ticker);
            if ($processed) {
                $this->processedData[$ticker] = $processed;
            } else {
                $errors[] = "{$ticker}: Failed to process data";
            }
        }
        
        echo "✅ Data processing completed\n";
        echo "✅ Successfully processed: " . count($this->processedData) . " tickers\n";
        echo "❌ Errors: " . count($errors) . "\n";
        
        if (!empty($errors)) {
            echo "Error details:\n";
            foreach (array_slice($errors, 0, 5) as $error) {
                echo "  - {$error}\n";
            }
            if (count($errors) > 5) {
                echo "  ... and " . (count($errors) - 5) . " more errors\n";
            }
        }
        
        $this->phaseTimes['processing'] = round(microtime(true) - $phaseStart, 2);
    }
    
    /**
     * Spracovanie dát pre jeden ticker - OPTIMALIZOVANÉ
     */
    private function processTickerData($ticker) {
        $tickerData = [
            'ticker' => $ticker,
            'finnhub_data' => $this->finnhubStaticData[$ticker] ?? [],
            'polygon_batch' => $this->polygonBatchData[$ticker] ?? null,
            'polygon_details' => $this->polygonDetailsData[$ticker] ?? null
        ];
        
        // Validate required data
        if (!$tickerData['polygon_batch']) {
            return false;
        }
        
        // Extract and validate previous close - INTELLIGENTNÉ HĽADANIE
        $previousClose = HistoricalDataManager::findValidPreviousClose($tickerData, $ticker);
        if ($previousClose === null || $previousClose <= 0) {
            return false;
        }
        
        // Extract Polygon details
        $marketCap = null;
        $companyName = $ticker;
        $sharesOutstanding = null;
        $companyType = null;
        $primaryExchange = null;
        
        if ($tickerData['polygon_details']) {
            $details = $tickerData['polygon_details'];
            $marketCap = $details['market_cap'] ?? null;
            $companyName = $details['name'] ?? $ticker;
            $sharesOutstanding = $details['weighted_shares_outstanding'] ?? null;
            $companyType = $details['type'] ?? null;
            $primaryExchange = $details['primary_exchange'] ?? null;
        }
        
        // Determine company size based on market cap
        $size = $this->getCompanySize($marketCap);
        
        return [
            'finnhub' => [
                'eps_estimate' => $tickerData['finnhub_data']['eps_estimate'] ?? null,
                'revenue_estimate' => $tickerData['finnhub_data']['revenue_estimate'] ?? null,
                'report_time' => $tickerData['finnhub_data']['report_time'] ?? 'TNS'
            ],
            'polygon' => [
                'previous_close' => $previousClose,
                'market_cap' => $marketCap,
                'company_name' => $companyName,
                'shares_outstanding' => $sharesOutstanding,
                'company_type' => $companyType,
                'primary_exchange' => $primaryExchange,
                'size' => $size
            ]
        ];
    }
    
    /**
     * Fáza 4: Database Saving - Uloženie spracovaných dát
     */
    private function databaseSaving() {
        $phaseStart = microtime(true);
        echo "\n=== PHASE 4: DATABASE SAVING ===\n";
        
        if (empty($this->processedData)) {
            throw new Exception("No data to save");
        }
        
        try {
            $this->batchInsertData();
            $this->phaseTimes['database'] = round(microtime(true) - $phaseStart, 2);
            
        } catch (Exception $e) {
            throw new Exception("Database saving failed: " . $e->getMessage());
        }
    }
    
    /**
     * Batch INSERT pre optimalizáciu databázových operácií
     */
    private function batchInsertData() {
        global $pdo;
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            $finnhubSaved = 0;
            $polygonSaved = 0;
            
            // Use prepared statements for safety and performance
            $finnhubStmt = $pdo->prepare("
                INSERT INTO earningstickerstoday (
                    ticker, report_date, eps_estimate, revenue_estimate, 
                    report_time, data_source, source_priority
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    eps_estimate = VALUES(eps_estimate),
                    revenue_estimate = VALUES(revenue_estimate),
                    report_time = VALUES(report_time),
                    data_source = VALUES(data_source),
                    source_priority = VALUES(source_priority)
            ");
            
            $polygonStmt = $pdo->prepare("
                INSERT INTO todayearningsmovements (
                    ticker, company_name, previous_close, market_cap, size,
                    shares_outstanding, company_type, primary_exchange, report_date, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    company_name = VALUES(company_name),
                    previous_close = VALUES(previous_close),
                    market_cap = VALUES(market_cap),
                    size = VALUES(size),
                    shares_outstanding = VALUES(shares_outstanding),
                    company_type = VALUES(company_type),
                    primary_exchange = VALUES(primary_exchange),
                    report_date = VALUES(report_date),
                    updated_at = NOW()
            ");
            
            foreach ($this->processedData as $ticker => $data) {
                // Save Finnhub data
                $finnhubStmt->execute([
                    $ticker,
                    $this->date,
                    $data['finnhub']['eps_estimate'],
                    $data['finnhub']['revenue_estimate'],
                    $data['finnhub']['report_time'],
                    'finnhub',
                    1
                ]);
                $finnhubSaved++;
                
                // Save Polygon data
                $polygonStmt->execute([
                    $ticker,
                    $data['polygon']['company_name'],
                    $data['polygon']['previous_close'],
                    $data['polygon']['market_cap'],
                    $data['polygon']['size'],
                    $data['polygon']['shares_outstanding'],
                    $data['polygon']['company_type'],
                    $data['polygon']['primary_exchange'],
                    $this->date
                ]);
                $polygonSaved++;
            }
            
            // Commit transaction
            $pdo->commit();
            
            echo "✅ Database saving completed successfully\n";
            echo "✅ Finnhub data saved: {$finnhubSaved} records\n";
            echo "✅ Polygon data saved: {$polygonSaved} records\n";
            
        } catch (Exception $e) {
            // Rollback transaction
            $pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Fáza 5: Final Summary - Finálne štatistiky
     */
    private function finalSummary() {
        echo "\n=== PHASE 5: FINAL SUMMARY ===\n";
        
        $totalTime = round(microtime(true) - $this->startTime, 2);
        
        // Get final statistics
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM earningstickerstoday WHERE report_date = ?");
        $stmt->execute([$this->date]);
        $totalFinnhubRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM todayearningsmovements");
        $stmt->execute();
        $totalPolygonRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM todayearningsmovements WHERE market_cap IS NOT NULL");
        $stmt->execute();
        $marketCapRecords = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "📊 Database Records:\n";
        echo "  earningstickerstoday: {$totalFinnhubRecords} records\n";
        echo "  todayearningsmovements: {$totalPolygonRecords} records\n";
        echo "  Records with market cap: {$marketCapRecords}\n";
        
        echo "\n📊 API Performance:\n";
        echo "  Finnhub: 1 call (earnings calendar)\n";
        echo "  Polygon Batch: 1 call (previous close)\n";
        echo "  Polygon Details: " . count($this->todayTickers) . " calls (company info)\n";
        echo "  Total API calls: " . (count($this->todayTickers) + 2) . "\n";
        
        echo "\n⏱️  Time Breakdown:\n";
        echo "  Discovery: {$this->phaseTimes['discovery']}s\n";
        echo "  Polygon Batch: {$this->phaseTimes['polygon_batch']}s\n";
        echo "  Polygon Details: {$this->phaseTimes['polygon_details']}s\n";
        echo "  Processing: {$this->phaseTimes['processing']}s\n";
        echo "  Database: {$this->phaseTimes['database']}s\n";
        echo "  🚀 TOTAL EXECUTION TIME: {$totalTime}s\n";
    }
    
    /**
     * Retry mechanism pre zvýšenie reliability
     */
    private function retryOperation(callable $operation, int $maxAttempts = 3, int $delay = 1000) {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $result = $operation();
                if ($result) return $result;
                
                if ($attempt < $maxAttempts) {
                    echo "⚠️  Attempt {$attempt} failed, retrying in " . ($delay/1000) . "s...\n";
                    usleep($delay * 1000);
                }
                
            } catch (Exception $e) {
                if ($attempt === $maxAttempts) throw $e;
                echo "⚠️  Attempt {$attempt} failed with error: " . $e->getMessage() . "\n";
                usleep($delay * 1000);
            }
        }
        return false;
    }
    
    /**
     * Určenie veľkosti spoločnosti na základe market cap
     */
    private function getCompanySize($marketCap) {
        if (!$marketCap || $marketCap <= 0) return 'Small';
        
        $marketCapInBillions = $marketCap / 1000000000;
        if ($marketCapInBillions >= 10) {
            return 'Large';
        } elseif ($marketCapInBillions >= 2) {
            return 'Mid';
        }
        return 'Small';
    }
}

/**
 * Metrics Collector pre sledovanie výkonnosti
 */
class MetricsCollector {
    private $metrics = [];
    
    public function recordApiCall($api, $duration, $success) {
        $this->metrics['api_calls'][] = [
            'api' => $api,
            'duration' => $duration,
            'success' => $success,
            'timestamp' => time()
        ];
    }
    
    public function getSummary() {
        if (empty($this->metrics['api_calls'])) {
            return ['total_api_calls' => 0, 'success_rate' => 0, 'avg_duration' => 0];
        }
        
        $totalCalls = count($this->metrics['api_calls']);
        $successfulCalls = count(array_filter($this->metrics['api_calls'], fn($call) => $call['success']));
        $totalDuration = array_sum(array_column($this->metrics['api_calls'], 'duration'));
        
        return [
            'total_api_calls' => $totalCalls,
            'success_rate' => round(($successfulCalls / $totalCalls) * 100, 2),
            'avg_duration' => round($totalDuration / $totalCalls, 2)
        ];
    }
}
?>
