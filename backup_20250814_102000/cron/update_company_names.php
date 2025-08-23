<?php
/**
 * Update Company Names Cron
 * Updates company names for all earnings tickers daily
 * Uses Finnhub API to fetch company names
 */

require_once __DIR__ . '/../config.php';

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

function updateCompanyNames($pdo, $date) {
    try {
        // Get all tickers for today
        $stmt = $pdo->prepare("
            SELECT DISTINCT ticker 
            FROM EarningsTickersToday 
            WHERE report_date = ?
        ");
        $stmt->execute([$date]);
        $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tickers)) {
            echo "❌ No tickers found for date: {$date}\n";
            return false;
        }
        
        echo "📊 Found " . count($tickers) . " tickers to update\n";
        
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
        
        $updatedCount = 0;
        $skippedCount = 0;
        
        foreach ($tickers as $ticker) {
            $companyName = getCompanyName($ticker);
            
            // Only update if we got a real company name (not just the ticker)
            if ($companyName !== $ticker) {
                $stmt->execute([$ticker, $companyName]);
                $updatedCount++;
            } else {
                $skippedCount++;
            }
            
            // Sleep to avoid rate limits (Finnhub has 60 calls/minute limit)
            if (($updatedCount + $skippedCount) % 50 == 0) {
                sleep(2);
            }
        }
        
        $pdo->commit();
        
        echo "✅ SUCCESS: Updated {$updatedCount} company names, skipped {$skippedCount}\n";
        return $updatedCount;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Database error updating company names: " . $e->getMessage());
        echo "❌ Error: " . $e->getMessage() . "\n";
        return false;
    }
}

// Main execution
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "🏢 COMPANY NAMES UPDATE: {$date} (US Eastern Time)\n";

$result = updateCompanyNames($pdo, $date);

if ($result !== false) {
    echo "✅ COMPLETE: {$result} company names updated\n";
} else {
    echo "❌ FAILED: Company names update failed\n";
    exit(1);
}
?>
