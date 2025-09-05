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
require_once __DIR__ . '/UnifiedApiWrapper.php';
require_once __DIR__ . '/UnifiedLogger.php';
require_once __DIR__ . '/Finnhub.php';
require_once __DIR__ . '/HistoricalDataManager.php';

class RegularDataUpdatesDynamic {
    private $date;
    private $timezone;
    private $startTime;
    private $lock;
    private $config;
    private $metrics;
    private $apiWrapper; // Add UnifiedApiWrapper instance
    
    // Data storage
    private $existingTickers = [];
    private $finnhubTickers = [];
    private $actualUpdates = [];
    private $priceUpdates = [];
    private $marketCapDiffUpdates = [];
    private $currentData = [];
    private $historicalData = []; // Pre-fetched historical data
    
    // Performance tracking
    private $phaseTimes = [];
    
    public function __construct() {
        $this->timezone = new DateTimeZone('America/New_York');
        $this->date = (new DateTime('now', $this->timezone))->format('Y-m-d');
        $this->startTime = microtime(true);
        $this->lock = new Lock('regular_data_updates_dynamic');
        $this->config = new DynamicUpdateConfig();
        $this->metrics = new DynamicUpdateMetrics();
        $this->apiWrapper = new UnifiedApiWrapper();
        $this->logger = new UnifiedLogger();
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
        
        // Pre-fetch all historical data in one batch query
        $this->prefetchHistoricalData();
        
        // Process chunks in parallel using curl_multi
        $this->processChunksInParallel($chunks, $totalPolygonCalls, $successfulCalls);
        
        echo "✅ Updated prices for " . count($this->priceUpdates) . " tickers in {$totalPolygonCalls} API calls\n";
        echo "✅ Successful API calls: {$successfulCalls}/{$totalPolygonCalls}\n";
        
        // FALLBACK: If no prices were updated, use fallback mechanism
        if (empty($this->priceUpdates)) {
            echo "⚠️  No prices updated, triggering fallback mechanism...\n";
            $this->processFallbackData();
        }
        
        $this->phaseTimes['polygon_data'] = round(microtime(true) - $phaseStart, 2);
    }
    
    /**
     * Pre-fetch all historical data in one batch query
     */
    private function prefetchHistoricalData() {
        global $pdo;
        
        if (empty($this->existingTickers)) {
            return;
        }
        
        $placeholders = str_repeat('?,', count($this->existingTickers) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT ticker, previous_close 
            FROM todayearningsmovements 
            WHERE ticker IN ($placeholders)
        ");
        $stmt->execute($this->existingTickers);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->historicalData = [];
        foreach ($results as $row) {
            $this->historicalData[$row['ticker']] = $row['previous_close'];
        }
        
        echo "📊 Pre-fetched historical data for " . count($this->historicalData) . " tickers\n";
    }
    
    /**
     * Process chunks in parallel using curl_multi
     */
    private function processChunksInParallel($chunks, &$totalPolygonCalls, &$successfulCalls) {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $chunkMap = [];
        
        // Start all chunk requests in parallel
        foreach ($chunks as $index => $tickerChunk) {
            echo "🚀 Starting chunk " . ($index + 1) . "/" . count($chunks) . " (" . count($tickerChunk) . " tickers)...\n";
            
            $ch = curl_init();
            $tickerList = implode(',', $tickerChunk);
            $url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers?tickers={$tickerList}&apikey=" . POLYGON_API_KEY;
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);
            
            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[] = $ch;
            $chunkMap[spl_object_hash($ch)] = ['index' => $index, 'tickers' => $tickerChunk];
        }
        
        // Execute all requests in parallel
        $active = null;
        do {
            $status = curl_multi_exec($multiHandle, $active);
            if ($active) {
                curl_multi_select($multiHandle);
            }
            
            // Process completed requests
            while ($info = curl_multi_info_read($multiHandle)) {
                if ($info['msg'] == CURLMSG_DONE) {
                    $ch = $info['handle'];
                    $chunkInfo = $chunkMap[spl_object_hash($ch)];
                    $index = $chunkInfo['index'];
                    $tickerChunk = $chunkInfo['tickers'];
                    
                    $response = curl_multi_getcontent($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $error = curl_error($ch);
                    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
                    
                    if ($error) {
                        echo "  ❌ Chunk " . ($index + 1) . " failed: {$error}\n";
                        $this->processFallbackDataForChunk($tickerChunk);
                    } elseif ($httpCode === 200 && $response) {
                        $data = json_decode($response, true);
                        if (isset($data['tickers']) && is_array($data['tickers'])) {
                            $batchData = [];
                            foreach ($data['tickers'] as $result) {
                                if (isset($result['ticker'])) {
                                    $batchData[$result['ticker']] = $result;
                                }
                            }
                            
                            $this->processPolygonBatchDataOptimized($batchData);
                            $successfulCalls++;
                            echo "  ✅ Chunk " . ($index + 1) . " processed successfully in {$totalTime}s\n";
                        } else {
                            echo "  ❌ Chunk " . ($index + 1) . " failed: Invalid response format\n";
                            $this->processFallbackDataForChunk($tickerChunk);
                        }
                    } else {
                        echo "  ❌ Chunk " . ($index + 1) . " failed: HTTP {$httpCode}\n";
                        $this->processFallbackDataForChunk($tickerChunk);
                    }
                    
                    curl_multi_remove_handle($multiHandle, $ch);
                    curl_close($ch);
                    $totalPolygonCalls++;
                }
            }
        } while ($active && $status == CURLM_OK);
        
        curl_multi_close($multiHandle);
    }
    
    /**
     * Optimized Polygon batch data processing (no individual DB calls)
     */
    private function processPolygonBatchDataOptimized($batchData) {
        if (empty($batchData)) {
            return;
        }
        
        foreach ($batchData as $ticker => $result) {
            // Use pre-fetched historical data
            $previousClose = $this->historicalData[$ticker] ?? ($result['prevDay']['c'] ?? null);
            
            if ($previousClose === null || $previousClose <= 0) {
                continue;
            }
            
            // Get current price
            $currentPrice = $result['lastTrade']['p'] ?? $result['prevDay']['c'] ?? null;
            
            // Calculate percent change ONLY if we have valid current price
            $priceChangePercent = null;
            if ($currentPrice !== null && $currentPrice > 0 && $previousClose > 0) {
                $priceChangePercent = (($currentPrice - $previousClose) / $previousClose) * 100;
            }
            
            $this->priceUpdates[$ticker] = [
                'current_price' => $currentPrice,
                'previous_close' => $previousClose,
                'price_change_percent' => $priceChangePercent,
                'price_source' => 'polygon_optimized',
                'change_source' => 'polygon_optimized'
            ];
        }
    }
    
    /**
     * Spracovanie Polygon batch dát s robustnou logikou pre % CHANGE - UPRAVENÉ
     */
    private function processPolygonBatchData($batchData, $lastTradesData = null) {
        // FALLBACK: If Polygon API failed, use previous_close as fallback
        if (empty($batchData)) {
            echo "⚠️  Polygon API failed, using fallback mechanism...\n";
            $this->processFallbackData();
            return;
        }
        
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
            
            // Get current price using existing logic
            $priceData = $this->apiWrapper->getCurrentPrice($result);
            
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
            
            // Use robust percent change calculation ONLY if we have valid current price
            $priceChangePercent = null;
            $changeSource = 'no_change';
            if ($currentPrice > 0 && $previousClose > 0) {
                $percentChangeData = $this->apiWrapper->computePercentChange($result, $lastTradeV3, $previousClose);
                $priceChangePercent = $percentChangeData['percent'];
                $changeSource = $percentChangeData['source'];
            }
            
            echo "✅ {$ticker}: Processing with historical prevClose={$previousClose}, currentPrice={$currentPrice}, changePercent={$priceChangePercent}\n";
            
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
     * Fallback mechanism keď Polygon API zlyhá
     */
    private function processFallbackData() {
        echo "🔄 Processing fallback data for all tickers...\n";
        
        foreach ($this->existingTickers as $ticker) {
            // Get previous_close from database
            global $pdo;
            $stmt = $pdo->prepare("SELECT previous_close FROM TodayEarningsMovements WHERE ticker = ?");
            $stmt->execute([$ticker]);
            $result = $stmt->fetch();
            
            if ($result && $result['previous_close'] > 0) {
                $previousClose = $result['previous_close'];
                
                // Use previous_close as current_price (fallback)
                $currentPrice = $previousClose;
                $priceChangePercent = null; // No valid current price, so no change calculation
                
                echo "✅ {$ticker}: Fallback - current_price={$currentPrice}, change=NULL (no valid price)\n";
                
                $this->priceUpdates[$ticker] = [
                    'current_price' => $currentPrice,
                    'previous_close' => $previousClose,
                    'price_change_percent' => $priceChangePercent,
                    'price_source' => 'fallback',
                    'change_source' => 'fallback'
                ];
            }
        }
        
        echo "✅ Fallback processing completed for " . count($this->priceUpdates) . " tickers\n";
    }

    /**
     * Fallback mechanism pre konkrétny chunk tickerov
     */
    private function processFallbackDataForChunk($tickerChunk) {
        echo "🔄 Processing fallback data for chunk of tickers...\n";
        foreach ($tickerChunk as $ticker) {
            // Get previous_close from database
            global $pdo;
            $stmt = $pdo->prepare("SELECT previous_close FROM TodayEarningsMovements WHERE ticker = ?");
            $stmt->execute([$ticker]);
            $result = $stmt->fetch();
            
            if ($result && $result['previous_close'] > 0) {
                $previousClose = $result['previous_close'];
                
                // Use previous_close as current_price (fallback)
                $currentPrice = $previousClose;
                $priceChangePercent = null; // No valid current price, so no change calculation
                
                echo "✅ {$ticker}: Fallback - current_price={$currentPrice}, change=NULL (no valid price)\n";
                
                $this->priceUpdates[$ticker] = [
                    'current_price' => $currentPrice,
                    'previous_close' => $previousClose,
                    'price_change_percent' => $priceChangePercent,
                    'price_source' => 'fallback',
                    'change_source' => 'fallback'
                ];
            }
        }
        echo "✅ Fallback processing completed for chunk of tickers\n";
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
            $priceChangePercent = $priceData['price_change_percent'];
            
            // Calculate market cap diff using the price_change_percent from priceUpdates
            // ONLY if we have valid current price and price change percent
            if ($marketCap && $previousClose > 0 && $currentPrice > 0 && $priceChangePercent !== null) {
                $marketCapDiff = ($priceChangePercent / 100) * $marketCap;
                $marketCapDiffBillions = $marketCapDiff / 1000000000;
                
                $this->marketCapDiffUpdates[$ticker] = [
                    'market_cap_diff' => $marketCapDiff,
                    'market_cap_diff_billions' => $marketCapDiffBillions
                ];
                $marketCapDiffCount++;
                
                echo "✅ {$ticker}: Market Cap Diff = {$marketCapDiffBillions}B (change: {$priceChangePercent}%)\n";
            } else {
                // Set market cap diff to null if no valid price data
                $this->marketCapDiffUpdates[$ticker] = [
                    'market_cap_diff' => null,
                    'market_cap_diff_billions' => null
                ];
                
                if ($currentPrice === null || $currentPrice <= 0) {
                    echo "⚠️  {$ticker}: No valid current price - skipping market cap diff calculation\n";
                }
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
     * Fáza 5: Batch databázové aktualizácie - OPTIMALIZOVANÉ
     */
    private function batchDatabaseUpdates() {
        $phaseStart = microtime(true);
        echo "\n=== STEP 5: BATCH DATABASE UPDATES (OPTIMIZED) ===\n";
        
        global $pdo;
        
        $totalUpdates = 0;
        
        // Prepare batch update data
        $updateData = [];
        $tickersToUpdate = [];
        
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
            $newChangeSource = $priceData['change_source'] ?? $current['change_source'];
            
            // Only include if there are changes
            if ($this->hasChanges($current, $newEpsActual, $newRevenueActual, $newCurrentPrice, 
                                 $newPriceChangePercent, $newMarketCapDiff, $newMarketCapDiffBillions, $newChangeSource)) {
                
                $updateData[] = [
                    'ticker' => $ticker,
                    'current_price' => $newCurrentPrice,
                    'price_change_percent' => $newPriceChangePercent,
                    'change_source' => $newChangeSource,
                    'eps_actual' => $newEpsActual,
                    'revenue_actual' => $newRevenueActual,
                    'market_cap_diff' => $newMarketCapDiff,
                    'market_cap_diff_billions' => $newMarketCapDiffBillions
                ];
                $tickersToUpdate[] = $ticker;
            }
        }
        
        if (empty($updateData)) {
            echo "✅ No records need updating\n";
            $this->phaseTimes['database_updates'] = round(microtime(true) - $phaseStart, 2);
            return;
        }
        
        echo "🔄 Updating " . count($updateData) . " records using batch operation...\n";
        
        // Batch UPDATE using CASE statements
        $this->executeBatchUpdate($pdo, $updateData);
        
        $totalUpdates = count($updateData);
        echo "✅ Batch updated {$totalUpdates} records in database\n";
        
        $this->phaseTimes['database_updates'] = round(microtime(true) - $phaseStart, 2);
    }
    
    /**
     * Vykoná batch UPDATE pomocou CASE statements
     */
    private function executeBatchUpdate($pdo, $updateData) {
        if (empty($updateData)) return;
        
        // Build CASE statements for each column
        $caseStatements = [];
        $params = [];
        
        // Prepare CASE statements for each field
        $fields = ['current_price', 'price_change_percent', 'change_source', 'eps_actual', 'revenue_actual', 'market_cap_diff', 'market_cap_diff_billions'];
        
        foreach ($fields as $field) {
            $caseSql = "{$field} = CASE ticker ";
            foreach ($updateData as $data) {
                $caseSql .= "WHEN ? THEN ? ";
                $params[] = $data['ticker'];
                $params[] = $data[$field];
            }
            $caseSql .= "ELSE {$field} END";
            $caseStatements[] = $caseSql;
        }
        
        // Build final SQL
        $placeholders = str_repeat('?,', count($updateData) - 1) . '?';
        $sql = "
            UPDATE TodayEarningsMovements 
            SET " . implode(', ', $caseStatements) . ",
                updated_at = NOW()
            WHERE ticker IN ($placeholders)
        ";
        
        // Add ticker parameters for WHERE clause
        foreach ($updateData as $data) {
            $params[] = $data['ticker'];
        }
        
        // Execute batch update
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo "  🚀 Batch UPDATE executed with " . count($params) . " parameters\n";
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
                
                // Check if result is valid (not null, not false, can be empty array)
                if ($result !== null && $result !== false) {
                    return $result;
                }
                
                if ($attempt < $maxAttempts) {
                    echo "⚠️  Attempt {$attempt} failed (null/false result), retrying in " . ($delay/1000) . "s...\n";
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
        'polygon_batch_limit' => 25, // Reduced from 35 to 25 for better API stability
        'rate_limit_delay' => 0.2, // 200ms delay (increased from 100ms for better API stability)
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

