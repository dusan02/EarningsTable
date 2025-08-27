<?php
require_once 'config.php';

echo "=== TESTING EPS & REVENUE DATA SOURCES ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// Test tickers
$testTickers = ['ATAT', 'MDB', 'BMO', 'BNS'];

foreach ($testTickers as $ticker) {
    echo "\n=== TESTING {$ticker} ===\n";
    
    // Source 1: Alpha Vantage EARNINGS (historical)
    echo "\n1. ALPHA VANTAGE EARNINGS (Historical):\n";
    $url = "https://www.alphavantage.co/query?function=EARNINGS&symbol={$ticker}&apikey=YFO8D5S1D0E4F80C";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['quarterlyEarnings'])) {
        echo "✅ Found quarterly earnings data\n";
        $latest = $data['quarterlyEarnings'][0] ?? null;
        if ($latest) {
            echo "  Latest: {$latest['fiscalDateEnding']} - EPS: {$latest['reportedEPS']}, Revenue: {$latest['reportedRevenue']}\n";
        }
    } else {
        echo "❌ No quarterly earnings data\n";
    }
    
    sleep(12); // Rate limiting
    
    // Source 2: Alpha Vantage OVERVIEW (current)
    echo "\n2. ALPHA VANTAGE OVERVIEW (Current):\n";
    $url = "https://www.alphavantage.co/query?function=OVERVIEW&symbol={$ticker}&apikey=YFO8D5S1D0E4F80C";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['Symbol'])) {
        echo "✅ Found company overview\n";
        echo "  EPS: {$data['EPS']}\n";
        echo "  Revenue TTM: {$data['RevenueTTM']}\n";
        echo "  Market Cap: {$data['MarketCapitalization']}\n";
    } else {
        echo "❌ No company overview data\n";
    }
    
    sleep(12); // Rate limiting
    
    // Source 3: Finnhub (for comparison)
    echo "\n3. FINNHUB (Today's Estimates):\n";
    try {
        require_once 'common/Finnhub.php';
        $finnhub = new Finnhub();
        $response = $finnhub->getEarningsCalendar('', $date, $date);
        $finnhubTickers = $response['earningsCalendar'] ?? [];
        
        $finnhubData = null;
        foreach ($finnhubTickers as $earning) {
            if ($earning['symbol'] === $ticker) {
                $finnhubData = $earning;
                break;
            }
        }
        
        if ($finnhubData) {
            echo "✅ Found in Finnhub today\n";
            echo "  EPS Estimate: {$finnhubData['epsEstimate']}\n";
            echo "  Revenue Estimate: {$finnhubData['revenueEstimate']}\n";
            echo "  Time: {$finnhubData['time']}\n";
        } else {
            echo "❌ Not in Finnhub today\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Finnhub error: " . $e->getMessage() . "\n";
    }
    
    // Source 4: Polygon (market data)
    echo "\n4. POLYGON (Market Data):\n";
    try {
        require_once 'common/api_functions.php';
        $marketData = getPolygonTickerDetails($ticker);
        $batchData = getPolygonBatchQuote([$ticker]);
        
        if ($marketData && $batchData && isset($batchData[$ticker])) {
            echo "✅ Found market data\n";
            $priceData = getCurrentPrice($batchData[$ticker]);
        $currentPrice = $priceData ? $priceData['price'] : 'N/A';
        echo "  Current Price: " . $currentPrice . "\n";
            echo "  Market Cap: {$marketData['market_cap']}\n";
            echo "  Company Name: {$marketData['name']}\n";
        } else {
            echo "❌ No market data\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Polygon error: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n";
}

echo "\n=== SUMMARY OF DATA SOURCES ===\n";
echo "For EPS & Revenue data:\n\n";

echo "1. ALPHA VANTAGE EARNINGS:\n";
echo "   ✅ Historical EPS/Revenue (quarterly/annual)\n";
echo "   ❌ No current estimates\n";
echo "   ⚠️  Rate limited (5 calls/min)\n\n";

echo "2. ALPHA VANTAGE OVERVIEW:\n";
echo "   ✅ Current EPS (TTM)\n";
echo "   ✅ Current Revenue (TTM)\n";
echo "   ❌ No estimates\n";
echo "   ⚠️  Rate limited (5 calls/min)\n\n";

echo "3. FINNHUB:\n";
echo "   ✅ Current EPS estimates\n";
echo "   ✅ Current Revenue estimates\n";
echo "   ✅ Today's earnings calendar\n";
echo "   ✅ No rate limits\n\n";

echo "4. POLYGON:\n";
echo "   ✅ Market cap, prices\n";
echo "   ❌ No EPS/Revenue data\n";
echo "   ✅ No rate limits\n\n";

echo "=== RECOMMENDATION ===\n";
echo "Best approach for EPS/Revenue data:\n";
echo "1. Use FINNHUB for current estimates (primary)\n";
echo "2. Use ALPHA VANTAGE for historical data (supplement)\n";
echo "3. Use POLYGON for market data (prices, market cap)\n";
?>
