<?php
$json = file_get_contents('http://localhost:8000/public/api/earnings-tickers-today.php');
$data = json_decode($json, true);

if (!$data || !isset($data['data'])) {
    echo "❌ Failed to get API data\n";
    echo "Response: " . $json . "\n";
    exit(1);
}

echo "=== API DATA DEBUG ===\n";
echo "Total records: " . count($data['data']) . "\n\n";

// Show first 5 records with revenue data
$count = 0;
foreach($data['data'] as $item) {
    if ($count >= 5) break;
    
    echo $item['ticker'] . ":\n";
    echo "  revenue_estimate: " . var_export($item['revenue_estimate'], true) . "\n";
    echo "  revenue_actual: " . var_export($item['revenue_actual'], true) . "\n";
    echo "  parseFloat(revenue_estimate): " . var_export(floatval($item['revenue_estimate']), true) . "\n";
    echo "  parseFloat(revenue_actual): " . var_export(floatval($item['revenue_actual']), true) . "\n";
    echo "\n";
    $count++;
}

// Show records with NULL revenue values
echo "=== RECORDS WITH NULL REVENUE ===\n";
$nullCount = 0;
foreach($data['data'] as $item) {
    if ($item['revenue_estimate'] === null && $item['revenue_actual'] === null) {
        echo $item['ticker'] . " ";
        $nullCount++;
        if ($nullCount % 10 == 0) echo "\n";
    }
}
echo "\nTotal NULL records: " . $nullCount . "\n";
?>
