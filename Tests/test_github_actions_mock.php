<?php
/**
 * 🧪 GITHUB ACTIONS MOCK TEST
 * Jednoduchý test pre GitHub Actions bez databázy
 */

echo "🧪 GITHUB ACTIONS MOCK TEST\n";
echo "==========================\n\n";

$testResults = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0
];

/**
 * Test 1: PHP Environment
 */
function testPHPEnvironment() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 1: PHP Environment\n";
    
    try {
        $phpVersion = PHP_VERSION;
        $requiredVersion = '8.0.0';
        
        if (version_compare($phpVersion, $requiredVersion, '>=')) {
            echo "   ✅ PHP version OK: {$phpVersion}\n";
            $testResults['passed']++;
            return true;
        } else {
            echo "   ❌ PHP version too old: {$phpVersion} (required: {$requiredVersion})\n";
            $testResults['failed']++;
            return false;
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        return false;
    }
}

/**
 * Test 2: Required Extensions
 */
function testRequiredExtensions() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 2: Required Extensions\n";
    
    try {
        $requiredExtensions = ['curl', 'json', 'pdo', 'pdo_mysql'];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }
        
        if (empty($missingExtensions)) {
            echo "   ✅ All required extensions loaded\n";
            $testResults['passed']++;
            return true;
        } else {
            echo "   ❌ Missing extensions: " . implode(', ', $missingExtensions) . "\n";
            $testResults['failed']++;
            return false;
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        return false;
    }
}

/**
 * Test 3: File Structure
 */
function testFileStructure() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 3: File Structure\n";
    
    try {
        $requiredFiles = [
            'config.php',
            'composer.json',
            'README.md',
            'Tests/master_test.php'
        ];
        
        $missingFiles = [];
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                $missingFiles[] = $file;
            }
        }
        
        if (empty($missingFiles)) {
            echo "   ✅ All required files present\n";
            $testResults['passed']++;
            return true;
        } else {
            echo "   ❌ Missing files: " . implode(', ', $missingFiles) . "\n";
            $testResults['failed']++;
            return false;
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        return false;
    }
}

/**
 * Test 4: Basic PHP Functionality
 */
function testBasicPHPFunctionality() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 4: Basic PHP Functionality\n";
    
    try {
        // Test basic operations
        $testArray = [1, 2, 3, 4, 5];
        $sum = array_sum($testArray);
        
        if ($sum === 15) {
            echo "   ✅ Array operations working\n";
        } else {
            echo "   ❌ Array operations failed\n";
            $testResults['failed']++;
            return false;
        }
        
        // Test string operations
        $testString = "Hello World";
        $upperString = strtoupper($testString);
        
        if ($upperString === "HELLO WORLD") {
            echo "   ✅ String operations working\n";
        } else {
            echo "   ❌ String operations failed\n";
            $testResults['failed']++;
            return false;
        }
        
        // Test JSON operations
        $testData = ['test' => 'value', 'number' => 123];
        $jsonString = json_encode($testData);
        $decodedData = json_decode($jsonString, true);
        
        if ($decodedData === $testData) {
            echo "   ✅ JSON operations working\n";
        } else {
            echo "   ❌ JSON operations failed\n";
            $testResults['failed']++;
            return false;
        }
        
        $testResults['passed']++;
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        return false;
    }
}

/**
 * Test 5: Environment Variables
 */
function testEnvironmentVariables() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 5: Environment Variables\n";
    
    try {
        $envVars = [
            'DB_HOST' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'DB_NAME' => $_ENV['DB_NAME'] ?? 'earnings_db',
            'DB_USER' => $_ENV['DB_USER'] ?? 'root',
            'DB_PASS' => $_ENV['DB_PASS'] ?? 'root'
        ];
        
        echo "   📊 Environment variables:\n";
        foreach ($envVars as $key => $value) {
            echo "      {$key}: {$value}\n";
        }
        
        echo "   ✅ Environment variables accessible\n";
        $testResults['passed']++;
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        return false;
    }
}

// Run all tests
echo "🚀 Starting GitHub Actions Mock Tests...\n\n";

testPHPEnvironment();
testRequiredExtensions();
testFileStructure();
testBasicPHPFunctionality();
testEnvironmentVariables();

// Summary
echo "\n📊 GITHUB ACTIONS MOCK TEST SUMMARY\n";
echo "===================================\n";
echo "Total Tests: {$testResults['total']}\n";
echo "✅ Passed: {$testResults['passed']}\n";
echo "❌ Failed: {$testResults['failed']}\n";
echo "📈 Success Rate: " . round(($testResults['passed'] / $testResults['total']) * 100, 1) . "%\n\n";

if ($testResults['failed'] === 0) {
    echo "🎉 ALL GITHUB ACTIONS MOCK TESTS PASSED!\n";
    echo "✅ Environment is ready for full testing\n";
    exit(0);
} else {
    echo "⚠️ Some GitHub Actions mock tests failed.\n";
    echo "❌ Environment needs configuration\n";
    exit(1);
}
?>
