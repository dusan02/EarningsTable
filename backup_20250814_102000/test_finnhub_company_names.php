<?php
require_once 'config.php';

$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "🔍 Testing Finnhub API for company names: {$date}\n";

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
    echo "❌ Failed to fetch data from Finnhub API\n";
    exit(1);
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ JSON decode error: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "✅ API Response received\n";

if (isset($data['earningsCalendar'])) {
    echo "📈 Earnings Calendar count: " . count($data['earningsCalendar']) . "\n";
    
    if (!empty($data['earningsCalendar'])) {
        echo "📋 First 5 records with company names:\n";
        for ($i = 0; $i < min(5, count($data['earningsCalendar'])); $i++) {
            $earning = $data['earningsCalendar'][$i];
            echo "  {$earning['symbol']}: ";
            
            // Check if company name exists
            if (isset($earning['companyName'])) {
                echo "Company: '{$earning['companyName']}'";
            } elseif (isset($earning['name'])) {
                echo "Name: '{$earning['name']}'";
            } else {
                echo "No company name found";
            }
            
            echo " | EPS: {$earning['epsActual']}/{$earning['epsEstimate']}";
            echo " | Revenue: {$earning['revenueActual']}/{$earning['revenueEstimate']}\n";
        }
        
        // Check all available fields
        echo "\n🔍 Available fields in first record:\n";
        $firstRecord = $data['earningsCalendar'][0];
        foreach ($firstRecord as $key => $value) {
            echo "  {$key}: " . (is_string($value) ? "'{$value}'" : $value) . "\n";
        }
    }
} else {
    echo "❌ No earningsCalendar found in response\n";
}
?>
