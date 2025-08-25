<?php
/**
 * API Limits Monitor
 * Sleduje a reportuje API limity
 */

require_once __DIR__ . '/../config/env_loader.php';
require_once __DIR__ . '/../config/rate_limiter.php';
require_once __DIR__ . '/../config/api_wrapper.php';

echo "📊 API Limits Monitor\n";
echo "====================\n\n";

// 1. Získaj všetky API štatistiky
echo "🔍 Získavam API štatistiky...\n";
$allStats = ApiFactory::getAllStats();

if (empty($allStats)) {
    echo "⚠️ Žiadne aktívne API limitery\n";
    echo "   (API volania ešte neboli vykonané)\n\n";
} else {
    echo "✅ Našlo sa " . count($allStats) . " aktívnych limitrov\n\n";
}

// 2. Zobraz štatistiky pre každé API
foreach ($allStats as $apiKey => $stats) {
    echo "📈 $apiKey:\n";
    echo "   Aktuálne volania: {$stats['current']}/{$stats['limit']}\n";
    echo "   Zostávajúce: {$stats['remaining']}\n";
    echo "   Reset za: {$stats['reset_time']} sekúnd\n";
    echo "   Časové okno: {$stats['window']} sekúnd\n";
    
    // Urči stav
    $usagePercent = ($stats['current'] / $stats['limit']) * 100;
    if ($usagePercent >= 90) {
        echo "   🚨 STAV: Kritický (" . round($usagePercent, 1) . "%)\n";
    } elseif ($usagePercent >= 75) {
        echo "   ⚠️ STAV: Vysoký (" . round($usagePercent, 1) . "%)\n";
    } elseif ($usagePercent >= 50) {
        echo "   🔶 STAV: Stredný (" . round($usagePercent, 1) . "%)\n";
    } else {
        echo "   ✅ STAV: Normálny (" . round($usagePercent, 1) . "%)\n";
    }
    echo "\n";
}

// 3. Zobraz environment nastavenia
echo "⚙️ Environment nastavenia:\n";
$apiLimit = EnvLoader::get('API_RATE_LIMIT', 'N/A');
$apiWindow = EnvLoader::get('API_RATE_WINDOW', 'N/A');
$polygonLimit = EnvLoader::get('POLYGON_RATE_LIMIT', 'N/A');
$finnhubLimit = EnvLoader::get('FINNHUB_RATE_LIMIT', 'N/A');
$yahooLimit = EnvLoader::get('YAHOO_RATE_LIMIT', 'N/A');

echo "   Všeobecný limit: $apiLimit volaní za $apiWindow sekúnd\n";
echo "   Polygon limit: $polygonLimit volaní za minútu\n";
echo "   Finnhub limit: $finnhubLimit volaní za minútu\n";
echo "   Yahoo limit: $yahooLimit volaní za minútu\n\n";

// 4. Zobraz API log súbor
echo "📝 API Log súbor:\n";
$logFile = __DIR__ . '/../logs/api_calls.log';
if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    $logLines = count(file($logFile));
    echo "   Súbor: $logFile\n";
    echo "   Veľkosť: " . round($logSize / 1024, 2) . " KB\n";
    echo "   Počet záznamov: $logLines\n";
    
    // Zobraz posledných 5 záznamov
    if ($logLines > 0) {
        echo "   Posledných 5 API volaní:\n";
        $lines = file($logFile);
        $lastLines = array_slice($lines, -5);
        
        foreach ($lastLines as $line) {
            $data = json_decode($line, true);
            if ($data) {
                $time = $data['timestamp'];
                $api = $data['api'];
                $url = parse_url($data['url'], PHP_URL_PATH);
                $code = $data['http_code'];
                echo "     [$time] $api: $url (HTTP $code)\n";
            }
        }
    }
} else {
    echo "   ⚠️ API log súbor neexistuje\n";
}

echo "\n";

// 5. Kontrola úložiska rate limitov
echo "💾 Rate Limit úložisko:\n";
$storageDir = __DIR__ . '/../storage/rate_limits';
if (is_dir($storageDir)) {
    $files = glob($storageDir . '/*.json');
    echo "   Počet súborov: " . count($files) . "\n";
    
    if (!empty($files)) {
        echo "   Súbory:\n";
        foreach ($files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $data = json_decode(file_get_contents($file), true);
            $count = is_array($data) ? count($data) : 0;
            echo "     $filename: $count záznamov (" . round($size / 1024, 2) . " KB)\n";
        }
    }
} else {
    echo "   ⚠️ Úložisko neexistuje\n";
}

echo "\n";

// 6. Odporúčania
echo "💡 Odporúčania:\n";

$hasWarnings = false;
foreach ($allStats as $apiKey => $stats) {
    $usagePercent = ($stats['current'] / $stats['limit']) * 100;
    
    if ($usagePercent >= 90) {
        echo "   🚨 $apiKey: Kritické využitie! Zváž zvýšenie limitu.\n";
        $hasWarnings = true;
    } elseif ($usagePercent >= 75) {
        echo "   ⚠️ $apiKey: Vysoké využitie. Monitoruj pozorne.\n";
        $hasWarnings = true;
    }
}

if (!$hasWarnings) {
    echo "   ✅ Všetky API limity sú v norme\n";
}

// 7. Akčné kroky
echo "\n🔧 Akčné kroky:\n";
echo "   • Pravidelne kontroluj tento report\n";
echo "   • Nastav alerting pri vysokom využití\n";
echo "   • Optimalizuj API volania\n";
echo "   • Zváž caching pre často používané dáta\n";
echo "   • Monitoruj API logy pre anomálie\n";

echo "\n";

// 8. Export do JSON (ak je požadovaný)
if (isset($argv[1]) && $argv[1] === '--json') {
    $export = [
        'timestamp' => date('Y-m-d H:i:s'),
        'api_stats' => $allStats,
        'environment' => [
            'api_rate_limit' => $apiLimit,
            'api_rate_window' => $apiWindow,
            'polygon_rate_limit' => $polygonLimit,
            'finnhub_rate_limit' => $finnhubLimit,
            'yahoo_rate_limit' => $yahooLimit
        ],
        'log_file' => [
            'exists' => file_exists($logFile),
            'size' => file_exists($logFile) ? filesize($logFile) : 0,
            'lines' => file_exists($logFile) ? count(file($logFile)) : 0
        ]
    ];
    
    echo json_encode($export, JSON_PRETTY_PRINT);
    echo "\n";
}

echo "🎉 API monitoring dokončený!\n";
?>
