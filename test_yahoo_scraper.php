<?php
require_once 'config.php';
require_once 'common/YahooFinance.php';

echo "=== DETAILED YAHOO FINANCE TEST ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

try {
    $yahoo = new YahooFinance();
    
    // Test the URL directly
    $url = "https://finance.yahoo.com/calendar/earnings?day={$date}";
    echo "Testing URL: {$url}\n\n";
    
    // Test the request
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ],
            'timeout' => 30
        ]
    ]);
    
    $content = file_get_contents($url, false, $context);
    
    if ($content === false) {
        echo "❌ Failed to fetch Yahoo Finance page\n";
    } else {
        echo "✅ Successfully fetched Yahoo Finance page\n";
        echo "Content length: " . strlen($content) . " bytes\n\n";
        
        // Look for earnings data in the content
        if (preg_match('/"earningsCalendar":\s*(\[.*?\])/s', $content, $matches)) {
            echo "✅ Found earningsCalendar JSON data\n";
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                echo "✅ Parsed JSON data: " . count($jsonData) . " earnings\n";
                
                if (!empty($jsonData)) {
                    echo "\nFirst 5 earnings:\n";
                    for ($i = 0; $i < min(5, count($jsonData)); $i++) {
                        $earning = $jsonData[$i];
                        echo "  {$earning['symbol']} - {$earning['companyName']} - EPS: {$earning['epsEstimate']}\n";
                    }
                }
            } else {
                echo "❌ Failed to parse JSON data\n";
            }
        } else {
            echo "❌ No earningsCalendar JSON found in page\n";
            
            // Look for any earnings-related content
            if (strpos($content, 'earnings') !== false) {
                echo "✅ Found 'earnings' keyword in page\n";
            }
            
            if (strpos($content, 'calendar') !== false) {
                echo "✅ Found 'calendar' keyword in page\n";
            }
            
            // Show a small sample of the content
            echo "\nPage content sample (first 500 chars):\n";
            echo substr($content, 0, 500) . "...\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== END TEST ===\n";
?>
