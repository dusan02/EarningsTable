<?php
/**
 * Optimized Update Movements Cron - FIXED VERSION
 * Implements fallback logic for closed markets and missing shares outstanding
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';
require_once __DIR__ . '/../common/Finnhub.php';
require_once __DIR__ . '/../utils/polygon_api_optimized.php';

// Lock mechanism
$lock = new Lock('update_movements');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 MOVEMENTS UPDATE STARTED (FIXED VERSION)\n";

try {
    // Get tickers - use US Eastern Time to match fetch_earnings_tickers.php
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT DISTINCT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tickers)) {
        echo "❌ No tickers found\n";
        exit(1);
    }

    echo "📊 Raw tickers: " . count($tickers) . "\n";

    // FILTER: Apply core US ticker filter
    $tickers = array_values(array_filter($tickers, 'looksCoreUsTicker'));
    echo "🔍 Filtered tickers: " . count($tickers) . "\n";

    // Get batch snapshot
    $snapshotData = getBatchSnapshot($tickers);
    if (!$snapshotData) {
        echo "❌ Failed to get snapshot\n";
        exit(1);
    }

    // Get accurate market cap data from Polygon V3 Reference
    $accurateData = getAccurateMarketCapBatch($tickers);
    echo "📈 ACCURATE DATA FETCHED: " . count($accurateData) . " tickers from Polygon V3\n";

    // Process data with new logic
    $processedData = [];
    $priceOnlyData = [];
    $processingStartTime = microtime(true);
    $processedCount = 0;
    $priceOnlyCount = 0;

    foreach ($tickers as $ticker) {
        if (!isset($snapshotData[$ticker])) continue;

        $snapshot = $snapshotData[$ticker];
        $accurate = $accurateData[$ticker] ?? null;

        $result = processTickerDataWithAccurateMC($snapshot, $ticker, $accurate);
        
        if ($result['wrote_price']) {
            if ($result['wrote_mc']) {
                $processedData[$ticker] = $result['data'];
                $processedCount++;
            } else {
                $priceOnlyData[$ticker] = $result['data'];
                $priceOnlyCount++;
            }
        }
    }

    $processingEndTime = microtime(true);
    $processingTime = round(($processingEndTime - $processingStartTime) * 1000, 2);
    $totalProcessed = $processedCount + $priceOnlyCount;

    echo "✅ PROCESSING RESULTS:\n";
    echo "  ⏱️  Total processing time: {$processingTime}ms\n";
    echo "  📊 Full data (with MC): {$processedCount}\n";
    echo "  📈 Price only (no MC): {$priceOnlyCount}\n";
    echo "  🎯 Total processed: {$totalProcessed}\n\n";

    if (empty($processedData) && empty($priceOnlyData)) {
        echo "❌ No data processed\n";
        exit(1);
    }

    // BULK UPDATE: Full data with market cap
    if (!empty($processedData)) {
        $sql = "INSERT INTO TodayEarningsMovements (
            ticker, company_name, current_price, previous_close,
            market_cap, size, market_cap_diff, market_cap_diff_billions,
            price_change_percent, shares_outstanding
        ) VALUES ";

        $values = [];
        $placeholders = [];

        foreach ($processedData as $data) {
            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $values = array_merge($values, [
                $data['ticker'], $data['company_name'], $data['current_price'],
                $data['previous_close'], $data['market_cap'], $data['size'],
                $data['market_cap_diff'], $data['market_cap_diff_billions'],
                $data['price_change_percent'], $data['shares_outstanding']
            ]);
        }

        $sql .= implode(', ', $placeholders);
        $sql .= " ON DUPLICATE KEY UPDATE
            company_name = VALUES(company_name),
            current_price = VALUES(current_price),
            previous_close = VALUES(previous_close),
            market_cap = VALUES(market_cap),
            size = VALUES(size),
            market_cap_diff = VALUES(market_cap_diff),
            market_cap_diff_billions = VALUES(market_cap_diff_billions),
            price_change_percent = VALUES(price_change_percent),
            shares_outstanding = VALUES(shares_outstanding),
            updated_at = CURRENT_TIMESTAMP";

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $pdo->commit();
            echo "✅ FULL DATA: " . count($processedData) . " tickers updated with market cap\n";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "❌ DATABASE ERROR (full data): " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    // BULK UPDATE: Price only data (no market cap)
    if (!empty($priceOnlyData)) {
        $sql = "INSERT INTO TodayEarningsMovements (
            ticker, company_name, current_price, previous_close,
            market_cap, size, market_cap_diff, market_cap_diff_billions,
            price_change_percent, shares_outstanding
        ) VALUES ";

        $values = [];
        $placeholders = [];

        foreach ($priceOnlyData as $data) {
            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $values = array_merge($values, [
                $data['ticker'], $data['company_name'], $data['current_price'],
                $data['previous_close'], null, 'Unknown', null, null,
                $data['price_change_percent'], null
            ]);
        }

        $sql .= implode(', ', $placeholders);
        $sql .= " ON DUPLICATE KEY UPDATE
            company_name = VALUES(company_name),
            current_price = VALUES(current_price),
            previous_close = VALUES(previous_close),
            market_cap = VALUES(market_cap),
            size = VALUES(size),
            market_cap_diff = VALUES(market_cap_diff),
            market_cap_diff_billions = VALUES(market_cap_diff_billions),
            price_change_percent = VALUES(price_change_percent),
            shares_outstanding = VALUES(shares_outstanding),
            updated_at = CURRENT_TIMESTAMP";

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $pdo->commit();
            echo "✅ PRICE ONLY: " . count($priceOnlyData) . " tickers updated (no market cap)\n";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "❌ DATABASE ERROR (price only): " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    $executionTime = round(microtime(true) - $startTime, 2);
    echo "✅ FINAL SUCCESS: " . $totalProcessed . " tickers processed\n";
    echo "⏱️  Time: {$executionTime}s\n";
    echo "📊 Performance: " . round($totalProcessed / $executionTime, 1) . " tickers/s\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Check if ticker looks like a core US stock
 */
function looksCoreUsTicker(string $ticker): bool {
    if (strlen($ticker) > 5) return false;
    
    $upper = strtoupper($ticker);
    if (str_contains($upper, '-') || str_contains($upper, '.')) return false;
    if (str_contains($upper, 'WS') || str_contains($upper, 'W') || str_contains($upper, 'U')) return false;
    
    return true;
}

/**
 * Check if US market is currently open
 */
function isUsMarketOpen(): bool {
    $timezone = new DateTimeZone('America/New_York');
    $now = new DateTime('now', $timezone);
    
    $dow = (int)$now->format('N'); // 1-7 (Monday = 1)
    if ($dow >= 6) return false; // Weekend
    
    $time = (int)$now->format('Hi'); // 930-1600
    return $time >= 930 && $time <= 1600;
}

/**
 * Process ticker data with fallback logic
 */
function processTickerDataWithAccurateMC($snapshot, $ticker, $accurateData = null) {
    if (!isset($snapshot['lastTrade']) || !isset($snapshot['prevDay'])) {
        return ['wrote_price' => false, 'wrote_mc' => false];
    }

    $lastTrade = $snapshot['lastTrade']['p'] ?? 0;
    $prevClose = $snapshot['prevDay']['c'] ?? 0;

    // FALLBACK: Use previous close if market is closed and no last trade
    $currentPrice = ($lastTrade > 0) ? $lastTrade : (($prevClose > 0) ? $prevClose : 0);
    $priceSource = ($lastTrade > 0) ? 'last_trade' : 'prev_close';

    if ($currentPrice <= 0) {
        echo "⚠️  No price available for {$ticker}\n";
        return ['wrote_price' => false, 'wrote_mc' => false];
    }

    // Calculate price change percent (NULL if using fallback)
    $priceChangePercent = null;
    if ($lastTrade > 0 && $prevClose > 0) {
        $priceChangePercent = (($lastTrade - $prevClose) / $prevClose) * 100;
    }

    // Get shares outstanding
    $sharesOutstanding = null;
    $companyName = $ticker;
    
    if ($accurateData && isset($accurateData['shares_outstanding']) && $accurateData['shares_outstanding'] > 0) {
        $sharesOutstanding = $accurateData['shares_outstanding'];
        $companyName = $accurateData['company_name'];
    }

    // Prepare base data
    $data = [
        'ticker' => $ticker,
        'company_name' => $companyName,
        'current_price' => $currentPrice,
        'previous_close' => $prevClose,
        'price_change_percent' => $priceChangePercent
    ];

    // If we have shares outstanding, calculate market cap
    if ($sharesOutstanding) {
        $currentMarketCap = $currentPrice * $sharesOutstanding;
        $previousMarketCap = $prevClose * $sharesOutstanding;
        $marketCapDiff = $currentMarketCap - $previousMarketCap;
        $marketCapDiffBillions = $marketCapDiff / 1000000000;

        $size = match (true) {
            $currentMarketCap > 10000000000 => 'Large',
            $currentMarketCap > 1000000000 => 'Mid',
            default => 'Small'
        };

        $data['market_cap'] = $currentMarketCap;
        $data['size'] = $size;
        $data['market_cap_diff'] = $marketCapDiff;
        $data['market_cap_diff_billions'] = $marketCapDiffBillions;
        $data['shares_outstanding'] = $sharesOutstanding;

        echo "✅ {$ticker}: Price + MC ({$priceSource})\n";
        return ['wrote_price' => true, 'wrote_mc' => true, 'data' => $data];
    } else {
        echo "📊 {$ticker}: Price only ({$priceSource})\n";
        return ['wrote_price' => true, 'wrote_mc' => false, 'data' => $data];
    }
}
?> 