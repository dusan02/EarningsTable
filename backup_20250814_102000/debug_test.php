<?php
// DEBUG HEADER – vlož úplne na začiatok skriptu
error_reporting(-1);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('html_errors', '0');
ini_set('error_log', __DIR__ . '/logs/php_errors.log');
ob_implicit_flush(true);

set_error_handler(function($no,$str,$file,$line){
  fprintf(STDERR, "PHP ERROR [%d] %s in %s:%d\n", $no,$str,$file,$line);
});
set_exception_handler(function($e){
  fprintf(STDERR, "UNCAUGHT %s: %s\n%s\n", get_class($e), $e->getMessage(), $e->getTraceAsString());
});
register_shutdown_function(function(){
  $e = error_get_last();
  if ($e) fprintf(STDERR, "FATAL: %s in %s:%d\n", $e['message'], $e['file'], $e['line']);
});

echo "[DEBUG] Script start\n";

// Test PDO connection
echo "[DEBUG] Testing PDO connection...\n";

try {
    $dsn = 'mysql:host=127.0.0.1;port=3306;dbname=earnings_db;charset=utf8mb4';
    $opt = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, 'root', '', $opt);
    echo "[DEBUG] DB connection OK\n";
    
    // Test if database exists
    $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    echo "[DEBUG] Databases: " . implode(", ", $databases) . "\n";
    
    if (in_array('earnings_db', $databases)) {
        echo "[DEBUG] earnings_db exists\n";
        
        // Test tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "[DEBUG] Tables: " . implode(", ", $tables) . "\n";
        
        if (in_array('EarningsTickersToday', $tables)) {
            echo "[DEBUG] EarningsTickersToday exists\n";
            $count = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();
            echo "[DEBUG] Records in EarningsTickersToday: $count\n";
        } else {
            echo "[DEBUG] EarningsTickersToday does not exist\n";
        }
        
        if (in_array('TodayEarningsMovements', $tables)) {
            echo "[DEBUG] TodayEarningsMovements exists\n";
            $count = $pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
            echo "[DEBUG] Records in TodayEarningsMovements: $count\n";
        } else {
            echo "[DEBUG] TodayEarningsMovements does not exist\n";
        }
        
    } else {
        echo "[DEBUG] earnings_db does not exist\n";
    }
    
} catch (Throwable $e) {
    echo "[DEBUG] ERROR: " . $e->getMessage() . "\n";
    echo "[DEBUG] Error type: " . get_class($e) . "\n";
}

echo "[DEBUG] Script completed\n";
?>
