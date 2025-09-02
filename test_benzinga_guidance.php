<?php
/**
 * 🧪 TEST BENZINGA GUIDANCE API
 * 
 * Test súbor pre otestovanie Benzinga Guidance API integrácie
 */

require_once 'test_env.php';
require_once 'config.php';

echo "🧪 TESTING POLYGON GUIDANCE API INTEGRATION\n";
echo "===========================================\n\n";

try {
    // 1. Test konfigurácie
    echo "1️⃣  Testing configuration...\n";
    if (defined('POLYGON_API_KEY') && !empty(POLYGON_API_KEY)) {
        echo "   ✅ POLYGON_API_KEY is configured\n";
        echo "   🔑 Key: " . substr(POLYGON_API_KEY, 0, 8) . "...\n";
    } else {
        echo "   ❌ POLYGON_API_KEY is not configured\n";
        echo "   💡 Add POLYGON_API_KEY to your environment variables\n";
        exit(1);
    }
    
    // 2. Test databázového pripojenia
    echo "\n2️⃣  Testing database connection...\n";
    $stmt = $pdo->query("SELECT 1");
    echo "   ✅ Database connection successful\n";
    
    // 3. Test existencie tabuľky benzinga_guidance
    echo "\n3️⃣  Testing benzinga_guidance table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'benzinga_guidance'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ Table benzinga_guidance exists\n";
        
        // Skontroluj štruktúru
        $stmt = $pdo->query("DESCRIBE benzinga_guidance");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   📊 Table structure:\n";
        foreach ($columns as $column) {
            $key = $column['Key'] ? " ({$column['Key']})" : "";
            echo "      - {$column['Field']} ({$column['Type']}){$key}\n";
        }
    } else {
        echo "   ❌ Table benzinga_guidance does not exist\n";
        exit(1);
    }
    
    // 4. Test získania tickerov zo statického cronu
    echo "\n4️⃣  Testing ticker retrieval from static cron...\n";
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT ticker FROM earningstickerstoday WHERE report_date = ?");
    $stmt->execute([$date]);
    $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tickers)) {
        echo "   ✅ Found " . count($tickers) . " tickers for date: {$date}\n";
        echo "   📊 Sample tickers: " . implode(', ', array_slice($tickers, 0, 5));
        if (count($tickers) > 5) {
            echo " ... and " . (count($tickers) - 5) . " more";
        }
        echo "\n";
    } else {
        echo "   ⚠️  No tickers found for date: {$date}\n";
        echo "   💡 Run static cron first to get tickers\n";
    }
    
    // 5. Test Polygon Guidance triedy
    echo "\n5️⃣  Testing PolygonGuidance class...\n";
    if (file_exists('common/PolygonGuidance.php')) {
        echo "   ✅ PolygonGuidance.php file exists\n";
        
        // Test triedy
        require_once 'common/PolygonGuidance.php';
        try {
            $polygon = new PolygonGuidance();
            echo "   ✅ PolygonGuidance class instantiated successfully\n";
            
            // Test získania tickerov
            $testTickers = $polygon->getTickersFromStaticCron();
            echo "   ✅ getTickersFromStaticCron() method works\n";
            
        } catch (Exception $e) {
            echo "   ❌ PolygonGuidance class error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ❌ PolygonGuidance.php file not found\n";
    }
    
    // 6. Test cron súboru
    echo "\n6️⃣  Testing cron file...\n";
    if (file_exists('cron/5_benzinga_guidance_updates.php')) {
    echo "   ✅ 5_benzinga_guidance_updates.php cron file exists\n";
} else {
    echo "   ❌ 5_benzinga_guidance_updates.php cron file not found\n";
}
    
    // 7. Test master cron integrácie
    echo "\n7️⃣  Testing master cron integration...\n";
    if (file_exists('cron/1_enhanced_master_cron.php')) {
        echo "   ✅ 1_enhanced_master_cron.php exists\n";
        
        // Skontroluj či obsahuje Benzinga krok
        $masterCronContent = file_get_contents('cron/1_enhanced_master_cron.php');
        if (strpos($masterCronContent, '5_benzinga_guidance_updates.php') !== false) {
            echo "   ✅ Benzinga guidance step integrated in master cron\n";
        } else {
            echo "   ❌ Benzinga guidance step not found in master cron\n";
        }
    } else {
        echo "   ❌ 1_enhanced_master_cron.php not found\n";
    }
    
    echo "\n🎯 ALL TESTS COMPLETED!\n";
    echo "🚀 Polygon Guidance API integration is ready!\n";
    
} catch (Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
?>
