<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== CHECKING FINNHUB EARNINGS TODAY ===\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n\n";
    
    // Get earnings calendar from Finnhub
    $finnhub = new Finnhub();
    
    echo "=== FETCHING EARNINGS CALENDAR FROM FINNHUB ===\n";
    
    // Get earnings calendar for today
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    
    // Extract earnings calendar from response
    $earningsData = $response['earningsCalendar'] ?? [];
    
    if ($earningsData && !empty($earningsData)) {
        echo "✅ Found " . count($earningsData) . " total earnings reports today\n\n";
        
        // Filter for tickers with estimates and time
        $significantTickers = [];
        foreach ($earningsData as $earning) {
            $hasEstimates = (isset($earning['epsEstimate']) && $earning['epsEstimate'] !== null) || 
                           (isset($earning['revenueEstimate']) && $earning['revenueEstimate'] !== null);
            $hasTime = isset($earning['hour']) && !empty($earning['hour']);
            
            if ($hasEstimates || $hasTime) {
                $significantTickers[] = $earning;
            }
        }
        
        echo "=== SIGNIFICANT TICKERS (with estimates or time) ===\n";
        echo "Ticker | EPS Est | Revenue Est | Time | Quarter | Year\n";
        echo "-------|---------|-------------|------|---------|------\n";
        
        foreach ($significantTickers as $earning) {
            $ticker = $earning['symbol'] ?? 'N/A';
            $epsEstimate = $earning['epsEstimate'] ?? 'N/A';
            $revenueEstimate = $earning['revenueEstimate'] ?? 'N/A';
            $time = $earning['hour'] ?? 'N/A';
            $quarter = $earning['quarter'] ?? 'N/A';
            $year = $earning['year'] ?? 'N/A';
            
            echo sprintf("%-6s | %-7s | %-11s | %-4s | %-7s | %s\n", 
                $ticker, 
                $epsEstimate, 
                $revenueEstimate, 
                $time, 
                $quarter, 
                $year
            );
        }
        
        echo "\n=== SUMMARY ===\n";
        echo "📊 Total earnings reports: " . count($earningsData) . "\n";
        echo "🎯 Significant tickers (with estimates/time): " . count($significantTickers) . "\n";
        
        // Count by time
        $bmo = 0;
        $amc = 0;
        $tns = 0;
        
        foreach ($significantTickers as $earning) {
            $time = $earning['hour'] ?? '';
            if (strpos($time, 'bmo') !== false || strpos($time, 'BMO') !== false) {
                $bmo++;
            } elseif (strpos($time, 'amc') !== false || strpos($time, 'AMC') !== false) {
                $amc++;
            } else {
                $tns++;
            }
        }
        
        echo "🌅 Before Market Open (BMO): {$bmo}\n";
        echo "🌆 After Market Close (AMC): {$amc}\n";
        echo "⏰ Time Not Specified (TNS): {$tns}\n";
        
        // Show top tickers by revenue estimate
        echo "\n=== TOP TICKERS BY REVENUE ESTIMATE ===\n";
        $topByRevenue = array_filter($significantTickers, function($e) {
            return $e['revenueEstimate'] !== null && $e['revenueEstimate'] > 0;
        });
        
        usort($topByRevenue, function($a, $b) {
            return ($b['revenueEstimate'] ?? 0) - ($a['revenueEstimate'] ?? 0);
        });
        
        $top10 = array_slice($topByRevenue, 0, 10);
        
        foreach ($top10 as $earning) {
            $ticker = $earning['symbol'];
            $revenue = $earning['revenueEstimate'];
            $time = $earning['hour'] ?? 'TNS';
            echo "{$ticker}: $" . number_format($revenue) . " ({$time})\n";
        }
        
    } else {
        echo "❌ No earnings data found for today\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
