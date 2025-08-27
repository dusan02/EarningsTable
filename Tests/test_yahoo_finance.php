<?php
require_once 'config.php';

echo "=== TESTING YAHOO FINANCE API ===\n";

function getYahooFinanceData($ticker) {
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    return json_decode($response, true);
}

function getYahooEarningsData($ticker) {
    $url = "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$ticker}?modules=earnings,financialData,defaultKeyStatistics";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    return json_decode($response, true);
}

// Test tickers that are missing from Finnhub
$testTickers = ['BHP', 'PANW', 'GMBXF', 'PPERY', 'TLK', 'MTNOY', 'BPHLY', 'VRNA'];

foreach ($testTickers as $ticker) {
    echo "\n--- Testing {$ticker} ---\n";
    
    // Get basic quote data
    $quoteData = getYahooFinanceData($ticker);
    if ($quoteData && isset($quoteData['chart']['result'][0])) {
        $result = $quoteData['chart']['result'][0];
        $meta = $result['meta'];
        
        echo "✅ Quote data found:\n";
        echo "  Current Price: $" . number_format($meta['regularMarketPrice'], 2) . "\n";
        echo "  Previous Close: $" . number_format($meta['previousClose'], 2) . "\n";
        echo "  Market Cap: $" . number_format($meta['marketCap'] / 1000000000, 1) . "B\n";
        
        // Get earnings data
        $earningsData = getYahooEarningsData($ticker);
        if ($earningsData && isset($earningsData['quoteSummary']['result'][0])) {
            $earnings = $earningsData['quoteSummary']['result'][0];
            
            if (isset($earnings['earnings'])) {
                $nextEarnings = $earnings['earnings']['earningsDate'][0] ?? null;
                if ($nextEarnings) {
                    echo "  Next Earnings: " . date('Y-m-d', $nextEarnings['raw']) . "\n";
                }
            }
            
            if (isset($earnings['financialData'])) {
                $financial = $earnings['financialData'];
                echo "  EPS Estimate: $" . ($financial['targetMeanPrice'] ?? 'N/A') . "\n";
            }
        }
    } else {
        echo "❌ No data found for {$ticker}\n";
    }
    
    // Sleep to avoid rate limits
    sleep(1);
}

echo "\n=== YAHOO FINANCE TEST COMPLETE ===\n";
?>
