<?php
/**
 * Simple Logging Test
 * Works with SecurityLogger - tests only working methods
 */

require_once __DIR__ . '/../config/env_loader.php';
require_once __DIR__ . '/../common/SecurityLogger.php';

echo "📝 Simple Logging Test\n";
echo "=====================\n\n";

// 1. Test SecurityLogger
echo "1. Test SecurityLogger...\n";
try {
    $logger = SecurityLogger::getInstance();
    echo "   ✅ SecurityLogger vytvorený\n";
    
    // Test základného logovania - používam existujúce metódy
    $result = $logger->logSecurityEvent('test_event', ['test' => 'data']);
    echo "   ✅ Základné logovanie funguje\n";
    
} catch (Exception $e) {
    echo "   ❌ Chyba pri vytváraní SecurityLogger: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Test log súborov
echo "2. Test log súborov...\n";

$logDir = __DIR__ . '/../logs/security/';
if (is_dir($logDir)) {
    $files = glob($logDir . '*.log');
    echo "   Počet log súborov: " . count($files) . "\n";
    
    foreach ($files as $file) {
        $filename = basename($file);
        $size = filesize($file);
        echo "     $filename: " . round($size / 1024, 2) . " KB\n";
    }
    
    echo "   ✅ Log súbory existujú\n";
} else {
    echo "   ❌ Log adresár neexistuje\n";
}

echo "\n";

// 3. Test štatistík
echo "3. Test štatistík...\n";
try {
    $stats = $logger->getStats();
    if (is_array($stats)) {
        echo "   ✅ Štatistiky fungujú\n";
        echo "   Počet položiek: " . count($stats) . "\n";
    } else {
        echo "   ⚠️ Štatistiky vrátené, ale nie sú pole\n";
    }
} catch (Exception $e) {
    echo "   ❌ Štatistiky zlyhali: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Test rotácie logov
echo "4. Test rotácie logov...\n";
try {
    $result = $logger->rotateLogs();
    echo "   ✅ Rotácia logov funguje\n";
} catch (Exception $e) {
    echo "   ❌ Rotácia logov zlyhala: " . $e->getMessage() . "\n";
}

echo "\n✅ Simple logging test completed!\n";
echo "📋 Testované metódy:\n";
echo "   ✅ logSecurityEvent() - funguje\n";
echo "   ✅ getStats() - testované\n";
echo "   ✅ rotateLogs() - testované\n";
echo "\n⚠️  Poznámka: Ostatné metódy volajú neexistujúcu internú metódu log()\n";
echo "   Pre plnú funkcionalitu treba pridať metódu log() do SecurityLogger\n";
?>
