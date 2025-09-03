<?php
echo "🔍 Testing Guidance Configuration\n";
echo "================================\n\n";

// Test 1: Load config
echo "1. Loading config.php...\n";
try {
    require_once 'config.php';
    echo "✅ config.php loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Error loading config.php: " . $e->getMessage() . "\n";
}

// Test 2: Check database connection
echo "\n2. Checking database connection...\n";
try {
    if (isset($pdo)) {
        echo "✅ \$pdo is available\n";
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch();
        echo "   Database: " . $result['db_name'] . "\n";
    } else {
        echo "❌ \$pdo is not available\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 3: Check BenzingaGuidance class
echo "\n3. Loading BenzingaGuidance class...\n";
try {
    require_once 'common/BenzingaGuidance.php';
    echo "✅ BenzingaGuidance class loaded\n";
} catch (Exception $e) {
    echo "❌ Error loading BenzingaGuidance: " . $e->getMessage() . "\n";
}

echo "\n✅ Test completed\n";
?>
