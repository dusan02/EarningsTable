<?php
error_reporting(-1); 
ini_set('display_errors',1);

// Use simple test config
require_once __DIR__ . '/test_config.php';

try {
  $t0 = microtime(true);
  // $pdo is already created in test_config.php
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
    
    if (in_array('earningstickerstoday', array_column($tables,'Tables_in_earnings_db'))) {
      echo "✓ earningstickerstoday exists\n";
      $count = $pdo->query('SELECT COUNT(*) FROM earningstickerstoday')->fetchColumn();
      echo "Records: $count\n";
    } else {
      echo "✗ earningstickerstoday does not exist\n";
    }
    
    if (in_array('todayearningsmovements', array_column($tables,'Tables_in_earnings_db'))) {
      echo "✓ todayearningsmovements exists\n";
      $count = $pdo->query('SELECT COUNT(*) FROM todayearningsmovements')->fetchColumn();
      echo "Records: $count\n";
    } else {
      echo "✗ todayearningsmovements does not exist\n";
    }
    
  } else {
    echo "✗ earnings_db does not exist\n";
  }
  
} catch (Throwable $e) {
  echo "ERROR: ".$e->getMessage()."\n";
  echo "Error type: ".get_class($e)."\n";
}
?>
