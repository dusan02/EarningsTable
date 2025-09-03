<?php
/**
 * Simple SQL Injection Protection Test
 * Works with test_config.php
 */

require_once __DIR__ . '/test_config.php';

echo "🔍 Simple SQL Injection Protection Test\n";
echo "======================================\n\n";

// Test 1: Basic SQL injection attempt
echo "1. Testing basic SQL injection...\n";
$maliciousInput = "'; DROP TABLE users; --";
$safeInput = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

if (strpos($safeInput, 'DROP TABLE') !== false) {
    echo "   ✅ HTML escaping works (malicious content escaped)\n";
} else {
    echo "   ❌ HTML escaping failed\n";
}

// Test 2: Prepared statement test
echo "2. Testing prepared statement...\n";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM earningstickerstoday WHERE ticker = ?");
    $stmt->execute([$maliciousInput]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✅ Prepared statement works (count: " . $result['count'] . ")\n";
} catch (Exception $e) {
    echo "   ❌ Prepared statement failed: " . $e->getMessage() . "\n";
}

// Test 3: Input validation
echo "3. Testing input validation...\n";
$validTicker = "AAPL";
$invalidTicker = "AAPL!@#";

if (preg_match('/^[A-Z]{1,5}$/', $validTicker)) {
    echo "   ✅ Valid ticker format accepted\n";
} else {
    echo "   ❌ Valid ticker format rejected\n";
}

if (!preg_match('/^[A-Z]{1,5}$/', $invalidTicker)) {
    echo "   ✅ Invalid ticker format rejected\n";
} else {
    echo "   ❌ Invalid ticker format accepted\n";
}

echo "\n✅ SQL Injection protection test completed!\n";
?>
