<?php
/**
 * Database Connection Test
 * Test file for mydreams.cz hosting
 */

// Prevent direct access if config doesn't exist
if (!file_exists('config.php')) {
    die('❌ config.php not found. Please copy config.example.php to config.php and configure it.');
}

require_once 'config.php';

echo "<h1>🔧 EarningsTable - Database Connection Test</h1>";
echo "<p>Testing connection to mydreams.cz hosting...</p>";

try {
    // Test database connection
    $pdo->query('SELECT 1');
    echo "✅ <strong>Database connection:</strong> SUCCESS<br>";
    
    // Test if tables exist
    $tables = ['EarningsTickersToday', 'TodayEarningsMovements', 'SharesOutstanding'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ <strong>Table $table:</strong> EXISTS<br>";
        } else {
            echo "❌ <strong>Table $table:</strong> MISSING<br>";
        }
    }
    
    // Test PHP version
    echo "✅ <strong>PHP version:</strong> " . PHP_VERSION . "<br>";
    
    // Test timezone
    echo "✅ <strong>Timezone:</strong> " . date_default_timezone_get() . "<br>";
    
    // Test if we can write to logs directory
    $logsDir = __DIR__ . '/logs';
    if (is_dir($logsDir) && is_writable($logsDir)) {
        echo "✅ <strong>Logs directory:</strong> WRITABLE<br>";
    } else {
        echo "❌ <strong>Logs directory:</strong> NOT WRITABLE<br>";
    }
    
    // Test if we can write to storage directory
    $storageDir = __DIR__ . '/storage';
    if (is_dir($storageDir) && is_writable($storageDir)) {
        echo "✅ <strong>Storage directory:</strong> WRITABLE<br>";
    } else {
        echo "❌ <strong>Storage directory:</strong> NOT WRITABLE<br>";
    }
    
    echo "<br><strong>🎉 All tests completed!</strong><br>";
    echo "<p>If all tests pass, your hosting is ready for EarningsTable.</p>";
    
} catch (PDOException $e) {
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage() . "<br>";
    echo "<p>Please check your database configuration in config.php</p>";
} catch (Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>If database tables are missing, import sql/setup_all_tables.sql</li>";
echo "<li>Set up cron jobs for automated data fetching</li>";
echo "<li>Test the dashboard at: <a href='public/dashboard-fixed.html'>Dashboard</a></li>";
echo "<li>Test API at: <a href='public/api/earnings-tickers-today.php'>API</a></li>";
echo "</ol>";
?>
