<?php
// db_inspector.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/config.php'; // načíta $pdo premennú

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "✅ DB connection OK\n\n";

    // Zisti aktuálny názov DB
    $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Current DB: " . ($db ?: '(none)') . "\n\n";

    echo "Databases:\n";
    foreach ($pdo->query("SHOW DATABASES") as $row) {
        echo " - {$row[0]}\n";
    }
    echo "\n";

    // Prepnúť na earnings_db
    $pdo->exec("USE earnings_db");
    echo "Using DB: earnings_db\n\n";

    echo "Tables in earnings_db:\n";
    foreach ($pdo->query("SHOW TABLES") as $row) {
        echo " - {$row[0]}\n";
    }
    echo "\n";

    $tables = ['EarningsTickersToday','TodayEarningsMovements'];
    foreach ($tables as $t) {
        echo "DESCRIBE {$t}:\n";
        try {
            $stmt = $pdo->query("DESCRIBE {$t}");
            foreach ($stmt as $r) {
                printf("  %-24s %-20s %s\n", $r['Field'], $r['Type'], $r['Null'] === 'NO' ? 'NOT NULL' : 'NULL');
            }
        } catch (Throwable $e) {
            echo "  ⚠️  Table '{$t}' not found: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

} catch (Throwable $e) {
    echo "❌ DB ERROR: " . $e->getMessage() . "\n";
    if ($e instanceof PDOException) {
        $info = $e->errorInfo ?? [];
        if (!empty($info)) echo "SQLSTATE: {$info[0]}  Driver-Code: {$info[1]}  Message: {$info[2]}\n";
    }
    exit(1);
}
?>
