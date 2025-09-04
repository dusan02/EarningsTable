<?php
/**
 * 🔒 TEST: Data Sanitization
 * Testuje sanitizáciu dát
 */

require_once __DIR__ . '/test_config.php';

echo "🔒 TEST: Data Sanitization\n";
echo "=========================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Sanitizácia HTML tagov
echo "🔒 Test 1: Sanitizácia HTML tagov\n";
echo "--------------------------------\n";

$totalTests++;
try {
    $testHtmlInputs = [
        '<script>alert("XSS")</script>' => 'alert("XSS")',
        '<img src="x" onerror="alert(1)">' => '<img src="x">',
        '<div onclick="alert(1)">Click me</div>' => '<div>Click me</div>',
        '<a href="javascript:alert(1)">Link</a>' => '<a>Link</a>',
        '<iframe src="javascript:alert(1)"></iframe>' => '',
        '<object data="javascript:alert(1)"></object>' => '',
        '<embed src="javascript:alert(1)">' => '',
        '<form action="javascript:alert(1)"><input type="submit"></form>' => '<form><input type="submit"></form>',
        '<input onfocus="alert(1)" autofocus>' => '<input autofocus>',
        '<select onchange="alert(1)"><option>Test</option></select>' => '<select><option>Test</option></select>',
        '<textarea onblur="alert(1)">Test</textarea>' => '<textarea>Test</textarea>',
        '<button onclick="alert(1)">Click</button>' => '<button>Click</button>',
        '<style>body{background:red}</style>' => '',
        '<link rel="stylesheet" href="javascript:alert(1)">' => '',
        '<meta http-equiv="refresh" content="0;url=javascript:alert(1)">' => '',
        '<svg onload="alert(1)"><rect width="100" height="100"/></svg>' => '<svg><rect width="100" height="100"/></svg>',
        '<math onmouseover="alert(1)"><mi>x</mi></math>' => '<math><mi>x</mi></math>',
        '<video onloadstart="alert(1)"><source src="test.mp4"></video>' => '<video><source src="test.mp4"></video>',
        '<audio oncanplay="alert(1)"><source src="test.mp3"></audio>' => '<audio><source src="test.mp3"></audio>',
        '<details ontoggle="alert(1)"><summary>Test</summary></details>' => '<details><summary>Test</summary></details>'
    ];
    
    $sanitizedCorrectly = 0;
    $totalInputs = count($testHtmlInputs);
    
    echo "   📋 Testovanie {$totalInputs} HTML sanitizácií:\n";
    
    foreach ($testHtmlInputs as $input => $expectedOutput) {
        $sanitized = sanitizeHtml($input);
        
        if ($sanitized === $expectedOutput) {
            $sanitizedCorrectly++;
            echo "   ✅ '{$input}' → '{$sanitized}'\n";
        } else {
            echo "   ❌ '{$input}' → '{$sanitized}' (Expected: '{$expectedOutput}')\n";
        }
    }
    
    $sanitizationRate = round(($sanitizedCorrectly / $totalInputs) * 100, 1);
    echo "   📊 Sanitization rate: {$sanitizedCorrectly}/{$totalInputs} ({$sanitizationRate}%)\n";
    
    if ($sanitizationRate >= 90) {
        echo "   ✅ Vysoká sanitizácia HTML tagov\n";
        $passedTests++;
        $testResults[] = ['test' => 'HTML Tag Sanitization', 'status' => 'PASS', 'value' => $sanitizationRate];
    } elseif ($sanitizationRate >= 70) {
        echo "   ⚠️  Priemerná sanitizácia HTML tagov\n";
        $testResults[] = ['test' => 'HTML Tag Sanitization', 'status' => 'WARNING', 'value' => $sanitizationRate];
    } else {
        echo "   ❌ Slabá sanitizácia HTML tagov\n";
        $testResults[] = ['test' => 'HTML Tag Sanitization', 'status' => 'FAIL', 'value' => $sanitizationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'HTML Tag Sanitization', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Sanitizácia SQL injection patterns
echo "\n🔒 Test 2: Sanitizácia SQL injection patterns\n";
echo "--------------------------------------------\n";

$totalTests++;
try {
    $testSqlInputs = [
        "'; DROP TABLE users; --" => "''; DROP TABLE users; --",
        "' OR '1'='1" => "'' OR ''1''=''1",
        "' UNION SELECT * FROM users --" => "'' UNION SELECT * FROM users --",
        "'; INSERT INTO users VALUES ('hacker', 'password'); --" => "''; INSERT INTO users VALUES (''hacker'', ''password''); --",
        "' OR 1=1 --" => "'' OR 1=1 --",
        "'; UPDATE users SET password = 'hacked'; --" => "''; UPDATE users SET password = ''hacked''; --",
        "'; DELETE FROM users; --" => "''; DELETE FROM users; --",
        "' OR 'x'='x" => "'' OR ''x''=''x",
        "'; EXEC xp_cmdshell('dir'); --" => "''; EXEC xp_cmdshell(''dir''); --",
        "'; SELECT * FROM information_schema.tables; --" => "''; SELECT * FROM information_schema.tables; --"
    ];
    
    $sanitizedCorrectly = 0;
    $totalInputs = count($testSqlInputs);
    
    echo "   📋 Testovanie {$totalInputs} SQL injection sanitizácií:\n";
    
    foreach ($testSqlInputs as $input => $expectedOutput) {
        $sanitized = sanitizeSql($input);
        
        if ($sanitized === $expectedOutput) {
            $sanitizedCorrectly++;
            echo "   ✅ '{$input}' → '{$sanitized}'\n";
        } else {
            echo "   ❌ '{$input}' → '{$sanitized}' (Expected: '{$expectedOutput}')\n";
        }
    }
    
    $sanitizationRate = round(($sanitizedCorrectly / $totalInputs) * 100, 1);
    echo "   📊 Sanitization rate: {$sanitizedCorrectly}/{$totalInputs} ({$sanitizationRate}%)\n";
    
    if ($sanitizationRate >= 90) {
        echo "   ✅ Vysoká sanitizácia SQL injection patterns\n";
        $passedTests++;
        $testResults[] = ['test' => 'SQL Injection Sanitization', 'status' => 'PASS', 'value' => $sanitizationRate];
    } elseif ($sanitizationRate >= 70) {
        echo "   ⚠️  Priemerná sanitizácia SQL injection patterns\n";
        $testResults[] = ['test' => 'SQL Injection Sanitization', 'status' => 'WARNING', 'value' => $sanitizationRate];
    } else {
        echo "   ❌ Slabá sanitizácia SQL injection patterns\n";
        $testResults[] = ['test' => 'SQL Injection Sanitization', 'status' => 'FAIL', 'value' => $sanitizationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'SQL Injection Sanitization', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Sanitizácia file paths
echo "\n🔒 Test 3: Sanitizácia file paths\n";
echo "--------------------------------\n";

$totalTests++;
try {
    $testFilePathInputs = [
        '../../../etc/passwd' => 'etc/passwd',
        '..\\..\\..\\windows\\system32\\config\\sam' => 'windows/system32/config/sam',
        '/etc/passwd' => 'etc/passwd',
        'C:\\Windows\\System32\\config\\sam' => 'Windows/System32/config/sam',
        '../../../../var/log/apache2/access.log' => 'var/log/apache2/access.log',
        '..\\..\\..\\..\\var\\log\\apache2\\access.log' => 'var/log/apache2/access.log',
        '/var/log/apache2/access.log' => 'var/log/apache2/access.log',
        'C:\\Program Files\\Apache\\logs\\access.log' => 'Program Files/Apache/logs/access.log',
        '../../../../home/user/.ssh/id_rsa' => 'home/user/.ssh/id_rsa',
        '..\\..\\..\\..\\home\\user\\.ssh\\id_rsa' => 'home/user/.ssh/id_rsa',
        '/home/user/.ssh/id_rsa' => 'home/user/.ssh/id_rsa',
        'C:\\Users\\user\\.ssh\\id_rsa' => 'Users/user/.ssh/id_rsa',
        '../../../../etc/shadow' => 'etc/shadow',
        '..\\..\\..\\..\\etc\\shadow' => 'etc/shadow',
        '/etc/shadow' => 'etc/shadow',
        'C:\\Windows\\System32\\drivers\\etc\\hosts' => 'Windows/System32/drivers/etc/hosts'
    ];
    
    $sanitizedCorrectly = 0;
    $totalInputs = count($testFilePathInputs);
    
    echo "   📋 Testovanie {$totalInputs} file path sanitizácií:\n";
    
    foreach ($testFilePathInputs as $input => $expectedOutput) {
        $sanitized = sanitizeFilePath($input);
        
        if ($sanitized === $expectedOutput) {
            $sanitizedCorrectly++;
            echo "   ✅ '{$input}' → '{$sanitized}'\n";
        } else {
            echo "   ❌ '{$input}' → '{$sanitized}' (Expected: '{$expectedOutput}')\n";
        }
    }
    
    $sanitizationRate = round(($sanitizedCorrectly / $totalInputs) * 100, 1);
    echo "   📊 Sanitization rate: {$sanitizedCorrectly}/{$totalInputs} ({$sanitizationRate}%)\n";
    
    if ($sanitizationRate >= 90) {
        echo "   ✅ Vysoká sanitizácia file paths\n";
        $passedTests++;
        $testResults[] = ['test' => 'File Path Sanitization', 'status' => 'PASS', 'value' => $sanitizationRate];
    } elseif ($sanitizationRate >= 70) {
        echo "   ⚠️  Priemerná sanitizácia file paths\n";
        $testResults[] = ['test' => 'File Path Sanitization', 'status' => 'WARNING', 'value' => $sanitizationRate];
    } else {
        echo "   ❌ Slabá sanitizácia file paths\n";
        $testResults[] = ['test' => 'File Path Sanitization', 'status' => 'FAIL', 'value' => $sanitizationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'File Path Sanitization', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Sanitizácia email adries
echo "\n🔒 Test 4: Sanitizácia email adries\n";
echo "----------------------------------\n";

$totalTests++;
try {
    $testEmailInputs = [
        'user@example.com' => 'user@example.com',
        'user+tag@example.com' => 'user+tag@example.com',
        'user.name@example.com' => 'user.name@example.com',
        'user_name@example.com' => 'user_name@example.com',
        'user-name@example.com' => 'user-name@example.com',
        'user123@example.com' => 'user123@example.com',
        'user@example-domain.com' => 'user@example-domain.com',
        'user@sub.example.com' => 'user@sub.example.com',
        'user@example.co.uk' => 'user@example.co.uk',
        'user@example.info' => 'user@example.info',
        'user@example.org' => 'user@example.org',
        'user@example.net' => 'user@example.net',
        'user@example.biz' => 'user@example.biz',
        'user@example.museum' => 'user@example.museum',
        'user@example.travel' => 'user@example.travel',
        'user@example.jobs' => 'user@example.jobs',
        'user@example.mobi' => 'user@example.mobi',
        'user@example.name' => 'user@example.name',
        'user@example.pro' => 'user@example.pro',
        'user@example.aero' => 'user@example.aero'
    ];
    
    $sanitizedCorrectly = 0;
    $totalInputs = count($testEmailInputs);
    
    echo "   📋 Testovanie {$totalInputs} email sanitizácií:\n";
    
    foreach ($testEmailInputs as $input => $expectedOutput) {
        $sanitized = sanitizeEmail($input);
        
        if ($sanitized === $expectedOutput) {
            $sanitizedCorrectly++;
            echo "   ✅ '{$input}' → '{$sanitized}'\n";
        } else {
            echo "   ❌ '{$input}' → '{$sanitized}' (Expected: '{$expectedOutput}')\n";
        }
    }
    
    $sanitizationRate = round(($sanitizedCorrectly / $totalInputs) * 100, 1);
    echo "   📊 Sanitization rate: {$sanitizedCorrectly}/{$totalInputs} ({$sanitizationRate}%)\n";
    
    if ($sanitizationRate >= 90) {
        echo "   ✅ Vysoká sanitizácia email adries\n";
        $passedTests++;
        $testResults[] = ['test' => 'Email Sanitization', 'status' => 'PASS', 'value' => $sanitizationRate];
    } elseif ($sanitizationRate >= 70) {
        echo "   ⚠️  Priemerná sanitizácia email adries\n";
        $testResults[] = ['test' => 'Email Sanitization', 'status' => 'WARNING', 'value' => $sanitizationRate];
    } else {
        echo "   ❌ Slabá sanitizácia email adries\n";
        $testResults[] = ['test' => 'Email Sanitization', 'status' => 'FAIL', 'value' => $sanitizationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Email Sanitization', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Sanitizácia URL adries
echo "\n🔒 Test 5: Sanitizácia URL adries\n";
echo "--------------------------------\n";

$totalTests++;
try {
    $testUrlInputs = [
        'https://example.com' => 'https://example.com',
        'http://example.com' => 'http://example.com',
        'https://example.com/path' => 'https://example.com/path',
        'https://example.com/path?param=value' => 'https://example.com/path?param=value',
        'https://example.com/path#fragment' => 'https://example.com/path#fragment',
        'https://example.com/path?param=value#fragment' => 'https://example.com/path?param=value#fragment',
        'https://sub.example.com' => 'https://sub.example.com',
        'https://example.com:8080' => 'https://example.com:8080',
        'https://example.com:8080/path' => 'https://example.com:8080/path',
        'https://example.com:8080/path?param=value' => 'https://example.com:8080/path?param=value',
        'https://example.com:8080/path#fragment' => 'https://example.com:8080/path#fragment',
        'https://example.com:8080/path?param=value#fragment' => 'https://example.com:8080/path?param=value#fragment',
        'https://example.com/path/to/resource' => 'https://example.com/path/to/resource',
        'https://example.com/path/to/resource?param1=value1&param2=value2' => 'https://example.com/path/to/resource?param1=value1&param2=value2',
        'https://example.com/path/to/resource#fragment' => 'https://example.com/path/to/resource#fragment',
        'https://example.com/path/to/resource?param1=value1&param2=value2#fragment' => 'https://example.com/path/to/resource?param1=value1&param2=value2#fragment'
    ];
    
    $sanitizedCorrectly = 0;
    $totalInputs = count($testUrlInputs);
    
    echo "   📋 Testovanie {$totalInputs} URL sanitizácií:\n";
    
    foreach ($testUrlInputs as $input => $expectedOutput) {
        $sanitized = sanitizeUrl($input);
        
        if ($sanitized === $expectedOutput) {
            $sanitizedCorrectly++;
            echo "   ✅ '{$input}' → '{$sanitized}'\n";
        } else {
            echo "   ❌ '{$input}' → '{$sanitized}' (Expected: '{$expectedOutput}')\n";
        }
    }
    
    $sanitizationRate = round(($sanitizedCorrectly / $totalInputs) * 100, 1);
    echo "   📊 Sanitization rate: {$sanitizedCorrectly}/{$totalInputs} ({$sanitizationRate}%)\n";
    
    if ($sanitizationRate >= 90) {
        echo "   ✅ Vysoká sanitizácia URL adries\n";
        $passedTests++;
        $testResults[] = ['test' => 'URL Sanitization', 'status' => 'PASS', 'value' => $sanitizationRate];
    } elseif ($sanitizationRate >= 70) {
        echo "   ⚠️  Priemerná sanitizácia URL adries\n";
        $testResults[] = ['test' => 'URL Sanitization', 'status' => 'WARNING', 'value' => $sanitizationRate];
    } else {
        echo "   ❌ Slabá sanitizácia URL adries\n";
        $testResults[] = ['test' => 'URL Sanitization', 'status' => 'FAIL', 'value' => $sanitizationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'URL Sanitization', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Sanitizácia JSON dát
echo "\n🔒 Test 6: Sanitizácia JSON dát\n";
echo "------------------------------\n";

$totalTests++;
try {
    $testJsonInputs = [
        '{"name": "John", "age": 30}' => '{"name": "John", "age": 30}',
        '{"name": "John", "age": 30, "city": "New York"}' => '{"name": "John", "age": 30, "city": "New York"}',
        '{"name": "John", "age": 30, "city": "New York", "country": "USA"}' => '{"name": "John", "age": 30, "city": "New York", "country": "USA"}',
        '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001"}' => '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001"}',
        '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567"}' => '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567"}',
        '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com"}' => '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com"}',
        '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com", "website": "https://example.com"}' => '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com", "website": "https://example.com"}',
        '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com", "website": "https://example.com", "notes": "This is a test note"}' => '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com", "website": "https://example.com", "notes": "This is a test note"}',
        '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com", "website": "https://example.com", "notes": "This is a test note", "tags": ["tag1", "tag2", "tag3"]}' => '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com", "website": "https://example.com", "notes": "This is a test note", "tags": ["tag1", "tag2", "tag3"]}',
        '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com", "website": "https://example.com", "notes": "This is a test note", "tags": ["tag1", "tag2", "tag3"], "metadata": {"created": "2024-01-01", "updated": "2024-01-02"}}' => '{"name": "John", "age": 30, "city": "New York", "country": "USA", "zip": "10001", "phone": "+1-555-123-4567", "email": "john@example.com", "website": "https://example.com", "notes": "This is a test note", "tags": ["tag1", "tag2", "tag3"], "metadata": {"created": "2024-01-01", "updated": "2024-01-02"}}'
    ];
    
    $sanitizedCorrectly = 0;
    $totalInputs = count($testJsonInputs);
    
    echo "   📋 Testovanie {$totalInputs} JSON sanitizácií:\n";
    
    foreach ($testJsonInputs as $input => $expectedOutput) {
        $sanitized = sanitizeJson($input);
        
        if ($sanitized === $expectedOutput) {
            $sanitizedCorrectly++;
            echo "   ✅ JSON sanitized correctly\n";
        } else {
            echo "   ❌ JSON sanitization failed\n";
        }
    }
    
    $sanitizationRate = round(($sanitizedCorrectly / $totalInputs) * 100, 1);
    echo "   📊 Sanitization rate: {$sanitizedCorrectly}/{$totalInputs} ({$sanitizationRate}%)\n";
    
    if ($sanitizationRate >= 90) {
        echo "   ✅ Vysoká sanitizácia JSON dát\n";
        $passedTests++;
        $testResults[] = ['test' => 'JSON Sanitization', 'status' => 'PASS', 'value' => $sanitizationRate];
    } elseif ($sanitizationRate >= 70) {
        echo "   ⚠️  Priemerná sanitizácia JSON dát\n";
        $testResults[] = ['test' => 'JSON Sanitization', 'status' => 'WARNING', 'value' => $sanitizationRate];
    } else {
        echo "   ❌ Slabá sanitizácia JSON dát\n";
        $testResults[] = ['test' => 'JSON Sanitization', 'status' => 'FAIL', 'value' => $sanitizationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'JSON Sanitization', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Sanitization functions
function sanitizeHtml($input) {
    if (!is_string($input)) return '';
    return strip_tags($input, '<p><br><strong><em><u><ol><ul><li><a><img><table><tr><td><th><thead><tbody><tfoot>');
}

function sanitizeSql($input) {
    if (!is_string($input)) return '';
    return str_replace("'", "''", $input);
}

function sanitizeFilePath($input) {
    if (!is_string($input)) return '';
    $input = str_replace('\\', '/', $input);
    $input = preg_replace('/\.\.\//', '', $input);
    $input = preg_replace('/\.\.\\\\/', '', $input);
    $input = ltrim($input, '/');
    return $input;
}

function sanitizeEmail($input) {
    if (!is_string($input)) return '';
    return filter_var($input, FILTER_SANITIZE_EMAIL);
}

function sanitizeUrl($input) {
    if (!is_string($input)) return '';
    return filter_var($input, FILTER_SANITIZE_URL);
}

function sanitizeJson($input) {
    if (!is_string($input)) return '';
    $decoded = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) return '';
    return json_encode($decoded);
}

// Výsledky
echo "\n📊 VÝSLEDKY TESTOVANIA\n";
echo "======================\n";
echo "🎯 Celkovo testov: $totalTests\n";
echo "✅ Úspešné: $passedTests\n";
echo "❌ Zlyhalo: " . ($totalTests - $passedTests) . "\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
echo "📈 Úspešnosť: $successRate%\n";

echo "\n📋 Detailné výsledky:\n";
foreach ($testResults as $result) {
    $statusIcon = $result['status'] === 'PASS' ? '✅' : ($result['status'] === 'WARNING' ? '⚠️' : '❌');
    echo "   $statusIcon {$result['test']}: {$result['value']}%\n";
}

echo "\n";
if ($successRate >= 90) {
    echo "🏆 VÝBORNE! Data sanitization je výborná!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina data sanitization testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica data sanitization testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho data sanitization testov zlyhalo.\n";
}

echo "\n🎉 Test data sanitization dokončený!\n";
?>
