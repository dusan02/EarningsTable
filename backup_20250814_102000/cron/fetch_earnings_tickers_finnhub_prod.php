<?php
/**
 * Finnhub Daily Earnings Fetch - Production
 * Stiahne dnešný earnings kalendár (Finnhub) → zapíše do EarningsTickersToday
 * Spúšťa sa denne o 02:00
 */

require_once __DIR__ . '/../config.php';

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

logMessage("Starting Finnhub daily earnings fetch...");

try {
    // Get today's date in US Eastern Time
    $date = date('Y-m-d');
    
    // Fetch earnings data from Finnhub
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
        logMessage("ERROR: Failed to fetch data from Finnhub API");
        exit(1);
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage("ERROR: Invalid JSON response from Finnhub");
        exit(1);
    }
    
    if (!isset($data['earningsCalendar'])) {
        logMessage("ERROR: No earnings calendar data in response");
        exit(1);
    }
    
    // Clear existing data for today
    $stmt = $pdo->prepare("DELETE FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    logMessage("Cleared existing data for $date");
    
    // Insert new data
    $insertStmt = $pdo->prepare("
        INSERT INTO EarningsTickersToday 
        (report_date, ticker, report_time, eps_actual, eps_estimate, revenue_actual, revenue_estimate) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $count = 0;
    $seen = [];
    
    foreach ($data['earningsCalendar'] as $earning) {
        $ticker = strtoupper($earning['symbol']);
        $key = $earning['date'] . '-' . $ticker;
        
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        
        $reportTime = match (strtolower($earning['hour'] ?? '')) {
            'bmo' => 'BMO',
            'amc' => 'AMC',
            default => 'TNS',
        };
        
        $insertStmt->execute([
            $earning['date'],
            $ticker,
            $reportTime,
            $earning['epsActual'] ?? null,
            $earning['epsEstimate'] ?? null,
            $earning['revenueActual'] ?? null,
            $earning['revenueEstimate'] ?? null,
        ]);
        
        $count++;
    }
    
    logMessage("SUCCESS: Inserted $count earnings records for $date");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}

logMessage("Finnhub daily earnings fetch completed successfully");
?>
