<?php
require_once 'D:/xampp/htdocs/earnings-table/config.php';

echo "🔍 CHECKING GILD AFTER SIZE FIX\n";
echo "===============================\n\n";

$stmt = $pdo->prepare("SELECT * FROM TodayEarningsMovements WHERE ticker = 'GILD'");
$stmt->execute();
$gild = $stmt->fetch(PDO::FETCH_ASSOC);

if ($gild) {
    echo "✅ GILD DATA:\n";
    echo "  🏢 Company: " . $gild['company_name'] . "\n";
    echo "  💰 Current Price: $" . number_format($gild['current_price'], 2) . "\n";
    echo "  📊 Market Cap: $" . number_format($gild['market_cap']) . "\n";
    echo "  📈 Market Cap (B$): $" . round($gild['market_cap'] / 1000000000, 1) . "B\n";
    echo "  📏 Size: " . $gild['size'] . " ✅\n";
    echo "  🎯 Shares Outstanding: " . number_format($gild['shares_outstanding']) . "\n";
    
    // Verify size calculation
    $calculatedSize = match (true) {
        $gild['market_cap'] > 10000000000 => 'Large',
        $gild['market_cap'] > 1000000000 => 'Mid',
        default => 'Small'
    };
    
    echo "  🔍 Calculated Size: " . $calculatedSize . "\n";
    echo "  ✅ Size Match: " . ($gild['size'] === $calculatedSize ? 'YES' : 'NO') . "\n";
    
} else {
    echo "❌ GILD not found in database\n";
}
?> 