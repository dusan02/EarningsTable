<?php
require_once 'D:/xampp/htdocs/earnings-table/config.php';

$sql = "CREATE TABLE IF NOT EXISTS SharesOutstanding (
    ticker CHAR(10) NOT NULL PRIMARY KEY,
    shares_outstanding BIGINT NOT NULL,
    fetched_on DATE NOT NULL,
    INDEX idx_fetched_on (fetched_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$pdo->exec($sql);
echo "✅ SharesOutstanding table created successfully!\n";
?> 