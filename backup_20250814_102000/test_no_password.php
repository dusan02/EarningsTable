<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dsn = 'mysql:host=localhost;port=3306;dbname=mysql;charset=utf8mb4';
$user = 'root';
$pass = '';
$opts = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_TIMEOUT => 3,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$t0 = microtime(true);
try {
  $pdo = new PDO($dsn, $user, $pass, $opts);
  $t1 = microtime(true);
  echo "OK PDO connect in " . round(($t1-$t0)*1000) . " ms\n";
  $row = $pdo->query("SELECT 1 AS ok")->fetch();
  echo "Query: " . $row['ok'] . "\n";
  
  // Test databases
  $dbs = $pdo->query("SHOW DATABASES")->fetchAll();
  echo "Databases: " . implode(', ', array_column($dbs,'Database')) . "\n";
  
} catch (Throwable $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
}
?>
