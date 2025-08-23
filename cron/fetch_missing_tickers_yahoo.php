<?php
/**
 * Fetch Missing Tickers from Yahoo Finance
 * Compare Finnhub earnings tickers with Yahoo Finance earnings calendar and add missing ones
 * Runs at 02:40h NY time
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Lock mechanism
$lock = new Lock('yahoo_missing_tickers');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 YAHOO FINANCE MISSING TICKERS FETCH STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n\n";
    
    // STEP 1: Get existing tickers from Finnhub
    echo "=== STEP 1: GETTING EXISTING TICKERS FROM FINNHUB ===\n";
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $existingTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($existingTickers) . " existing tickers from Finnhub\n";
    
    // STEP 2: Get Yahoo Finance earnings calendar
    echo "=== STEP 2: FETCHING YAHOO FINANCE EARNINGS CALENDAR ===\n";
    
    $yahooEarningsTickers = getYahooFinanceEarningsCalendar($date);
    echo "Found " . count($yahooEarningsTickers) . " tickers with earnings on Yahoo Finance\n";
    
    // STEP 3: Find missing tickers
    echo "=== STEP 3: FINDING MISSING TICKERS ===\n";
    
    $missingTickers = [];
    foreach ($yahooEarningsTickers as $yahooTicker) {
        if (!in_array($yahooTicker['ticker'], $existingTickers)) {
            $missingTickers[] = $yahooTicker;
            echo "✅ Found missing ticker: {$yahooTicker['ticker']} - {$yahooTicker['company_name']}\n";
        }
    }
    
    // STEP 4: Save missing tickers
    echo "\n=== STEP 4: SAVING MISSING TICKERS ===\n";
    
    if (!empty($missingTickers)) {
        // Save to EarningsTickersToday
        $stmt = $pdo->prepare("
            INSERT INTO EarningsTickersToday (
                ticker, eps_estimate, revenue_estimate, report_date, report_time
            ) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                eps_estimate = VALUES(eps_estimate),
                revenue_estimate = VALUES(revenue_estimate),
                report_time = VALUES(report_time)
        ");
        
        $savedCount = 0;
        foreach ($missingTickers as $tickerData) {
            $stmt->execute([
                $tickerData['ticker'],
                $tickerData['eps_estimate'],
                $tickerData['revenue_estimate'],
                $tickerData['report_date'],
                $tickerData['report_time']
            ]);
            $savedCount++;
        }
        
        // Also create entries in TodayEarningsMovements
        $stmt = $pdo->prepare("
            INSERT INTO TodayEarningsMovements (
                ticker, company_name, eps_estimate, eps_actual, revenue_estimate, revenue_actual, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                eps_estimate = VALUES(eps_estimate),
                eps_actual = VALUES(eps_actual),
                revenue_estimate = VALUES(revenue_estimate),
                revenue_actual = VALUES(revenue_actual),
                updated_at = NOW()
        ");
        
        foreach ($missingTickers as $tickerData) {
            $stmt->execute([
                $tickerData['ticker'],
                $tickerData['company_name'],
                $tickerData['eps_estimate'],
                $tickerData['eps_actual'],
                $tickerData['revenue_estimate'],
                $tickerData['revenue_actual']
            ]);
        }
        
        echo "✅ Added " . $savedCount . " missing tickers from Yahoo Finance\n";
    } else {
        echo "✅ No missing tickers found\n";
    }
    
    // FINAL SUMMARY
    echo "\n=== FINAL SUMMARY ===\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $totalTickers = $stmt->fetchColumn();
    
    echo "📊 Finnhub tickers: " . count($existingTickers) . "\n";
    echo "📊 Yahoo Finance tickers: " . count($yahooEarningsTickers) . "\n";
    echo "📊 Missing tickers found: " . count($missingTickers) . "\n";
    echo "📊 Total tickers after merge: {$totalTickers}\n";
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "⏱️  Total time: {$executionTime}s\n";
    echo "✅ YAHOO FINANCE MISSING TICKERS FETCH COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Get Yahoo Finance earnings calendar for a specific date
 */
function getYahooFinanceEarningsCalendar($date) {
    $tickers = [];
    
    echo "Checking Yahoo Finance earnings for {$date}...\n";
    
    // Get a comprehensive list of tickers that might have earnings
    $allTickers = getAllPossibleEarningsTickers();
    echo "Checking " . count($allTickers) . " possible earnings tickers...\n";
    
    $foundTickers = [];
    $checkedCount = 0;
    
    foreach ($allTickers as $ticker) {
        $checkedCount++;
        if ($checkedCount % 10 == 0) {
            echo "Checked {$checkedCount}/" . count($allTickers) . " tickers...\n";
        }
        
        if (hasEarningsToday($ticker, $date)) {
            $foundTickers[] = [
                'ticker' => $ticker,
                'company_name' => $ticker,
                'eps_estimate' => null,
                'eps_actual' => null,
                'revenue_estimate' => null,
                'revenue_actual' => null,
                'report_time' => 'TNS',
                'report_date' => $date
            ];
            echo "✅ {$ticker} has earnings today\n";
        }
        
        // Small delay to avoid rate limits
        usleep(50000); // 0.05 second
    }
    
    echo "Found " . count($foundTickers) . " tickers with earnings today\n";
    return $foundTickers;
}

/**
 * Get a comprehensive list of possible earnings tickers
 */
function getAllPossibleEarningsTickers() {
    // S&P 500 companies (top 100 by market cap)
    $sp500 = [
        'AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA', 'META', 'BRK-B', 'LLY', 'TSLA', 'V',
        'UNH', 'XOM', 'JNJ', 'WMT', 'JPM', 'PG', 'MA', 'HD', 'CVX', 'AVGO',
        'ABBV', 'PEP', 'KO', 'BAC', 'PFE', 'TMO', 'COST', 'ACN', 'DHR', 'MRK',
        'VZ', 'ABT', 'TXN', 'NKE', 'PM', 'RTX', 'HON', 'QCOM', 'UPS', 'IBM',
        'LOW', 'CAT', 'GS', 'MS', 'AMGN', 'SPGI', 'INTC', 'SYK', 'T', 'DE',
        'GILD', 'ISRG', 'VRTX', 'ADI', 'REGN', 'PLD', 'CMCSA', 'NEE', 'TXN', 'SO',
        'DUK', 'NSC', 'AON', 'ITW', 'BDX', 'SCHW', 'CI', 'TJX', 'CME', 'USB',
        'PNC', 'BLK', 'MMC', 'ETN', 'APD', 'EOG', 'SLB', 'COP', 'MPC', 'PSX',
        'VLO', 'HAL', 'BKR', 'FANG', 'PXD', 'OKE', 'KMI', 'WMB', 'TRP', 'ENB'
    ];
    
    // Additional popular companies
    $additional = [
        'AMD', 'NFLX', 'CRM', 'ADBE', 'PYPL', 'INTC', 'ORCL', 'CSCO', 'IBM', 'GE',
        'BA', 'MMM', 'FDX', 'SBUX', 'MCD', 'YUM', 'CMG', 'NFLX', 'DIS', 'CMCSA',
        'VZ', 'T', 'TMUS', 'CHTR', 'DISH', 'PARA', 'WBD', 'FOX', 'NWSA', 'NWS',
        'SPY', 'QQQ', 'IWM', 'DIA', 'VTI', 'VOO', 'VEA', 'VWO', 'BND', 'AGG',
        'GLD', 'SLV', 'USO', 'TLT', 'SHY', 'LQD', 'HYG', 'EMB', 'EFA', 'EEM',
        'XLE', 'XLF', 'XLK', 'XLV', 'XLI', 'XLP', 'XLY', 'XLU', 'XLB', 'XLRE'
    ];
    
    // Merge and remove duplicates
    $allTickers = array_unique(array_merge($sp500, $additional));
    
    return $allTickers;
}

/**
 * Check if a specific ticker has earnings today using Yahoo Finance API
 */
function hasEarningsToday($ticker, $date) {
    // Use Yahoo Finance API to check earnings
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?interval=1d&range=1d";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept: application/json',
                'Accept-Language: en-US,en;q=0.9'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['chart']['result'][0])) {
        return false;
    }
    
    $result = $data['chart']['result'][0];
    $meta = $result['meta'];
    
    // Check if there's earnings information
    if (isset($meta['earningsTimestamp'])) {
        $earningsDate = date('Y-m-d', $meta['earningsTimestamp']);
        return $earningsDate === $date;
    }
    
    // Alternative check: look for earnings in events
    if (isset($result['events']['earnings'])) {
        foreach ($result['events']['earnings'] as $earning) {
            if (isset($earning['timestamp'])) {
                $earningsDate = date('Y-m-d', $earning['timestamp']);
                if ($earningsDate === $date) {
                    return true;
                }
            }
        }
    }
    
    return false;
}
?>
