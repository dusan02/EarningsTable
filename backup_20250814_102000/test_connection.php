<?php
error_reporting(-1); 
ini_set('display_errors',1);

echo "Testing MySQL connection...\n";

try {
  $t0 = microtime(true);
  $dsn = 'mysql:host=localhost;port=3306;charset=utf8mb4';
  $pdo = new PDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
  ]);
  $ver = $pdo->query('SELECT VERSION() AS v')->fetch()['v'] ?? '?';
  echo "OK (".number_format((microtime(true)-$t0)*1000,1)." ms) – MySQL: $ver\n";
  
  // Show databases
  $dbs = $pdo->query('SHOW DATABASES')->fetchAll();
  echo "Databases: ".implode(', ', array_column($dbs,'Database'))."\n";
  
} catch (Throwable $e) {
  echo "ERROR: ".$e->getMessage()."\n";
  echo "Error type: ".get_class($e)."\n";
}
?>
