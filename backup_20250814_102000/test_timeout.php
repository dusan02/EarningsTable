<?php
error_reporting(-1); 
ini_set('display_errors',1);

echo "Testing MySQL connection with timeout...\n";

// Set timeout
set_time_limit(10);

try {
  echo "Attempting connection...\n";
  $t0 = microtime(true);
  
  $dsn = 'mysql:host=localhost;port=3306;charset=utf8mb4';
  $pdo = new PDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
    PDO::ATTR_TIMEOUT => 5, // 5 second timeout
  ]);
  
  echo "Connection successful!\n";
  $ver = $pdo->query('SELECT VERSION() AS v')->fetch()['v'] ?? '?';
  echo "OK (".number_format((microtime(true)-$t0)*1000,1)." ms) – MySQL: $ver\n";
  
} catch (Throwable $e) {
  echo "ERROR: ".$e->getMessage()."\n";
  echo "Error type: ".get_class($e)."\n";
}

echo "Test completed.\n";
?>
