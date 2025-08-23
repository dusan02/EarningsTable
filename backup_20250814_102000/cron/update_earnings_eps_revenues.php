<?php
/**
 * Update Earnings Data Cron
 * Updates EPS/Revenue data every 5 minutes
 * Uses Finnhub API for real-time earnings data
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

function parseEarningsData($finnhubData) {
    $earnings = [];
    $seen = [];
    $withActualData = 0;

    if (!isset($finnhubData['earningsCalendar'])) {
        return $earnings;
    }

    foreach ($finnhubData['earningsCalendar'] as $earning) {
        $ticker = strtoupper($earning['symbol']);
        $key = $earning['date'] . '-' . $ticker;

        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        // Only include records with actual data
        $hasEpsActual = !empty($earning['epsActual']) && $earning['epsActual'] !== '/';
        $hasRevenueActual = !empty($earning['revenueActual']) && $earning['revenueActual'] !== '/';
        
        if ($hasEpsActual || $hasRevenueActual) {
            $withActualData++;
        }

        $reportTime = match (strtolower($earning['hour'] ?? '')) {
            'bmo' => 'BMO',
            'amc' => 'AMC',
            default => 'TNS',
        };

        $earnings[] = [
            'report_date' => $earning['date'],
            'ticker' => $ticker,
            'report_time' => $reportTime,
            'eps_actual' => $hasEpsActual ? $earning['epsActual'] : null,
            'eps_estimate' => !empty($earning['epsEstimate']) && $earning['epsEstimate'] !== '/' ? $earning['epsEstimate'] : null,
            'revenue_actual' => $hasRevenueActual ? $earning['revenueActual'] : null,
            'revenue_estimate' => !empty($earning['revenueEstimate']) && $earning['revenueEstimate'] !== '/' ? $earning['revenueEstimate'] : null,
        ];
    }

    echo "📊 Found {$withActualData} records with actual data out of " . count($finnhubData['earningsCalendar']) . " total\n";
    return $earnings;
}

function updateEarningsData($pdo, $earnings, $date) {
    try {
        $pdo->beginTransaction();

        // Update existing records with new EPS/Revenue data
        $stmt = $pdo->prepare("
            UPDATE EarningsTickersToday 
            SET 
                eps_actual = ?,
                eps_estimate = ?,
                revenue_actual = ?,
                revenue_estimate = ?
            WHERE report_date = ? AND ticker = ?
        ");

        $updatedCount = 0;
        foreach ($earnings as $earning) {
            $stmt->execute([
                $earning['eps_actual'],
                $earning['eps_estimate'],
                $earning['revenue_actual'],
                $earning['revenue_estimate'],
                $earning['report_date'],
                $earning['ticker']
            ]);
            
            if ($stmt->rowCount() > 0) {
                $updatedCount++;
            }
        }

        $pdo->commit();
        return $updatedCount;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Main execution - Use US Eastern Time for Finnhub API
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "🔄 EARNINGS UPDATE: {$date} (US Eastern Time)\n";

$finnhubData = fetchEarningsData($date);

if (!$finnhubData) {
    echo "❌ Failed to fetch data\n";
    exit(1);
}

$earnings = parseEarningsData($finnhubData);

if (empty($earnings)) {
    echo "❌ No earnings data with actual results found\n";
    exit(1);
}

$updatedCount = updateEarningsData($pdo, $earnings, $date);

if ($updatedCount !== false) {
    echo "✅ SUCCESS: Updated {$updatedCount} earnings records\n";
} else {
    echo "❌ Failed to update data\n";
    exit(1);
}
?> 