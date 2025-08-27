<?php
require_once 'D:/xampp/htdocs/earnings-table/config.php';

echo "🔍 CHECKING LLY DATA AFTER ACCURATE UPDATE\n";
echo "=========================================\n\n";

$stmt = $pdo->prepare("SELECT * FROM TodayEarningsMovements WHERE ticker = 'LLY'");
$stmt->execute();
$lly = $stmt->fetch(PDO::FETCH_ASSOC);

if ($lly) {
    echo "✅ LLY DATA IN DATABASE:\n";
    echo "  🏢 Company: " . $lly['company_name'] . "\n";
    echo "  💰 Current Price: $" . number_format($lly['current_price'], 2) . "\n";
    echo "  📊 Market Cap: $" . number_format($lly['market_cap']) . "\n";
    echo "  📈 Market Cap (B$): $" . round($lly['market_cap'] / 1000000000, 1) . "B\n";
    echo "  🎯 Shares Outstanding: " . number_format($lly['shares_outstanding']) . "\n";
    echo "  📏 Size: " . $lly['size'] . "\n";
    echo "  📊 Market Cap Diff: $" . number_format($lly['market_cap_diff']) . "\n";
    echo "  📈 Market Cap Diff (B$): $" . round($lly['market_cap_diff_billions'], 2) . "B\n";
    echo "  📊 Price Change %: " . round($lly['price_change_percent'], 2) . "%\n";
    
} else {
    echo "❌ LLY not found in database\n";
}
?> 