<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== DEBUG FINNHUB RESPONSE ===\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n\n";
    
    // Get earnings calendar from Finnhub
    $finnhub = new Finnhub();
    
    echo "=== RAW FINNHUB RESPONSE ===\n";
    
    // Get earnings calendar for today
    $earningsData = $finnhub->getEarningsCalendar('', $date, $date);
    
    echo "Response type: " . gettype($earningsData) . "\n";
    
    if (is_array($earningsData)) {
        echo "Array keys: " . implode(', ', array_keys($earningsData)) . "\n";
        echo "Array count: " . count($earningsData) . "\n";
        
        if (isset($earningsData['earningsCalendar'])) {
            echo "Found 'earningsCalendar' key\n";
            $calendar = $earningsData['earningsCalendar'];
            echo "Calendar count: " . count($calendar) . "\n";
            
            if (!empty($calendar)) {
                echo "First item keys: " . implode(', ', array_keys($calendar[0])) . "\n";
                echo "First item: " . json_encode($calendar[0], JSON_PRETTY_PRINT) . "\n";
            }
        } else {
            echo "No 'earningsCalendar' key found\n";
            if (!empty($earningsData)) {
                echo "First item keys: " . implode(', ', array_keys($earningsData[0])) . "\n";
                echo "First item: " . json_encode($earningsData[0], JSON_PRETTY_PRINT) . "\n";
            }
        }
    } else {
        echo "Response is not an array: " . var_export($earningsData, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
