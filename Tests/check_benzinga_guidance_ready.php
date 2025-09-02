<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Benzinga.php';

echo "=== BENZINGA GUIDANCE READINESS CHECK ===\n";

// 1) Check config keys
echo "Config: ";
if (empty(BENZINGA_API_KEY)) {
    echo "BENZINGA_API_KEY is empty (OK for now, required later)\n";
} else {
    echo "BENZINGA_API_KEY is set\n";
}

// 2) Check table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'BenzingaCorporateGuidance'");
if ($stmt->rowCount() > 0) {
    echo "✅ Table BenzingaCorporateGuidance exists\n";
} else {
    echo "❌ Table BenzingaCorporateGuidance missing. Run: php add_benzinga_guidance_table.php\n";
}

// 3) Client sanity
$client = new BenzingaClient();
echo "Client API key present: " . ($client->hasApiKey() ? 'yes' : 'no') . "\n";

// 4) Dry-run fetch (will fail without key)
$today = new DateTime('now', new DateTimeZone('America/New_York'));
$from = $today->format('Y-m-d');
$to = $today->format('Y-m-d');

if ($client->hasApiKey()) {
    $res = $client->getGuidance($from, $to, 1, 1);
    echo $res['success'] ? "✅ API reachable\n" : ("⚠️ API error: " . ($res['error'] ?? 'unknown') . "\n");
} else {
    echo "(Skip API call: no key)\n";
}

echo "\nReady. Once you set BENZINGA_API_KEY, you can fetch guidance.\n";
?>



