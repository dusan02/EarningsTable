<?php
require_once 'config.php';

echo "=== DETAILED TICKER FILTER TEST ===\n";

function looksCoreUsTicker(string $ticker): bool {
    echo "Testing: $ticker\n";
    echo "  Length: " . strlen($ticker) . "\n";
    
    if (strlen($ticker) > 5) {
        echo "  ❌ FILTERED: Length > 5\n";
        return false;
    }
    
    $upper = strtoupper($ticker);
    echo "  Upper: $upper\n";
    
    if (str_contains($upper, '-') || str_contains($upper, '.')) {
        echo "  ❌ FILTERED: Contains - or .\n";
        return false;
    }
    
    if (str_contains($upper, 'WS') || str_contains($upper, 'W') || str_contains($upper, 'U')) {
        echo "  ❌ FILTERED: Contains WS, W, or U\n";
        return false;
    }
    
    echo "  ✅ PASSES\n";
    return true;
}

$testTickers = ['PANW', 'BHP', 'GMBXF', 'PPERY', 'TLK', 'MTNOY', 'BPHLY', 'VRNA', 'FN', 'XP', 'BTDR'];

foreach ($testTickers as $ticker) {
    echo "\n---\n";
    $passes = looksCoreUsTicker($ticker);
    echo "RESULT: " . ($passes ? "PASSES" : "FILTERED OUT") . "\n";
}
?>
