<?php
/**
 * Security Monitoring Script
 * Monitoruje bezpečnostné udalosti a generuje reporty
 */

require_once __DIR__ . '/../config/env_loader.php';
require_once __DIR__ . '/../config/logger.php';

echo "🔒 Security Monitoring Report\n";
echo "============================\n\n";

// 1. Získaj štatistiky
echo "📊 Štatistiky za posledných 24 hodín:\n";
$logger = SecurityLogger::getInstance();
$stats = $logger->getStats(24);

echo "   Celkových udalostí: " . $stats['total_events'] . "\n";
echo "   Alertov: " . $stats['alerts'] . "\n\n";

// 2. Štatistiky podľa úrovne
if (!empty($stats['by_level'])) {
    echo "📈 Štatistiky podľa úrovne:\n";
    foreach ($stats['by_level'] as $level => $count) {
        $icon = match($level) {
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'error' => '❌',
            'critical' => '🚨',
            default => '📝'
        };
        echo "   $icon $level: $count\n";
    }
    echo "\n";
}

// 3. Štatistiky podľa udalosti
if (!empty($stats['by_event'])) {
    echo "🎯 Štatistiky podľa udalosti:\n";
    foreach ($stats['by_event'] as $event => $count) {
        $icon = match($event) {
            'login_failed' => '🔐',
            'sql_injection_attempt' => '💉',
            'xss_attempt' => '🕷️',
            'api_call' => '🌐',
            'file_access' => '📁',
            'database_change' => '🗄️',
            'application_error' => '💥',
            default => '📝'
        };
        echo "   $icon $event: $count\n";
    }
    echo "\n";
}

// 4. Top IP adresy
if (!empty($stats['by_ip'])) {
    echo "🌍 Top IP adresy:\n";
    arsort($stats['by_ip']);
    $topIPs = array_slice($stats['by_ip'], 0, 10, true);
    
    foreach ($topIPs as $ip => $count) {
        $status = match(true) {
            $count > 100 => '🚨 VYSOKÁ AKTIVITA',
            $count > 50 => '⚠️ STREDNÁ AKTIVITA',
            $count > 10 => '🔶 NORMÁLNA AKTIVITA',
            default => '✅ NÍZKA AKTIVITA'
        };
        echo "   $ip: $count udalostí - $status\n";
    }
    echo "\n";
}

// 5. Kontrola alertov
echo "🚨 Kontrola alertov:\n";
$alertFile = __DIR__ . '/../logs/security/alerts.log';
if (file_exists($alertFile)) {
    $alerts = file($alertFile, FILE_IGNORE_NEW_LINES);
    $recentAlerts = [];
    
    foreach ($alerts as $alert) {
        $data = json_decode($alert, true);
        if ($data && strtotime($data['timestamp']) >= time() - 3600) { // Posledná hodina
            $recentAlerts[] = $data;
        }
    }
    
    if (!empty($recentAlerts)) {
        echo "   Posledných " . count($recentAlerts) . " alertov:\n";
        foreach (array_slice($recentAlerts, -5) as $alert) {
            $time = date('H:i:s', strtotime($alert['timestamp']));
            echo "   [$time] {$alert['event']} z IP {$alert['ip']} ({$alert['count']}/{$alert['threshold']})\n";
        }
    } else {
        echo "   ✅ Žiadne recent alerty\n";
    }
} else {
    echo "   ✅ Žiadne alerty\n";
}

echo "\n";

// 6. Kontrola kritických udalostí
echo "🔍 Kontrola kritických udalostí:\n";
$criticalFile = __DIR__ . '/../logs/security/critical.log';
if (file_exists($criticalFile)) {
    $criticalEvents = file($criticalFile, FILE_IGNORE_NEW_LINES);
    $recentCritical = [];
    
    foreach ($criticalEvents as $event) {
        $data = json_decode($event, true);
        if ($data && strtotime($data['timestamp']) >= time() - 3600) {
            $recentCritical[] = $data;
        }
    }
    
    if (!empty($recentCritical)) {
        echo "   Posledných " . count($recentCritical) . " kritických udalostí:\n";
        foreach (array_slice($recentCritical, -3) as $event) {
            $time = date('H:i:s', strtotime($event['timestamp']));
            echo "   [$time] {$event['event']} z IP {$event['ip']}\n";
        }
    } else {
        echo "   ✅ Žiadne recent kritické udalosti\n";
    }
} else {
    echo "   ✅ Žiadne kritické udalosti\n";
}

echo "\n";

// 7. Kontrola výkonnostných problémov
echo "⚡ Kontrola výkonnostných problémov:\n";
$warningFile = __DIR__ . '/../logs/security/warning.log';
if (file_exists($warningFile)) {
    $warnings = file($warningFile, FILE_IGNORE_NEW_LINES);
    $performanceIssues = [];
    
    foreach ($warnings as $warning) {
        $data = json_decode($warning, true);
        if ($data && 
            strtotime($data['timestamp']) >= time() - 3600 && 
            in_array($data['event'], ['slow_operation', 'high_memory_usage'])) {
            $performanceIssues[] = $data;
        }
    }
    
    if (!empty($performanceIssues)) {
        echo "   Posledných " . count($performanceIssues) . " výkonnostných problémov:\n";
        foreach (array_slice($performanceIssues, -3) as $issue) {
            $time = date('H:i:s', strtotime($issue['timestamp']));
            if ($issue['event'] === 'slow_operation') {
                echo "   [$time] Pomalá operácia: {$issue['data']['operation']} ({$issue['data']['duration']}ms)\n";
            } else {
                echo "   [$time] Vysoké pamäťové využitie: {$issue['data']['operation']}\n";
            }
        }
    } else {
        echo "   ✅ Žiadne výkonnostné problémy\n";
    }
} else {
    echo "   ✅ Žiadne warning udalosti\n";
}

echo "\n";

// 8. Odporúčania
echo "💡 Odporúčania:\n";

$recommendations = [];

// Kontrola neúspešných prihlásení
$failedLogins = $stats['by_event']['login_failed'] ?? 0;
if ($failedLogins > 10) {
    $recommendations[] = "🔐 Vysoký počet neúspešných prihlásení ($failedLogins) - zváž implementáciu CAPTCHA";
}

// Kontrola SQL injection pokusov
$sqlInjectionAttempts = $stats['by_event']['sql_injection_attempt'] ?? 0;
if ($sqlInjectionAttempts > 0) {
    $recommendations[] = "💉 Detekované SQL injection pokusy ($sqlInjectionAttempts) - skontroluj IP adresy";
}

// Kontrola XSS pokusov
$xssAttempts = $stats['by_event']['xss_attempt'] ?? 0;
if ($xssAttempts > 0) {
    $recommendations[] = "🕷️ Detekované XSS pokusy ($xssAttempts) - skontroluj validáciu vstupov";
}

// Kontrola API abuse
$apiCalls = $stats['by_event']['api_call'] ?? 0;
if ($apiCalls > 1000) {
    $recommendations[] = "🌐 Vysoký počet API volaní ($apiCalls) - zváž optimalizáciu alebo rate limiting";
}

// Kontrola chýb
$errors = $stats['by_event']['application_error'] ?? 0;
if ($errors > 50) {
    $recommendations[] = "💥 Vysoký počet chýb ($errors) - skontroluj logy a oprav chyby";
}

if (!empty($recommendations)) {
    foreach ($recommendations as $recommendation) {
        echo "   $recommendation\n";
    }
} else {
    echo "   ✅ Všetko vyzerá v poriadku\n";
}

echo "\n";

// 9. Export do JSON (ak je požadovaný)
if (isset($argv[1]) && $argv[1] === '--json') {
    $export = [
        'timestamp' => date('Y-m-d H:i:s'),
        'stats' => $stats,
        'recommendations' => $recommendations
    ];
    
    echo json_encode($export, JSON_PRETTY_PRINT);
    echo "\n";
}

echo "🎉 Security monitoring report dokončený!\n";
?>
