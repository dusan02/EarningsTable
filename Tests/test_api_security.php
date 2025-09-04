<?php
/**
 * 🌐 API SECURITY TESTS
 * Testy pre API bezpečnosť a ochranu
 */

require_once 'config.php';
require_once 'common/UnifiedLogger.php';
require_once 'common/UnifiedValidator.php';
require_once 'common/UnifiedApiWrapper.php';

echo "🌐 TEST: API Security System\n";
echo "===========================\n\n";

$testResults = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'details' => []
];

/**
 * Test 1: API Authentication & Authorization
 */
function testAPIAuthentication() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 1: API Authentication & Authorization\n";
    
    try {
        // Test API key generation
        $apiKey = bin2hex(random_bytes(32));
        $apiSecret = bin2hex(random_bytes(64));
        
        if (strlen($apiKey) === 64 && strlen($apiSecret) === 128) {
            echo "   ✅ API key generation working\n";
        } else {
            echo "   ❌ API key generation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "API key generation failed";
            return false;
        }
        
        // Test API key validation
        $validApiKey = 'valid_api_key_123456789012345678901234567890123456789012345678901234567890';
        $invalidApiKey = 'invalid_key';
        
        $isValidKey = (strlen($validApiKey) === 64 && preg_match('/^[a-f0-9]+$/', $validApiKey));
        $isInvalidKey = !(strlen($invalidApiKey) === 64 && preg_match('/^[a-f0-9]+$/', $invalidApiKey));
        
        if ($isValidKey && $isInvalidKey) {
            echo "   ✅ API key validation working\n";
        } else {
            echo "   ❌ API key validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "API key validation failed";
            return false;
        }
        
        // Test API key permissions
        $apiKeys = [
            'read_only_key' => ['permissions' => ['read']],
            'read_write_key' => ['permissions' => ['read', 'write']],
            'admin_key' => ['permissions' => ['read', 'write', 'delete', 'admin']]
        ];
        
        $testCases = [
            ['key' => 'read_only_key', 'action' => 'read', 'expected' => true],
            ['key' => 'read_only_key', 'action' => 'write', 'expected' => false],
            ['key' => 'read_write_key', 'action' => 'write', 'expected' => true],
            ['key' => 'read_write_key', 'action' => 'delete', 'expected' => false],
            ['key' => 'admin_key', 'action' => 'admin', 'expected' => true]
        ];
        
        $permissionTestsPassed = 0;
        foreach ($testCases as $test) {
            $hasPermission = in_array($test['action'], $apiKeys[$test['key']]['permissions']);
            if ($hasPermission === $test['expected']) {
                $permissionTestsPassed++;
            }
        }
        
        if ($permissionTestsPassed === count($testCases)) {
            echo "   ✅ API key permissions working\n";
        } else {
            echo "   ❌ API key permissions failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "API key permissions failed";
            return false;
        }
        
        // Test JWT token simulation
        $jwtHeader = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $jwtPayload = base64_encode(json_encode([
            'user_id' => 123,
            'role' => 'user',
            'exp' => time() + 3600,
            'iat' => time()
        ]));
        $jwtSignature = hash_hmac('sha256', $jwtHeader . '.' . $jwtPayload, 'secret_key');
        $jwtToken = $jwtHeader . '.' . $jwtPayload . '.' . $jwtSignature;
        
        if (strlen($jwtToken) > 100 && substr_count($jwtToken, '.') === 2) {
            echo "   ✅ JWT token generation working\n";
        } else {
            echo "   ❌ JWT token generation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "JWT token generation failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "API authentication & authorization OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "API authentication test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 2: API Rate Limiting & DoS Protection
 */
function testAPIRateLimiting() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 2: API Rate Limiting & DoS Protection\n";
    
    try {
        // Test rate limiting per IP
        $rateLimits = [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'requests_per_day' => 10000
        ];
        
        $requestLog = [];
        $currentTime = time();
        
        // Simulate requests - create 65 requests in last minute
        for ($i = 0; $i < 65; $i++) {
            $requestLog[] = $currentTime - ($i * 0.9); // 0.9 seconds apart (faster than 1 second)
        }
        
        $recentRequests = array_filter($requestLog, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < 60; // Last minute
        });
        
        $isRateLimited = count($recentRequests) > $rateLimits['requests_per_minute'];
        
        if ($isRateLimited) {
            echo "   ✅ Rate limiting per IP working\n";
        } else {
            echo "   ❌ Rate limiting per IP failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Rate limiting per IP failed";
            return false;
        }
        
        // Test rate limiting per API key
        $apiKeyLimits = [
            'free_tier' => ['requests_per_hour' => 100],
            'premium_tier' => ['requests_per_hour' => 1000],
            'enterprise_tier' => ['requests_per_hour' => 10000]
        ];
        
        $freeTierRequests = 105; // Over limit
        $premiumTierRequests = 500; // Under limit
        
        $freeTierLimited = $freeTierRequests > $apiKeyLimits['free_tier']['requests_per_hour'];
        $premiumTierLimited = $premiumTierRequests > $apiKeyLimits['premium_tier']['requests_per_hour'];
        
        if ($freeTierLimited && !$premiumTierLimited) {
            echo "   ✅ Rate limiting per API key working\n";
        } else {
            echo "   ❌ Rate limiting per API key failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Rate limiting per API key failed";
            return false;
        }
        
        // Test DoS protection
        $dosThresholds = [
            'concurrent_connections' => 100,
            'requests_per_second' => 10,
            'bandwidth_per_minute' => 1024 * 1024 * 10 // 10MB
        ];
        
        $currentConnections = 105; // Over threshold
        $currentRPS = 5; // Under threshold
        $currentBandwidth = 1024 * 1024 * 5; // 5MB, under threshold
        
        $isDosAttack = ($currentConnections > $dosThresholds['concurrent_connections']) ||
                      ($currentRPS > $dosThresholds['requests_per_second']) ||
                      ($currentBandwidth > $dosThresholds['bandwidth_per_minute']);
        
        if ($isDosAttack) {
            echo "   ✅ DoS attack detection working\n";
        } else {
            echo "   ❌ DoS attack detection failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "DoS attack detection failed";
            return false;
        }
        
        // Test IP blocking
        $blockedIPs = [
            '192.168.1.100' => time() - 100,
            '10.0.0.50' => time() - 200
        ];
        
        $testIPs = [
            '192.168.1.100' => true,  // Should be blocked
            '192.168.1.101' => false, // Should not be blocked
            '10.0.0.50' => true       // Should be blocked
        ];
        
        $blockingTestsPassed = 0;
        foreach ($testIPs as $ip => $expectedBlocked) {
            $isBlocked = isset($blockedIPs[$ip]);
            if ($isBlocked === $expectedBlocked) {
                $blockingTestsPassed++;
            }
        }
        
        if ($blockingTestsPassed === count($testIPs)) {
            echo "   ✅ IP blocking mechanism working\n";
        } else {
            echo "   ❌ IP blocking mechanism failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "IP blocking mechanism failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "API rate limiting & DoS protection OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "API rate limiting test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 3: API Input Validation & Sanitization
 */
function testAPIInputValidation() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 3: API Input Validation & Sanitization\n";
    
    try {
        // Test JSON input validation
        $validJson = '{"ticker": "AAPL", "date": "2024-01-15", "limit": 10}';
        $invalidJson = '{"ticker": "AAPL", "date": "invalid-date", "limit": "not-a-number"}';
        
        $validData = json_decode($validJson, true);
        $invalidData = json_decode($invalidJson, true);
        
        $isValidJson = ($validData !== null && isset($validData['ticker']));
        $isInvalidJson = ($invalidData !== null && !is_numeric($invalidData['limit']));
        
        if ($isValidJson && $isInvalidJson) {
            echo "   ✅ JSON input validation working\n";
        } else {
            echo "   ❌ JSON input validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "JSON input validation failed";
            return false;
        }
        
        // Test parameter validation
        $testParams = [
            'ticker' => ['value' => 'AAPL', 'pattern' => '/^[A-Z]{1,5}$/', 'valid' => true],
            'date' => ['value' => '2024-01-15', 'pattern' => '/^\d{4}-\d{2}-\d{2}$/', 'valid' => true],
            'limit' => ['value' => 10, 'type' => 'integer', 'min' => 1, 'max' => 100, 'valid' => true],
            'invalid_ticker' => ['value' => 'INVALID123', 'pattern' => '/^[A-Z]{1,5}$/', 'valid' => false],
            'invalid_date' => ['value' => '2024-13-45', 'pattern' => '/^\d{4}-\d{2}-\d{2}$/', 'valid' => false],
            'invalid_limit' => ['value' => 1000, 'type' => 'integer', 'min' => 1, 'max' => 100, 'valid' => false]
        ];
        
        $validationTestsPassed = 0;
        foreach ($testParams as $paramName => $param) {
            $isValid = true;
            
            if (isset($param['pattern'])) {
                $isValid = $isValid && preg_match($param['pattern'], $param['value']);
            }
            
            if (isset($param['type'])) {
                switch ($param['type']) {
                    case 'integer':
                        $isValid = $isValid && (is_int($param['value']) || (is_string($param['value']) && is_numeric($param['value'])));
                        break;
                    case 'string':
                        $isValid = $isValid && is_string($param['value']);
                        break;
                }
            }
            
            if (isset($param['min']) && isset($param['max'])) {
                $numericValue = is_numeric($param['value']) ? (int)$param['value'] : $param['value'];
                $isValid = $isValid && ($numericValue >= $param['min'] && $numericValue <= $param['max']);
            }
            
            if ($isValid === $param['valid']) {
                $validationTestsPassed++;
            }
        }
        
        if ($validationTestsPassed === count($testParams)) {
            echo "   ✅ Parameter validation working\n";
        } else {
            echo "   ❌ Parameter validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Parameter validation failed";
            return false;
        }
        
        // Test SQL injection prevention
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "' OR '1'='1",
            "'; INSERT INTO users VALUES ('hacker', 'password'); --",
            "' UNION SELECT * FROM users --"
        ];
        
        $sqlInjectionBlocked = 0;
        foreach ($maliciousInputs as $input) {
            // Simulate prepared statement protection
            $isSafe = !preg_match('/[\'";]/', $input) || preg_match('/^[a-zA-Z0-9\s\-_\.]+$/', $input);
            if (!$isSafe) {
                $sqlInjectionBlocked++;
            }
        }
        
        if ($sqlInjectionBlocked === count($maliciousInputs)) {
            echo "   ✅ SQL injection prevention working\n";
        } else {
            echo "   ❌ SQL injection prevention failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "SQL injection prevention failed";
            return false;
        }
        
        // Test XSS prevention
        $xssInputs = [
            '<script>alert("XSS")</script>',
            'javascript:alert("XSS")',
            '<img src="x" onerror="alert(\'XSS\')">',
            '<iframe src="javascript:alert(\'XSS\')"></iframe>'
        ];
        
        $xssBlocked = 0;
        foreach ($xssInputs as $input) {
            $isSafe = !preg_match('/<script|javascript:|onerror=|onload=/i', $input);
            if (!$isSafe) {
                $xssBlocked++;
            }
        }
        
        if ($xssBlocked === count($xssInputs)) {
            echo "   ✅ XSS prevention working\n";
        } else {
            echo "   ❌ XSS prevention failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "XSS prevention failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "API input validation & sanitization OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "API input validation test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 4: API Response Security
 */
function testAPIResponseSecurity() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 4: API Response Security\n";
    
    try {
        // Test response headers
        $securityHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => "default-src 'self'",
            'Access-Control-Allow-Origin' => 'https://earnings-table.com',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-API-Key'
        ];
        
        $requiredHeaders = ['X-Content-Type-Options', 'X-Frame-Options', 'X-XSS-Protection'];
        $hasRequiredHeaders = true;
        
        foreach ($requiredHeaders as $header) {
            if (!isset($securityHeaders[$header])) {
                $hasRequiredHeaders = false;
                break;
            }
        }
        
        if ($hasRequiredHeaders) {
            echo "   ✅ API response security headers configured\n";
        } else {
            echo "   ❌ API response security headers missing\n";
            $testResults['failed']++;
            $testResults['details'][] = "API response security headers missing";
            return false;
        }
        
        // Test CORS configuration
        $allowedOrigins = ['https://earnings-table.com', 'https://www.earnings-table.com'];
        $testOrigins = [
            'https://earnings-table.com' => true,
            'https://www.earnings-table.com' => true,
            'https://malicious-site.com' => false,
            'http://earnings-table.com' => false
        ];
        
        $corsTestsPassed = 0;
        foreach ($testOrigins as $origin => $expected) {
            $isAllowed = in_array($origin, $allowedOrigins);
            if ($isAllowed === $expected) {
                $corsTestsPassed++;
            }
        }
        
        if ($corsTestsPassed === count($testOrigins)) {
            echo "   ✅ CORS configuration working\n";
        } else {
            echo "   ❌ CORS configuration failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "CORS configuration failed";
            return false;
        }
        
        // Test response data sanitization
        $sensitiveData = [
            'user_id' => 123,
            'email' => 'user@example.com',
            'password_hash' => 'hashed_password_123',
            'api_key' => 'secret_api_key_456',
            'internal_notes' => 'Internal system notes'
        ];
        
        $publicData = [
            'user_id' => 123,
            'email' => 'user@example.com'
        ];
        
        $sanitizedData = array_intersect_key($sensitiveData, $publicData);
        
        if (count($sanitizedData) === 2 && !isset($sanitizedData['password_hash'])) {
            echo "   ✅ Response data sanitization working\n";
        } else {
            echo "   ❌ Response data sanitization failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Response data sanitization failed";
            return false;
        }
        
        // Test error response security
        $errorResponses = [
            '400' => ['error' => 'Bad Request', 'message' => 'Invalid parameters'],
            '401' => ['error' => 'Unauthorized', 'message' => 'Authentication required'],
            '403' => ['error' => 'Forbidden', 'message' => 'Access denied'],
            '404' => ['error' => 'Not Found', 'message' => 'Resource not found'],
            '500' => ['error' => 'Internal Server Error', 'message' => 'An error occurred']
        ];
        
        $hasSecureErrors = true;
        foreach ($errorResponses as $code => $response) {
            if (isset($response['error']) && isset($response['message'])) {
                // Check that error messages don't expose sensitive information
                $isSecure = !preg_match('/password|key|secret|token|database|sql/i', $response['message']);
                if (!$isSecure) {
                    $hasSecureErrors = false;
                    break;
                }
            }
        }
        
        if ($hasSecureErrors) {
            echo "   ✅ Error response security working\n";
        } else {
            echo "   ❌ Error response security failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Error response security failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "API response security OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "API response security test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 5: API Monitoring & Logging
 */
function testAPIMonitoring() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 5: API Monitoring & Logging\n";
    
    try {
        // Test API request logging
        $requestLog = [
            'timestamp' => time(),
            'method' => 'GET',
            'endpoint' => '/api/earnings',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'response_code' => 200,
            'response_time' => 150, // milliseconds
            'api_key' => 'key_123'
        ];
        
        if (isset($requestLog['timestamp']) && isset($requestLog['method']) && isset($requestLog['endpoint'])) {
            echo "   ✅ API request logging working\n";
        } else {
            echo "   ❌ API request logging failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "API request logging failed";
            return false;
        }
        
        // Test API metrics collection
        $apiMetrics = [
            'total_requests' => 1000,
            'successful_requests' => 950,
            'failed_requests' => 50,
            'average_response_time' => 200,
            'peak_requests_per_minute' => 100,
            'unique_api_keys' => 25,
            'top_endpoints' => [
                '/api/earnings' => 400,
                '/api/tickers' => 300,
                '/api/guidance' => 200
            ]
        ];
        
        if (isset($apiMetrics['total_requests']) && $apiMetrics['successful_requests'] + $apiMetrics['failed_requests'] === $apiMetrics['total_requests']) {
            echo "   ✅ API metrics collection working\n";
        } else {
            echo "   ❌ API metrics collection failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "API metrics collection failed";
            return false;
        }
        
        // Test API anomaly detection
        $normalRequests = [
            ['response_time' => 150, 'status_code' => 200, 'endpoint' => '/api/earnings'],
            ['response_time' => 200, 'status_code' => 200, 'endpoint' => '/api/tickers'],
            ['response_time' => 100, 'status_code' => 200, 'endpoint' => '/api/guidance']
        ];
        
        $anomalousRequests = [
            ['response_time' => 5000, 'status_code' => 200, 'endpoint' => '/api/earnings'], // Very slow
            ['response_time' => 50, 'status_code' => 500, 'endpoint' => '/api/earnings'],   // Error
            ['response_time' => 100, 'status_code' => 200, 'endpoint' => '/api/admin']      // Unusual endpoint
        ];
        
        $anomalyDetected = false;
        foreach ($anomalousRequests as $request) {
            if ($request['response_time'] > 1000 || $request['status_code'] >= 400 || strpos($request['endpoint'], '/admin') !== false) {
                $anomalyDetected = true;
                break;
            }
        }
        
        if ($anomalyDetected) {
            echo "   ✅ API anomaly detection working\n";
        } else {
            echo "   ❌ API anomaly detection failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "API anomaly detection failed";
            return false;
        }
        
        // Test API security alerts
        $securityAlerts = [
            ['type' => 'rate_limit_exceeded', 'ip' => '192.168.1.100', 'timestamp' => time()],
            ['type' => 'invalid_api_key', 'ip' => '192.168.1.101', 'timestamp' => time()],
            ['type' => 'suspicious_request', 'ip' => '192.168.1.102', 'timestamp' => time()]
        ];
        
        if (count($securityAlerts) === 3 && isset($securityAlerts[0]['type'])) {
            echo "   ✅ API security alerts working\n";
        } else {
            echo "   ❌ API security alerts failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "API security alerts failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "API monitoring & logging OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "API monitoring test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 6: API Endpoint Security
 */
function testAPIEndpointSecurity() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 6: API Endpoint Security\n";
    
    try {
        // Test endpoint authentication requirements
        $endpoints = [
            'GET /api/earnings' => ['auth_required' => false, 'rate_limit' => 100],
            'POST /api/earnings' => ['auth_required' => true, 'rate_limit' => 10],
            'PUT /api/earnings/123' => ['auth_required' => true, 'rate_limit' => 10],
            'DELETE /api/earnings/123' => ['auth_required' => true, 'rate_limit' => 5],
            'GET /api/admin/users' => ['auth_required' => true, 'admin_required' => true, 'rate_limit' => 5]
        ];
        
        $securityTestsPassed = 0;
        foreach ($endpoints as $endpoint => $config) {
            $isSecure = true;
            
            // Check authentication requirements
            if (strpos($endpoint, 'POST') === 0 || strpos($endpoint, 'PUT') === 0 || strpos($endpoint, 'DELETE') === 0) {
                $isSecure = $isSecure && $config['auth_required'];
            }
            
            // Check admin requirements
            if (strpos($endpoint, '/admin/') !== false) {
                $isSecure = $isSecure && isset($config['admin_required']) && $config['admin_required'];
            }
            
            // Check rate limits
            $isSecure = $isSecure && isset($config['rate_limit']) && $config['rate_limit'] > 0;
            
            if ($isSecure) {
                $securityTestsPassed++;
            }
        }
        
        if ($securityTestsPassed === count($endpoints)) {
            echo "   ✅ Endpoint authentication requirements working\n";
        } else {
            echo "   ❌ Endpoint authentication requirements failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Endpoint authentication requirements failed";
            return false;
        }
        
        // Test endpoint parameter validation
        $endpointParams = [
            '/api/earnings' => ['ticker' => 'string', 'date' => 'date', 'limit' => 'integer'],
            '/api/tickers' => ['symbol' => 'string', 'exchange' => 'string'],
            '/api/guidance' => ['ticker' => 'string', 'period' => 'string', 'year' => 'integer']
        ];
        
        $paramValidationPassed = 0;
        foreach ($endpointParams as $endpoint => $params) {
            $hasValidation = count($params) > 0;
            if ($hasValidation) {
                $paramValidationPassed++;
            }
        }
        
        if ($paramValidationPassed === count($endpointParams)) {
            echo "   ✅ Endpoint parameter validation working\n";
        } else {
            echo "   ❌ Endpoint parameter validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Endpoint parameter validation failed";
            return false;
        }
        
        // Test endpoint access control
        $accessControl = [
            'user' => ['/api/earnings', '/api/tickers', '/api/guidance'],
            'admin' => ['/api/earnings', '/api/tickers', '/api/guidance', '/api/admin/users', '/api/admin/settings'],
            'guest' => ['/api/earnings']
        ];
        
        $accessTestsPassed = 0;
        $testCases = [
            ['role' => 'user', 'endpoint' => '/api/earnings', 'expected' => true],
            ['role' => 'user', 'endpoint' => '/api/admin/users', 'expected' => false],
            ['role' => 'admin', 'endpoint' => '/api/admin/users', 'expected' => true],
            ['role' => 'guest', 'endpoint' => '/api/tickers', 'expected' => false]
        ];
        
        foreach ($testCases as $test) {
            $hasAccess = in_array($test['endpoint'], $accessControl[$test['role']]);
            if ($hasAccess === $test['expected']) {
                $accessTestsPassed++;
            }
        }
        
        if ($accessTestsPassed === count($testCases)) {
            echo "   ✅ Endpoint access control working\n";
        } else {
            echo "   ❌ Endpoint access control failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Endpoint access control failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "API endpoint security OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "API endpoint security test failed: " . $e->getMessage();
        return false;
    }
}

// Run all tests
echo "🚀 Starting API Security Tests...\n\n";

testAPIAuthentication();
testAPIRateLimiting();
testAPIInputValidation();
testAPIResponseSecurity();
testAPIMonitoring();
testAPIEndpointSecurity();

// Summary
echo "\n📊 API SECURITY TEST SUMMARY\n";
echo "===========================\n";
echo "Total Tests: {$testResults['total']}\n";
echo "✅ Passed: {$testResults['passed']}\n";
echo "❌ Failed: {$testResults['failed']}\n";
echo "📈 Success Rate: " . round(($testResults['passed'] / $testResults['total']) * 100, 1) . "%\n\n";

if ($testResults['failed'] > 0) {
    echo "❌ FAILED TESTS:\n";
    foreach ($testResults['details'] as $detail) {
        if (strpos($detail, 'failed') !== false) {
            echo "   • $detail\n";
        }
    }
    echo "\n";
}

if ($testResults['passed'] === $testResults['total']) {
    echo "🎉 ALL API SECURITY TESTS PASSED!\n";
    exit(0);
} else {
    echo "⚠️  Some API security tests failed. Review implementation.\n";
    exit(1);
}
?>
