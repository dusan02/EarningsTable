<?php
require_once 'config.php';

echo "=== Testing AVGO Guidance vs Estimate Matching ===\n";

// Check AVGO in EarningsTickersToday
$stmt = $pdo->query("SELECT ticker, fiscal_period, fiscal_year, eps_estimate, revenue_estimate FROM earningstickerstoday WHERE ticker = 'AVGO' LIMIT 1");
$ett = $stmt->fetch();
echo "AVGO ETT: " . json_encode($ett) . "\n";

// Check AVGO guidance
$stmt = $pdo->query("SELECT ticker, fiscal_period, fiscal_year, estimated_eps_guidance, estimated_revenue_guidance, eps_guide_vs_consensus_pct, revenue_guide_vs_consensus_pct FROM benzinga_guidance WHERE ticker = 'AVGO' ORDER BY last_updated DESC LIMIT 3");
echo "\nAVGO Guidance Records:\n";
while($row = $stmt->fetch()) {
    echo json_encode($row) . "\n";
}

// Test API endpoint
echo "\n=== Testing API Endpoint ===\n";
$response = file_get_contents('http://localhost:8000/api/earnings-tickers-today.php');
$data = json_decode($response, true);

if ($data && isset($data['data'])) {
    $avgo = null;
    foreach ($data['data'] as $item) {
        if ($item['ticker'] === 'AVGO') {
            $avgo = $item;
            break;
        }
    }
    
    if ($avgo) {
        echo "AVGO API Response:\n";
        echo "EPS Guide: " . ($avgo['eps_guide'] ?? 'null') . "\n";
        echo "EPS Guide Surprise: " . ($avgo['eps_guide_surprise'] ?? 'null') . "\n";
        echo "EPS Guide Basis: " . ($avgo['eps_guide_basis'] ?? 'null') . "\n";
        echo "Revenue Guide: " . ($avgo['revenue_guide'] ?? 'null') . "\n";
        echo "Revenue Guide Surprise: " . ($avgo['revenue_guide_surprise'] ?? 'null') . "\n";
        echo "Revenue Guide Basis: " . ($avgo['revenue_guide_basis'] ?? 'null') . "\n";
    } else {
        echo "AVGO not found in API response\n";
    }
} else {
    echo "API response error\n";
}
?>
