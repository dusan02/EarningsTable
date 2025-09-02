<?php
/**
 * ⚡ REGULAR DATA UPDATES - DYNAMIC
 * 
 * Trieda pre aktualizáciu dynamických dát každých 5 minút:
 * - Finnhub: EPS/Revenue Actual (skutočné hodnoty po reporte)
 * - Polygon: Current Price, Price Change %, Market Cap Diff
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/Lock.php';
require_once __DIR__ . '/api_functions.php';
require_once __DIR__ . '/Finnhub.php';
require_once __DIR__ . '/HistoricalDataManager.php';

class RegularDataUpdatesDynamic {
    private $date;
    private $timezone;
    private $startTime;
    private $lock;
    private $config;
    private $metrics;
    
    // Data storage
    private $existingTickers = [];
    private $finnhubTickers = [];
    private $actualUpdates = [];
    private $priceUpdates = [];
    private $marketCapDiffUpdates = [];
    private $currentData = [];
    
    // Performance tracking
    private $phaseTimes = [];
    
    public function __construct() {
        $this->timezone = new DateTimeZone('America/New_York');
        $this->date = (new DateTime('now', $this->timezone))->format('Y-m-d');
        $this->startTime = microtime(true);
        $this->lock = new Lock('regular_data_updates_dynamic');
        $this->config = new DynamicUpdateConfig();
        $this->metrics = new DynamicUpdateMetrics();
    }
    
    /**
     * Hlavná metóda pre spustenie celého procesu
     */
    public function run() {
        echo "⚡ REGULAR DATA UPDATES - DYNAMIC STARTED\n";
        echo "📅 Date: {$this->date}\n";
        echo "⏰ Time: " . (new DateTime('now', $this->timezone))->format('H:i:s') . " NY\n\n";
        
        try {
            $this->initialize();
            $this->getExistingTickers();
            $this->fetchFinnhubDynamicData();
            $this->fetchPolygonDynamicData();
            $this->calculateMarketCapDiff();
            $this->batchDatabaseUpdates();
            $this->finalSummary();
            
            echo "\n✅ REGULAR DATA UPDATES - DYNAMIC COMPLETED\n";
            
        } catch (Exception $e) {
            echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    /**
     * Inicializácia a lock mechanism
     */
    private function initialize() {
        $phaseStart = microtime(true);
        
        // Lock mechanism
        if (!$this->lock->acquire()) {
            throw new Exception("Another process is running");
        }
        register_shutdown_function(fn() => $this->lock->release());
        
        $this->phaseTimes['initialize'] = round(microtime(true) - $phaseStart, 2);
    }
    
    /**
     * Fáza 1: Získanie existujúcich tickerov z databázy
     */
    private function getExistingTickers() {
        $phaseStart = microtime(true);
        echo "=== STEP 1: GETTING EXISTING TICKERS ===\n";
        
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT ticker FROM TodayEarningsMovements");
        $stmt->execute();
        $this->existingTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($this->existingTickers)) {
            throw new Exception("No tickers found for today");
        }
        
        echo "✅ Found " . count($this->existingTickers) . " existing tickers\n";
        
        $this->phaseTimes['get_tickers'] = round(microtime(true) - $phaseStart, 2);
    }
    
    /**
     * Fáza 2: Získanie Finnhub dynamických dát
     */
    private function fetchFinnhubDynamicData() {
        $phaseStart = microtime(true);
        echo "\n=== STEP 2: FINNHUB DYNAMIC DATA ===\n";
        
        global $pdo;
        
        // Get only Finnhub tickers from database
        $stmt = $pdo->prepare("
            SELECT ticker 
            FROM earningstickerstoday 
            WHERE data_source = 'finnhub' 
            AND report_date = ?
        ");
        $stmt->execute([$this->date]);
        $this->finnhubTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($this->finnhubTickers)) {
            echo "❌ No Finnhub tickers found for today\n";
            $this->actualUpdates = [];
            $this->phaseTimes['finnhub_data'] = round(microtime(true) - $phaseStart, 2);
            return;
        }
        
        echo "✅ Found " . count($this->finnhubTickers) . " Finnhub tickers\n";
        
        // Fetch Finnhub data with retry
        $finnhub = new Finnhub();
        $response = $this->retryOperation(
            fn() => $finnhub->getEarningsCalendar('', $this->date, $this->date),
            maxAttempts: $this->config->get('max_retry_attempts'),
            delay: $this->config->get('retry_delay')
        );
        
        $earningsData = $response['earningsCalendar'] ?? [];
        
        // Process data efficiently
        $this->processFinnhubData($earningsData);
        
        $this->phaseTimes['finnhub_data'] = round(microtime(true) - $phaseStart, 2);
    }
    
    /**
     * Efektívne spracovanie Finnhub dát
     */
    private function processFinnhubData($earningsData) {
        $finnhubTickersSet = array_flip($this->finnhubTickers);
        $epsActualCount = 0;
        $revenueActualCount = 0;
        
        $this->actualUpdates = array_reduce($earningsData, function($carry, $earning) use ($finnhubTickersSet, &$epsActualCount, &$revenueActualCount) {
            $ticker = $earning['symbol'] ?? '';
            if (isset($finnhubTickersSet[$ticker])) {
                $epsActual = $earning['epsActual'] ?? null;
                $revenueActual = $earning['revenueActual'] ?? null;
                
                // Only include if we have actual values
                if ($epsActual !== null || $revenueActual !== null) {
                    $carry[$ticker] = [
                        'eps_actual' => $epsActual,
                        'revenue_actual' => $revenueActual
                    ];
                    
                    if ($epsActual !== null) $epsActualCount++;
                    if ($revenueActual !== null) $revenueActualCount++;
                }
            }
            return $carry;
        }, []);
        
        echo "✅ Found actual values for " . count($this->actualUpdates) . " tickers\n";
        echo "   - EPS actual: {$epsActualCount}\n";
        echo "   - Revenue actual: {$revenueActualCount}\n";
    }
    
    /**
     * Fáza 3: Získanie Polygon dynamických dát
     */
    private function fetchPolygonDynamicData() {
        $phaseStart = microtime(true);
        echo "\n=== STEP 3: POLYGON DYNAMIC DATA ===\n";
        
        $this->priceUpdates = [];
        $chunks = array_chunk($this->existingTickers, $this->config->get('polygon_batch_limit'));
        $totalPolygonCalls = 0;
        $successfulCalls = 0;
        
        foreach ($chunks as $index => $tickerChunk) {
            echo "Processing price chunk " . ($index + 1) . "/" . count($chunks) . " (" . count($tickerChunk) . " tickers)...\n";
            
            $chunkStart = microtime(true);
            
            // Get snapshot data
            $batchData = $this->retryOperation(
                fn() => getPolygonBatchQuote($tickerChunk),
                maxAttempts: $this->config->get('max_retry_attempts'),
                delay: $this->config->get('retry_delay')
            );
            
            // Get last trades from V3 API for better extended hours data
            $lastTradesData = $this->retryOperation(
                fn() => getPolygonBatchLastTrades($tickerChunk),
                maxAttempts: $this->config->get('max_retry_attempts'),
                delay: $this->config->get('retry_delay')
            );
            
            $chunkDuration = round(microtime(true) - $chunkStart, 2);
            
            if ($batchData) {
                $this->processPolygonBatchData($batchData, $lastTradesData);
                $successfulCalls++;
                $this->metrics->recordApiCall('polygon', $chunkDuration, true);
            } else {
                $this->metrics->recordApiCall('polygon', $chunkDuration, false);
            }
            
            $totalPolygonCalls++;
            
            // Rate limiting
            if ($index < count($chunks) - 1) {
                sleep($this->config->get('rate_limit_delay'));
            }
        }
        
        echo "✅ Updated prices for " . count($this->priceUpdates) . " tickers in {$totalPolygonCalls} API calls\n";
        echo "✅ Successful API calls: {$successfulCalls}/{$totalPolygonCalls}\n";
        
        $this->phaseTimes['polygon_data'] = round(microtime(true) - $phaseStart, 2);
    }
    
    /**
     * Spracovanie Polygon batch dát s robustnou logikou pre % CHANGE - UPRAVENÉ
     */
    private function processPolygonBatchData($batchData, $lastTradesData = null) {
        // batchData is already ticker-keyed array from getPolygonBatchQuote
        foreach ($batchData as $ticker => $result) {
            // Use historical previous_close from database instead of current prevDay.c
            $historicalPreviousClose = HistoricalDataManager::getHistoricalPreviousCloseFromDB($ticker);
            
            // Fallback to current prevDay.c if historical data not available
            $previousClose = $historicalPreviousClose ?? ($result['prevDay']['c'] ?? null);
            
            // Validate previous close
            if ($previousClose === null || $previousClose <= 0) {
                echo "⚠️  {$ticker}: Skipping - no valid previous close (historical: {$historicalPreviousClose}, current: " . ($result['prevDay']['c'] ?? 'null') . ")\n";
                continue;
            }
            
            // Get last trade from V3 API if available
            $lastTradeV3 = $lastTradesData[$ticker] ?? null;
            
            // Use robust percent change calculation
            $percentChangeData = computePercentChange($result, $lastTradeV3, $previousClose);
            $priceChangePercent = $percentChangeData['percent'];
            $changeSource = $percentChangeData['source'];
            
            // Get current price using existing logic
            $priceData = getCurrentPrice($result);
            
            // FALLBACK: If no current price, use historical previous_close from database
            if ($priceData === null || $priceData['price'] <= 0) {
                $fallbackData = HistoricalDataManager::getCurrentPriceWithFallback($result, $ticker);
                if ($fallbackData) {
                    $currentPrice = $fallbackData['price'];
                    $priceSource = $fallbackData['source'];
                } else {
                    echo "⚠️  {$ticker}: Skipping - no valid current price or historical fallback\n";
                    continue;
                }
            } else {
                $currentPrice = $priceData['price'];
                $priceSource = $priceData['source'];
            }
            
            echo "✅ {$ticker}: Processing with historical prevClose={$previousClose}, currentPrice={$currentPrice}\n";
            
            $this->priceUpdates[$ticker] = [
                'current_price' => $currentPrice,
                'previous_close' => $previousClose,
                'price_change_percent' => $priceChangePercent,
                'price_source' => $priceSource,
                'change_source' => $changeSource
            ];
        }
    }
    
    /**
     * Fáza 4: Výpočet Market Cap Diff
     */
    private function calculateMarketCapDiff() {
        $phaseStart = microtime(true);
        echo "\n=== STEP 4: CALCULATING MARKET CAP DIFF ===\n";
        
        // Batch fetch current data
        $this->fetchCurrentDataBatch();
        
        $this->marketCapDiffUpdates = [];
        $marketCapDiffCount = 0;
        
        foreach ($this->existingTickers as $ticker) {
            if (!isset($this->currentData[$ticker]) || !isset($this->priceUpdates[$ticker])) {
                continue;
            }
            
            $currentData = $this->currentData[$ticker];
            $priceData = $this->priceUpdates[$ticker];
            
            $marketCap = $currentData['market_cap'];
            $previousClose = $currentData['previous_close'];
            $currentPrice = $priceData['current_price'];
            
            // Calculate market cap diff
            if ($marketCap && $previousClose > 0 && $currentPrice > 0) {
                $priceChangePercent = (($currentPrice - $previousClose) / $previousClose) * 100;
                $marketCapDiff = ($priceChangePercent / 100) * $marketCap;
                $marketCapDiffBillions = $marketCapDiff / 1000000000;
                
                $this->marketCapDiffUpdates[$ticker] = [
                    'market_cap_diff' => $marketCapDiff,
                    'market_cap_diff_billions' => $marketCapDiffBillions
                ];
                $marketCapDiffCount++;
            }
        }
        
        echo "✅ Calculated market cap diff for {$marketCapDiffCount} tickers\n";
        
        $this->phaseTimes['market_cap_diff'] = round(microtime(true) - $phaseStart, 2);
    }
    
    // Historical data management moved to HistoricalDataManager class
    
    /**
     * Batch fetch current data from database
     */
    private function fetchCurrentDataBatch() {
        global $pdo;
        
        if (empty($this->existingTickers)) {
            return;
        }
        
        $placeholders = str_repeat('?,', count($this->existingTickers) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT ticker, market_cap, previous_close, eps_actual, revenue_actual, 
                   current_price, price_change_percent, market_cap_diff, market_cap_diff_billions
            FROM todayearningsmovements 
            WHERE ticker IN ($placeholders)
        ");
        $stmt->execute($this->existingTickers);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->currentData = [];
        foreach ($results as $row) {
            $this->currentData[$row['ticker']] = $row;
        }
    }
    
    /**
     * Fáza 5: Batch databázové aktualizácie
     */
    private function batchDatabaseUpdates() {
        $phaseStart = microtime(true);
        echo "\n=== STEP 5: BATCH DATABASE UPDATES ===\n";
        
        global $pdo;
        
        $totalUpdates = 0;
        
        // Prepare batch update statement
        $updateStmt = $pdo->prepare("
            UPDATE TodayEarningsMovements 
            SET 
                current_price = ?,
                price_change_percent = ?,
                change_source = ?,
                eps_actual = ?,
                revenue_actual = ?,
                market_cap_diff = ?,
                market_cap_diff_billions = ?,
                updated_at = NOW()
            WHERE ticker = ?
        ");
        
        foreach ($this->existingTickers as $ticker) {
            $actualData = $this->actualUpdates[$ticker] ?? null;
            $priceData = $this->priceUpdates[$ticker] ?? null;
            $marketCapDiffData = $this->marketCapDiffUpdates[$ticker] ?? null;
            $current = $this->currentData[$ticker] ?? null;
            
            if (!$current) {
                continue;
            }
            
            // Merge updates with existing data
            $newEpsActual = $actualData['eps_actual'] ?? $current['eps_actual'];
            $newRevenueActual = $actualData['revenue_actual'] ?? $current['revenue_actual'];
            $newCurrentPrice = $priceData['current_price'] ?? $current['current_price'];
            $newPriceChangePercent = $priceData['price_change_percent'] ?? $current['price_change_percent'];
            $newMarketCapDiff = $marketCapDiffData['market_cap_diff'] ?? $current['market_cap_diff'];
            $newMarketCapDiffBillions = $marketCapDiffData['market_cap_diff_billions'] ?? $current['market_cap_diff_billions'];
            
            // Get change source
            $newChangeSource = $priceData['change_source'] ?? $current['change_source'];
            
            // Only update if there are changes
            if ($this->hasChanges($current, $newEpsActual, $newRevenueActual, $newCurrentPrice, 
                                 $newPriceChangePercent, $newMarketCapDiff, $newMarketCapDiffBillions, $newChangeSource)) {
                
                $updateStmt->execute([
                    $newCurrentPrice,
                    $newPriceChangePercent,
                    $newChangeSource,
                    $newEpsActual,
                    $newRevenueActual,
                    $newMarketCapDiff,
                    $newMarketCapDiffBillions,
                    $ticker
                ]);
                $totalUpdates++;
            }
        }
        
        echo "✅ Updated {$totalUpdates} records in database\n";
        
        $this->phaseTimes['database_updates'] = round(microtime(true) - $phaseStart, 2);
    }
    
    /**
     * Kontrola či sa dáta zmenili
     */
    private function hasChanges($current, $newEpsActual, $newRevenueActual, $newCurrentPrice, 
                               $newPriceChangePercent, $newMarketCapDiff, $newMarketCapDiffBillions, $newChangeSource) {
        return $newEpsActual !== $current['eps_actual'] ||
               $newRevenueActual !== $current['revenue_actual'] ||
               $newCurrentPrice !== $current['current_price'] ||
               $newPriceChangePercent !== $current['price_change_percent'] ||
               $newMarketCapDiff !== $current['market_cap_diff'] ||
               $newMarketCapDiffBillions !== $current['market_cap_diff_billions'] ||
               $newChangeSource !== $current['change_source'];
    }
    
    /**
     * Fáza 6: Finálne štatistiky
     */
    private function finalSummary() {
        echo "\n=== FINAL SUMMARY ===\n";
        
        $totalTime = round(microtime(true) - $this->startTime, 2);
        
        // Get final statistics
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM TodayEarningsMovements");
        $stmt->execute();
        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL");
        $stmt->execute();
        $epsActualRecords = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE revenue_actual IS NOT NULL");
        $stmt->execute();
        $revenueActualRecords = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE current_price IS NOT NULL");
        $stmt->execute();
        $priceRecords = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "📊 Total records: {$totalRecords}\n";
        echo "📊 Records with EPS actual: {$epsActualRecords}\n";
        echo "📊 Records with Revenue actual: {$revenueActualRecords}\n";
        echo "📊 Records with prices: {$priceRecords}\n";
        
        // API performance
        $apiMetrics = $this->metrics->getSummary();
        echo "🚀 API Performance:\n";
        echo "   - Total API calls: {$apiMetrics['total_api_calls']}\n";
        echo "   - Success rate: {$apiMetrics['success_rate']}%\n";
        echo "   - Average duration: {$apiMetrics['avg_duration']}s\n";
        
        // Show recent actual values
        $this->showRecentActualValues();
        
        echo "\n⏱️  Time Breakdown:\n";
        echo "  Initialize: {$this->phaseTimes['initialize']}s\n";
        echo "  Get Tickers: {$this->phaseTimes['get_tickers']}s\n";
        echo "  Finnhub Data: {$this->phaseTimes['finnhub_data']}s\n";
        echo "  Polygon Data: {$this->phaseTimes['polygon_data']}s\n";
        echo "  Market Cap Diff: {$this->phaseTimes['market_cap_diff']}s\n";
        echo "  Database Updates: {$this->phaseTimes['database_updates']}s\n";
        echo "  🚀 TOTAL EXECUTION TIME: {$totalTime}s\n";
    }
    
    /**
     * Zobrazenie recent actual values
     */
    private function showRecentActualValues() {
        global $pdo;
        
        echo "\n=== RECENT ACTUAL VALUES ===\n";
        $stmt = $pdo->prepare("
            SELECT ticker, eps_actual, revenue_actual, updated_at 
            FROM TodayEarningsMovements 
            WHERE eps_actual IS NOT NULL OR revenue_actual IS NOT NULL
            ORDER BY updated_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recentActuals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($recentActuals as $record) {
            $eps = $record['eps_actual'] ?? 'N/A';
            $revenue = $record['revenue_actual'] ?? 'N/A';
            $time = date('H:i:s', strtotime($record['updated_at']));
            echo "{$record['ticker']} | EPS: {$eps} | Revenue: {$revenue} | {$time}\n";
        }
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
}

/**
 * Configuration class pre flexibilné nastavenia
 */
class DynamicUpdateConfig {
    private $settings = [
        'polygon_batch_limit' => 100,
        'rate_limit_delay' => 1,
        'max_retry_attempts' => 3,
        'retry_delay' => 1000
    ];
    
    public function get($key) {
        return $this->settings[$key] ?? null;
    }
    
    public function set($key, $value) {
        $this->settings[$key] = $value;
    }
}

/**
 * Metrics Collector pre sledovanie výkonnosti
 */
class DynamicUpdateMetrics {
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
