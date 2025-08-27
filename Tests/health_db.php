<?php
error_reporting(-1); 
ini_set('display_errors',1);

// Test with hardcoded values first
$dsn = 'mysql:host=localhost;port=3306;dbname=earnings_db;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
  $t0 = microtime(true);
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
  ]);
  $ver = $pdo->query('SELECT VERSION() AS v')->fetch()['v'] ?? '?';
  $dbs = $pdo->query('SHOW DATABASES')->fetchAll();
  echo "OK (".number_format((microtime(true)-$t0)*1000,1)." ms) – MySQL: $ver\n";
  echo "Databases: ".implode(', ', array_column($dbs,'Database'))."\n";
  
  // Check if earnings_db exists
  if (in_array('earnings_db', array_column($dbs,'Database'))) {
    echo "✓ earnings_db exists\n";
    
    // Check tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll();
    echo "Tables: ".implode(', ', array_column($tables,'Tables_in_earnings_db'))."\n";
    
    if (in_array('EarningsTickersToday', array_column($tables,'Tables_in_earnings_db'))) {
      echo "✓ EarningsTickersToday exists\n";
      $count = $pdo->query('SELECT COUNT(*) FROM EarningsTickersToday')->fetchColumn();
      echo "Records: $count\n";
    } else {
      echo "✗ EarningsTickersToday does not exist\n";
    }
    
    if (in_array('TodayEarningsMovements', array_column($tables,'Tables_in_earnings_db'))) {
      echo "✓ TodayEarningsMovements exists\n";
      $count = $pdo->query('SELECT COUNT(*) FROM TodayEarningsMovements')->fetchColumn();
      echo "Records: $count\n";
    } else {
      echo "✗ TodayEarningsMovements does not exist\n";
    }
    
  } else {
    echo "✗ earnings_db does not exist\n";
  }
  
} catch (Throwable $e) {
  echo "ERROR: ".$e->getMessage()."\n";
  echo "Error type: ".get_class($e)."\n";
}
?>
