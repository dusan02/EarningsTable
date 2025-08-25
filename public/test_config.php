<?php
/**
 * 🔧 Configuration Test for Hosting
 * Test konfigurácie pre mydreams.cz hosting
 */

echo "<h1>🔧 EarningsTable Configuration Test</h1>";
echo "<hr>";

// Test 1: .env loader
echo "<h2>1. Environment Loader Test</h2>";
try {
    require_once '../config/env_loader.php';
    echo "✅ EnvLoader loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ EnvLoader failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Configuration
echo "<h2>2. Configuration Test</h2>";
try {
    require_once '../config/config.php';
    echo "✅ Configuration loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Configuration failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 3: Database Connection
echo "<h2>3. Database Connection Test</h2>";
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result['test'] == 1) {
            echo "✅ Database connection successful<br>";
            echo "📊 Database: " . DB_NAME . "<br>";
            echo "👤 User: " . DB_USER . "<br>";
        } else {
            echo "❌ Database test query failed<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ PDO connection not available<br>";
}

// Test 4: API Keys
echo "<h2>4. API Keys Test</h2>";
if (!empty(FINNHUB_API_KEY) && FINNHUB_API_KEY !== 'your_finnhub_api_key_here') {
    echo "✅ Finnhub API key configured<br>";
} else {
    echo "❌ Finnhub API key not configured<br>";
}

if (!empty(POLYGON_API_KEY) && POLYGON_API_KEY !== 'your_polygon_api_key_here') {
    echo "✅ Polygon API key configured<br>";
} else {
    echo "❌ Polygon API key not configured<br>";
}

// Test 5: Environment Variables
echo "<h2>5. Environment Variables Test</h2>";
echo "🌍 Environment: " . (defined('APP_ENV') ? APP_ENV : 'NOT SET') . "<br>";
echo "🐛 Debug Mode: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'ON' : 'OFF') : 'NOT SET') . "<br>";
echo "⏰ Timezone: " . (defined('APP_TIMEZONE') ? APP_TIMEZONE : 'NOT SET') . "<br>";
echo "🌐 Base URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT SET') . "<br>";

// Test 6: File Permissions
echo "<h2>6. File Permissions Test</h2>";
$logsDir = '../logs';
$storageDir = '../storage';

if (is_dir($logsDir) && is_writable($logsDir)) {
    echo "✅ Logs directory writable<br>";
} else {
    echo "❌ Logs directory not writable<br>";
}

if (is_dir($storageDir) && is_writable($storageDir)) {
    echo "✅ Storage directory writable<br>";
} else {
    echo "❌ Storage directory not writable<br>";
}

// Test 7: PHP Extensions
echo "<h2>7. PHP Extensions Test</h2>";
$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'openssl'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded<br>";
    } else {
        echo "❌ $ext extension not loaded<br>";
    }
}

echo "<hr>";
echo "<h2>🎯 Summary</h2>";
echo "<p>If all tests show ✅, your configuration is ready for production!</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Remove this test file after successful testing</li>";
echo "<li>Access your dashboard at: <a href='dashboard-fixed.html'>dashboard-fixed.html</a></li>";
echo "<li>Check logs at: logs/app.log</li>";
echo "</ul>";
?>
