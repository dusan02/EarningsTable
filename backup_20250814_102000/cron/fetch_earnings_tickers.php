<?php
/**
 * Optimized Earnings Fetcher
 * Streamlined version with essential functionality
 * Updated to use US Eastern Time for Finnhub API compatibility
 */

require_once __DIR__ . '/../config.php';

function fetchEarningsData($date) {
    $url = "https://finnhub.io/api/v1/calendar/earnings?from=$date&to=$date&token=" . FINNHUB_API_KEY;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => ['Accept: application/json'],
            'timeout' => 30,
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    
    return $data;
}

function getCompanyName($ticker) {
    $url = "https://finnhub.io/api/v1/stock/profile2?symbol={$ticker}&token=" . FINNHUB_API_KEY;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => ['Accept: application/json'],
            'timeout' => 5,
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return $ticker; // Fallback to ticker if API fails
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['name'])) {
        return $ticker; // Fallback to ticker if no name found
    }
    
    return $data['name'];
}

function parseEarningsData($finnhubData) {
    $earnings = [];
    $seen = [];
    
    if (!isset($finnhubData['earningsCalendar'])) {
        return $earnings;
    }
    
    foreach ($finnhubData['earningsCalendar'] as $earning) {
        $ticker = strtoupper($earning['symbol']);
        $key = $earning['date'] . '-' . $ticker;
        
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        
        $reportTime = match (strtolower($earning['hour'] ?? '')) {
            'bmo' => 'BMO',
            'amc' => 'AMC',
            default => 'TNS',
        };
        
        $earnings[] = [
            'report_date' => $earning['date'],
            'ticker' => $ticker,
            'report_time' => $reportTime,
            'eps_actual' => $earning['epsActual'] ?? null,
            'eps_estimate' => $earning['epsEstimate'] ?? null,
            'revenue_actual' => $earning['revenueActual'] ?? null,
            'revenue_estimate' => $earning['revenueEstimate'] ?? null,
        ];
    }
    
    return $earnings;
}

function saveEarningsToDatabase($pdo, $earnings, $date) {
    try {
        $pdo->beginTransaction();
        
        // Clear existing data
        $stmt = $pdo->prepare("DELETE FROM EarningsTickersToday WHERE report_date = ?");
        $stmt->execute([$date]);
        
        // Insert new data
        $stmt = $pdo->prepare("
            INSERT INTO EarningsTickersToday (
                report_date, ticker, report_time, 
                eps_actual, eps_estimate, revenue_actual, revenue_estimate
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($earnings as $earning) {
            $stmt->execute([
                $earning['report_date'],
                $earning['ticker'],
                $earning['report_time'],
                $earning['eps_actual'],
                $earning['eps_estimate'],
                $earning['revenue_actual'],
                $earning['revenue_estimate']
            ]);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function saveCompanyNamesToDatabase($pdo, $earnings, $date) {
    try {
        $pdo->beginTransaction();
        
        // Prepare statement for inserting/updating company names
        $stmt = $pdo->prepare("
            INSERT INTO TodayEarningsMovements (
                ticker, company_name, updated_at
            ) VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                company_name = VALUES(company_name),
                updated_at = NOW()
        ");
        
        $companyCount = 0;
        foreach ($earnings as $earning) {
            $ticker = $earning['ticker'];
            $companyName = getCompanyName($ticker);
            
            $stmt->execute([$ticker, $companyName]);
            $companyCount++;
            
            // Sleep to avoid rate limits (Finnhub has 60 calls/minute limit)
            if ($companyCount % 50 == 0) {
                sleep(2);
            }
        }
        
        $pdo->commit();
        return $companyCount;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Database error saving company names: " . $e->getMessage());
        return false;
    }
}

// Main execution - Use US Eastern Time for Finnhub API
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "🚀 EARNINGS FETCH: {$date} (US Eastern Time)\n";

$finnhubData = fetchEarningsData($date);

if (!$finnhubData) {
    echo "❌ Failed to fetch data\n";
    exit(1);
}

$earnings = parseEarningsData($finnhubData);

if (empty($earnings)) {
    echo "❌ No earnings data found\n";
    exit(1);
}

if (saveEarningsToDatabase($pdo, $earnings, $date)) {
    echo "✅ SUCCESS: " . count($earnings) . " earnings saved\n";
    
    // Now fetch and save company names
    echo "🏢 Fetching company names...\n";
    $companyCount = saveCompanyNamesToDatabase($pdo, $earnings, $date);
    
    if ($companyCount !== false) {
        echo "✅ SUCCESS: " . $companyCount . " company names saved\n";
    } else {
        echo "⚠️  Warning: Failed to save some company names\n";
    }
    
    // Additional company names update to ensure all tickers have names
    echo "🔄 Running additional company names update...\n";
    $additionalCount = updateCompanyNames($pdo, $date);
    if ($additionalCount !== false) {
        echo "✅ ADDITIONAL: " . $additionalCount . " company names updated\n";
    }
} else {
    echo "❌ Failed to save data\n";
    exit(1);
}
?> 