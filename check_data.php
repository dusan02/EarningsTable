<?php
echo "Starting data check...\n";

try {
    require_once 'config.php';
    echo "Config loaded successfully\n";
} catch (Exception $e) {
    echo "Error loading config: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== CHECKING DATA IN TABLES ===\n\n";

try {
    // Check EarningsTickersToday
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM EarningsTickersToday WHERE report_date = CURDATE()");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "EarningsTickersToday today: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "Error checking EarningsTickersToday: " . $e->getMessage() . "\n";
}

try {
    // Check TodayEarningsMovements
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "TodayEarningsMovements total: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "Error checking TodayEarningsMovements: " . $e->getMessage() . "\n";
}

try {
    // Check benzinga_guidance
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM benzinga_guidance");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "benzinga_guidance total: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "Error checking benzinga_guidance: " . $e->getMessage() . "\n";
}

try {
    // Check estimates_consensus
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM estimates_consensus");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "estimates_consensus total: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "Error checking estimates_consensus: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING API ENDPOINT ===\n";
$apiUrl = "http://localhost:8000/api/earnings-tickers-today.php";
echo "API URL: {$apiUrl}\n";
echo "Try accessing this URL in your browser or with curl\n";

echo "\n=== CHECKING CRON JOBS ===\n";
$cronFiles = [
    'cron/1_enhanced_master_cron.php',
    'cron/3_daily_data_setup_static.php',
    'cron/4_regular_data_updates_dynamic.php',
    'cron/5_benzinga_guidance_updates.php',
    'cron/6_estimates_consensus_updates.php'
];

foreach ($cronFiles as $cronFile) {
    if (file_exists($cronFile)) {
        echo "✓ {$cronFile} exists\n";
    } else {
        echo "✗ {$cronFile} missing\n";
    }
}

echo "\nData check completed.\n";
?>
