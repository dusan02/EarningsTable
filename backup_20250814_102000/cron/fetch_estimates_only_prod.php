<?php
/**
 * Estimates Update - Production
 * Aktualizuje EPS/Revenue estimates/actuals pre dnešné tickery
 * Spúšťa sa každých 5 minút
 */

require_once __DIR__ . '/../config.php';

// Log function
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $message\n";
}

logMessage("Starting estimates update...");

try {
    // Get today's date
    $date = date('Y-m-d');
    
    // Get today's tickers
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tickers)) {
        logMessage("No tickers found for today ($date)");
        exit(0);
    }
    
    logMessage("Found " . count($tickers) . " tickers for today");
    
    // Update estimates for each ticker
    $updateStmt = $pdo->prepare("
        UPDATE EarningsTickersToday 
        SET eps_actual = ?, eps_estimate = ?, revenue_actual = ?, revenue_estimate = ?
        WHERE report_date = ? AND ticker = ?
    ");
    
    $updated = 0;
    
    foreach ($tickers as $ticker) {
        // Fetch estimates from Finnhub
        $url = "https://finnhub.io/api/v1/calendar/earnings?from=$date&to=$date&symbol=$ticker&token=" . FINNHUB_API_KEY;
        
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => ['Accept: application/json'],
                'timeout' => 10,
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            logMessage("WARNING: Failed to fetch estimates for $ticker");
            continue;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['earningsCalendar'][0])) {
            continue;
        }
        
        $earning = $data['earningsCalendar'][0];
        
        $updateStmt->execute([
            $earning['epsActual'] ?? null,
            $earning['epsEstimate'] ?? null,
            $earning['revenueActual'] ?? null,
            $earning['revenueEstimate'] ?? null,
            $date,
            $ticker
        ]);
        
        $updated++;
    }
    
    logMessage("SUCCESS: Updated estimates for $updated tickers");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}

logMessage("Estimates update completed successfully");
?>
