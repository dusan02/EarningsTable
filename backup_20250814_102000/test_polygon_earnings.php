<?php
require_once 'config.php';

// Polygon API test
$date = '2025-08-12';
$polygonApiKey = 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX';

echo "🔍 TESTING POLYGON EARNINGS API\n";
echo "📅 Date: $date\n";
echo "🔑 Using paid Polygon account\n\n";

// Test Polygon Earnings Calendar
$polygonUrl = "https://api.polygon.io/v2/reference/earnings/surprises?date=$date&apiKey=$polygonApiKey";

echo "🌐 Fetching Polygon data...\n";
$polygonResponse = file_get_contents($polygonUrl);

if ($polygonResponse === false) {
    echo "❌ Error fetching Polygon data\n";
    exit;
}

$polygonData = json_decode($polygonResponse, true);

if (isset($polygonData['results'])) {
    echo "✅ Polygon data received\n";
    echo "📊 Total companies: " . count($polygonData['results']) . "\n";
    
    // Count companies with EPS actual data
    $epsActualCount = 0;
    $revenueActualCount = 0;
    $epsEstimateCount = 0;
    $revenueEstimateCount = 0;
    
    foreach ($polygonData['results'] as $company) {
        if (!empty($company['eps_actual'])) {
            $epsActualCount++;
        }
        if (!empty($company['revenue_actual'])) {
            $revenueActualCount++;
        }
        if (!empty($company['eps_estimate'])) {
            $epsEstimateCount++;
        }
        if (!empty($company['revenue_estimate'])) {
            $revenueEstimateCount++;
        }
    }
    
    echo "📈 Companies with EPS Actual: $epsActualCount\n";
    echo "💰 Companies with Revenue Actual: $revenueActualCount\n";
    echo "📊 Companies with EPS Estimate: $epsEstimateCount\n";
    echo "📊 Companies with Revenue Estimate: $revenueEstimateCount\n";
    
    // Show first few companies
    echo "\n📋 Sample companies:\n";
    for ($i = 0; $i < min(5, count($polygonData['results'])); $i++) {
        $company = $polygonData['results'][$i];
        echo "- {$company['ticker']}: EPS Est: {$company['eps_estimate']}, Actual: {$company['eps_actual']}, Revenue Est: {$company['revenue_estimate']}, Actual: {$company['revenue_actual']}\n";
    }
    
} else {
    echo "❌ No results in Polygon data\n";
    echo "Response: " . substr($polygonResponse, 0, 200) . "...\n";
}

echo "\n🔍 COMPARISON WITH FINNHUB:\n";
echo "📊 Finnhub total companies: 417\n";
echo "📈 Finnhub EPS Actual: 168\n";
echo "💰 Finnhub Revenue Actual: 167\n";

if (isset($polygonData['results'])) {
    $polygonTotal = count($polygonData['results']);
    echo "\n📊 Polygon total companies: $polygonTotal\n";
    echo "📈 Polygon EPS Actual: $epsActualCount\n";
    echo "💰 Polygon Revenue Actual: $revenueActualCount\n";
    
    echo "\n📊 COMPARISON:\n";
    echo "Total companies: Finnhub (417) vs Polygon ($polygonTotal)\n";
    echo "EPS Actual: Finnhub (168) vs Polygon ($epsActualCount)\n";
    echo "Revenue Actual: Finnhub (167) vs Polygon ($revenueActualCount)\n";
    
    // Calculate percentages
    $finnhubEpsPercent = round((168/417)*100, 1);
    $polygonEpsPercent = round(($epsActualCount/$polygonTotal)*100, 1);
    $finnhubRevPercent = round((167/417)*100, 1);
    $polygonRevPercent = round(($revenueActualCount/$polygonTotal)*100, 1);
    
    echo "\n📈 PERCENTAGES:\n";
    echo "Finnhub EPS Actual: $finnhubEpsPercent% ($epsActualCount/$polygonTotal)\n";
    echo "Polygon EPS Actual: $polygonEpsPercent% ($epsActualCount/$polygonTotal)\n";
    echo "Finnhub Revenue Actual: $finnhubRevPercent% (167/417)\n";
    echo "Polygon Revenue Actual: $polygonRevPercent% ($revenueActualCount/$polygonTotal)\n";
}
?>
