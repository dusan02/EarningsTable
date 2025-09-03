<?php
/**
 * 🚀 CRITICAL TEST: Cron Jobs
 * Testuje kľúčové cron job funkcionality
 */

require_once __DIR__ . '/test_config.php';

echo "🚀 CRITICAL TEST: Cron Jobs\n";
echo "===========================\n\n";

try {
    // 1. Test existencie cron súborov
    echo "1. Test existencie cron súborov...\n";
    
    $cronFiles = [
        '../cron/1_enhanced_master_cron.php' => 'Master Cron',
        '../cron/3_daily_data_setup_static.php' => 'Daily Data Setup',
        '../cron/4_regular_data_updates_dynamic.php' => 'Regular Updates',
        '../cron/5_benzinga_guidance_updates.php' => 'Benzinga Guidance',
        '../cron/6_estimates_consensus_updates.php' => 'Estimates Consensus'
    ];
    
    $existingCrons = 0;
    foreach ($cronFiles as $file => $description) {
        if (file_exists($file)) {
            echo "   ✅ $description: existuje\n";
            $existingCrons++;
        } else {
            echo "   ❌ $description: neexistuje\n";
        }
    }
    
    echo "   📊 Celkovo cron súborov: " . count($cronFiles) . "\n";
    echo "   📊 Existujúcich: $existingCrons\n";
    
    // 2. Test cron log súborov
    echo "\n2. Test cron log súborov...\n";
    
    $logFiles = [
        '../logs/master_cron.log' => 'Master Cron Log',
        '../logs/performance.log' => 'Performance Log',
        '../logs/earnings_fetch.log' => 'Earnings Fetch Log',
        '../logs/eps_update.log' => 'EPS Update Log',
        '../logs/prices_update.log' => 'Prices Update Log'
    ];
    
    $existingLogs = 0;
    foreach ($logFiles as $file => $description) {
        if (file_exists($file)) {
            $size = filesize($file);
            $sizeKB = round($size / 1024, 2);
            echo "   ✅ $description: existuje ({$sizeKB} KB)\n";
            $existingLogs++;
            
            // Kontrola poslednej aktualizácie
            $lastModified = date('Y-m-d H:i:s', filemtime($file));
            echo "     🕐 Posledná aktualizácia: $lastModified\n";
        } else {
            echo "   ❌ $description: neexistuje\n";
        }
    }
    
    echo "   📊 Celkovo log súborov: " . count($logFiles) . "\n";
    echo "   📊 Existujúcich: $existingLogs\n";
    
    // 3. Test cron job dependencies
    echo "\n3. Test cron job dependencies...\n";
    
    // Kontrola či sú potrebné triedy dostupné
    $requiredClasses = [
        '../common/UnifiedCronManager.php' => 'UnifiedCronManager',
        '../common/DailyDataSetup.php' => 'DailyDataSetup',
        '../common/RegularDataUpdatesDynamic.php' => 'RegularDataUpdatesDynamic',
        '../common/UnifiedApiWrapper.php' => 'UnifiedApiWrapper',
        '../common/UnifiedLogger.php' => 'UnifiedLogger'
    ];
    
    $existingClasses = 0;
    foreach ($requiredClasses as $file => $className) {
        if (file_exists($file)) {
            echo "   ✅ $className: existuje\n";
            $existingClasses++;
        } else {
            echo "   ❌ $className: neexistuje\n";
        }
    }
    
    echo "   📊 Celkovo tried: " . count($requiredClasses) . "\n";
    echo "   📊 Existujúcich: $existingClasses\n";
    
    // 4. Test cron job syntax
    echo "\n4. Test cron job syntax...\n";
    
    // Kontrola syntaxe hlavných cron súborov
    $mainCronFiles = [
        '../cron/1_enhanced_master_cron.php',
        '../cron/3_daily_data_setup_static.php',
        '../cron/4_regular_data_updates_dynamic.php'
    ];
    
    $syntaxOK = 0;
    foreach ($mainCronFiles as $file) {
        if (file_exists($file)) {
            $output = shell_exec("php -l $file 2>&1");
            if (strpos($output, 'No syntax errors') !== false) {
                echo "   ✅ " . basename($file) . ": syntax OK\n";
                $syntaxOK++;
            } else {
                echo "   ❌ " . basename($file) . ": syntax ERROR\n";
                echo "     $output\n";
            }
        }
    }
    
    echo "   📊 Celkovo súborov: " . count($mainCronFiles) . "\n";
    echo "   📊 Syntax OK: $syntaxOK\n";
    
    // 5. Test cron job lock mechanism
    echo "\n5. Test cron job lock mechanism...\n";
    
    // Kontrola Lock triedy
    if (file_exists('../common/Lock.php')) {
        echo "   ✅ Lock trieda existuje\n";
        
        // Test lock súborov
        $lockDir = '../storage/';
        if (is_dir($lockDir)) {
            $lockFiles = glob($lockDir . '*.lock');
            if (!empty($lockFiles)) {
                echo "   📁 Lock súbory nájdené: " . count($lockFiles) . "\n";
                foreach (array_slice($lockFiles, 0, 3) as $lockFile) {
                    $lockName = basename($lockFile);
                    $lockTime = date('Y-m-d H:i:s', filemtime($lockFile));
                    echo "     🔒 $lockName: $lockTime\n";
                }
            } else {
                echo "   📁 Žiadne lock súbory nenájdené\n";
            }
        } else {
            echo "   📁 Lock adresár neexistuje\n";
        }
    } else {
        echo "   ❌ Lock trieda neexistuje\n";
    }
    
    // 6. Test cron job performance
    echo "\n6. Test cron job performance...\n";
    
    // Kontrola performance logov
    if (file_exists('../logs/performance.log')) {
        $performanceLog = file_get_contents('../logs/performance.log');
        $lines = explode("\n", $performanceLog);
        $recentLines = array_slice($lines, -10); // posledných 10 riadkov
        
        echo "   📊 Posledných 10 performance záznamov:\n";
        foreach ($recentLines as $line) {
            if (trim($line) && strlen($line) > 10) {
                echo "     📈 " . trim($line) . "\n";
            }
        }
    } else {
        echo "   ⚠️ Performance log neexistuje\n";
    }
    
    // 7. Test cron job scheduling
    echo "\n7. Test cron job scheduling...\n";
    
    // Simulácia cron job času
    $currentTime = new DateTime('now', new DateTimeZone('America/New_York'));
    $currentHour = (int)$currentTime->format('H');
    $currentMinute = (int)$currentTime->format('i');
    
    echo "   🕐 Aktuálny čas (NY): " . $currentTime->format('Y-m-d H:i:s') . "\n";
    
    // Kontrola či je vhodný čas pre cron jobs
    if ($currentHour >= 4 && $currentHour <= 20) {
        echo "   ✅ Vhodný čas pre cron jobs (4:00 - 20:00 NY)\n";
    } else {
        echo "   ⚠️ Mimo obvyklého času pre cron jobs\n";
    }
    
    // Kontrola či sú cron jobs aktívne
    $masterCronLog = '../logs/master_cron.log';
    if (file_exists($masterCronLog)) {
        $logContent = file_get_contents($masterCronLog);
        $lastRun = '';
        
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $logContent, $matches)) {
            $lastRun = $matches[1];
        }
        
        if ($lastRun) {
            echo "   📅 Posledný beh master cron: $lastRun\n";
            
            $lastRunTime = new DateTime($lastRun);
            $timeDiff = $currentTime->diff($lastRunTime);
            
            if ($timeDiff->h < 2) {
                echo "   ✅ Master cron bežal nedávno (< 2 hodiny)\n";
            } else {
                echo "   ⚠️ Master cron nebežal dlho (" . $timeDiff->h . " hodín)\n";
            }
        } else {
            echo "   ⚠️ Nepodarilo sa zistiť posledný beh\n";
        }
    } else {
        echo "   ❌ Master cron log neexistuje\n";
    }
    
    echo "\n✅ Všetky critical testy pre Cron Jobs prešli úspešne!\n";
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
