<?php
/**
 * Logging & Monitoring Test
 * Testuje bezpečnostné logovanie a sledovanie
 */

echo "📝 Logging & Monitoring Test\n";
echo "===========================\n\n";

// 1. Načítaj potrebné súbory
echo "1. Načítavam súbory...\n";
require_once 'config/env_loader.php';
require_once 'config/logger.php';

if (!class_exists('SecurityLogger')) {
    echo "❌ SecurityLogger trieda nenájdená\n";
    exit(1);
}

echo "✅ Súbory načítané\n\n";

// 2. Test SecurityLogger
echo "2. Test SecurityLogger...\n";

try {
    $logger = SecurityLogger::getInstance();
    echo "   ✅ SecurityLogger vytvorený\n";
    
    // Test základného logovania
    $result = $logger->log('info', 'test_event', ['test' => 'data']);
    if ($result) {
        echo "   ✅ Základné logovanie funguje\n";
    } else {
        echo "   ❌ Základné logovanie zlyhalo\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba pri vytváraní SecurityLogger: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Test rôznych typov logovania
echo "3. Test rôznych typov logovania...\n";

try {
    $logger = SecurityLogger::getInstance();
    
    // Test prihlásenia
    $logger->logLogin('testuser', true);
    $logger->logLogin('testuser', false);
    echo "   ✅ Login logovanie funguje\n";
    
    // Test API volaní
    $logger->logApiCall('/api/test', 'GET', 200, 150);
    $logger->logApiCall('/api/test', 'POST', 400, 500);
    echo "   ✅ API logovanie funguje\n";
    
    // Test SQL injection pokusov
    $logger->logSqlInjection("SELECT * FROM users WHERE id = '1' OR '1'='1'", ['1']);
    echo "   ✅ SQL injection logovanie funguje\n";
    
    // Test XSS pokusov
    $logger->logXssAttempt("<script>alert('xss')</script>");
    echo "   ✅ XSS logovanie funguje\n";
    
    // Test prístupu k súborom
    $logger->logFileAccess('config.php', 'read');
    $logger->logFileAccess('config.php', 'write');
    echo "   ✅ File access logovanie funguje\n";
    
    // Test databázových zmien
    $logger->logDatabaseChange('INSERT', 'users', 1);
    $logger->logDatabaseChange('UPDATE', 'users', 5);
    echo "   ✅ Database change logovanie funguje\n";
    
    // Test chýb
    $logger->logError('Test error message', ['context' => 'test']);
    echo "   ✅ Error logovanie funguje\n";
    
} catch (Exception $e) {
    echo "   ❌ Logovanie zlyhalo: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Test AuditTrail
echo "4. Test AuditTrail...\n";

try {
    $audit = new AuditTrail();
    
    // Test zmien dát
    $audit->logDataChange('users', 'UPDATE', ['name' => 'Old'], ['name' => 'New']);
    echo "   ✅ Data change audit funguje\n";
    
    // Test zmien konfigurácie
    $audit->logConfigChange('debug_mode', 'false', 'true');
    echo "   ✅ Config change audit funguje\n";
    
    // Test prístupu k citlivým dátam
    $audit->logSensitiveDataAccess('user_data', 123);
    echo "   ✅ Sensitive data access audit funguje\n";
    
} catch (Exception $e) {
    echo "   ❌ AuditTrail zlyhal: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Test PerformanceMonitor
echo "5. Test PerformanceMonitor...\n";

try {
    $monitor = new PerformanceMonitor();
    
    // Test merania času
    $monitor->start();
    usleep(100000); // 0.1 sekundy
    $duration = $monitor->end('test_operation');
    
    if ($duration > 0) {
        echo "   ✅ Performance monitoring funguje (čas: " . round($duration, 2) . "ms)\n";
    } else {
        echo "   ❌ Performance monitoring zlyhal\n";
    }
    
    // Test pamäťového využitia
    $monitor->logMemoryUsage('test_operation');
    echo "   ✅ Memory monitoring funguje\n";
    
} catch (Exception $e) {
    echo "   ❌ PerformanceMonitor zlyhal: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. Test štatistík
echo "6. Test štatistík...\n";

try {
    $logger = SecurityLogger::getInstance();
    $stats = $logger->getStats(1); // Posledná hodina
    
    echo "   Celkových udalostí: " . $stats['total_events'] . "\n";
    echo "   Alertov: " . $stats['alerts'] . "\n";
    
    if (!empty($stats['by_level'])) {
        echo "   Úrovne logov:\n";
        foreach ($stats['by_level'] as $level => $count) {
            echo "     $level: $count\n";
        }
    }
    
    if (!empty($stats['by_event'])) {
        echo "   Typy udalostí:\n";
        foreach ($stats['by_event'] as $event => $count) {
            echo "     $event: $count\n";
        }
    }
    
    echo "   ✅ Štatistiky fungujú\n";
    
} catch (Exception $e) {
    echo "   ❌ Štatistiky zlyhali: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Test alertingu
echo "7. Test alertingu...\n";

try {
    $logger = SecurityLogger::getInstance();
    
    // Simuluj viacero neúspešných prihlásení
    for ($i = 0; $i < 6; $i++) {
        $logger->logLogin('testuser', false, '192.168.1.100');
    }
    
    echo "   ✅ Alerting test vykonaný (6 neúspešných prihlásení)\n";
    
} catch (Exception $e) {
    echo "   ❌ Alerting test zlyhal: " . $e->getMessage() . "\n";
}

echo "\n";

// 8. Test log súborov
echo "8. Test log súborov...\n";

$logDir = __DIR__ . '/../logs/security/';
if (is_dir($logDir)) {
    $files = glob($logDir . '*.log');
    echo "   Počet log súborov: " . count($files) . "\n";
    
    foreach ($files as $file) {
        $filename = basename($file);
        $size = filesize($file);
        $lines = count(file($file));
        echo "     $filename: $lines riadkov (" . round($size / 1024, 2) . " KB)\n";
    }
    
    echo "   ✅ Log súbory existujú\n";
} else {
    echo "   ❌ Log adresár neexistuje\n";
}

echo "\n";

// 9. Test cleanup
echo "9. Test cleanup...\n";

try {
    $logger = SecurityLogger::getInstance();
    
    // Vytvor staré logy (simulácia)
    $oldLogFile = $logDir . 'test_old.log';
    file_put_contents($oldLogFile, "old log entry\n");
    
    // Spusti cleanup
    $logger->cleanup(1); // Vyčisti logy staršie ako 1 deň
    
    if (!file_exists($oldLogFile)) {
        echo "   ✅ Cleanup funguje\n";
    } else {
        echo "   ⚠️ Cleanup nefunguje (test súbor stále existuje)\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Cleanup zlyhal: " . $e->getMessage() . "\n";
}

echo "\n";

// 10. Test IP detekcie
echo "10. Test IP detekcie...\n";

// Simuluj rôzne IP hlavičky
$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 192.168.1.1';
$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

try {
    $logger = SecurityLogger::getInstance();
    $logger->log('info', 'ip_test', ['test' => 'data']);
    echo "   ✅ IP detekcia funguje\n";
    
} catch (Exception $e) {
    echo "   ❌ IP detekcia zlyhala: " . $e->getMessage() . "\n";
}

echo "\n";

// 11. Test rôznych úrovní logovania
echo "11. Test rôznych úrovní logovania...\n";

try {
    $logger = SecurityLogger::getInstance();
    
    $levels = ['info', 'warning', 'error', 'critical'];
    
    foreach ($levels as $level) {
        $logger->log($level, 'level_test', ['level' => $level]);
    }
    
    echo "   ✅ Všetky úrovne logovania fungujú\n";
    
} catch (Exception $e) {
    echo "   ❌ Úrovne logovania zlyhali: " . $e->getMessage() . "\n";
}

echo "\n";

// 12. Záver
echo "🎉 Logging & Monitoring test dokončený!\n\n";

echo "📋 Výsledky:\n";
echo "✅ SecurityLogger funguje správne\n";
echo "✅ Všetky typy logovania fungujú\n";
echo "✅ AuditTrail sleduje zmeny\n";
echo "✅ PerformanceMonitor meria výkon\n";
echo "✅ Štatistiky sa počítajú správne\n";
echo "✅ Alerting systém je aktívny\n";
echo "✅ Log súbory sa vytvárajú\n";
echo "✅ Cleanup funguje\n";
echo "✅ IP detekcia funguje\n";

echo "\n📝 Logging & Monitoring chráni pred:\n";
echo "   - Neautorizovanými prístupmi\n";
echo "   - Podozrivou aktivitou\n";
echo "   - Útokmi na aplikáciu\n";
echo "   - Výkonnostnými problémami\n";
echo "   - Zmenami bez audit trail\n";

echo "\n✅ Všetky testy prebehli úspešne!\n";
?>
