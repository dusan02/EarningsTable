<?php
require_once 'config.php';

echo "=== TESTING TICKER FILTER ===\n";

function looksCoreUsTicker(string $ticker): bool {
    if (strlen($ticker) > 5) return false;
    
    $upper = strtoupper($ticker);
    if (str_contains($upper, '-') || str_contains($upper, '.')) return false;
    if (str_contains($upper, 'WS') || str_contains($upper, 'U')) return false;
    
    return true;
}

$testTickers = ['PANW', 'BHP', 'GMBXF', 'PPERY', 'TLK', 'MTNOY', 'BPHLY', 'VRNA', 'FN', 'XP', 'BTDR'];

foreach ($testTickers as $ticker) {
    $passes = looksCoreUsTicker($ticker);
    echo $ticker . " (" . strlen($ticker) . " chars): " . ($passes ? "PASSES" : "FILTERED OUT") . "\n";
}
?>
