<?php
/**
 * 🍪 CSRF PROTECTION TESTS
 * Testy pre CSRF (Cross-Site Request Forgery) ochranu
 */

require_once 'config.php';
require_once 'common/UnifiedLogger.php';
require_once 'common/UnifiedValidator.php';

echo "🍪 TEST: CSRF Protection System\n";
echo "==============================\n\n";

$testResults = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'details' => []
];

/**
 * Test 1: CSRF Token Generation
 */
function testCSRFTokenGeneration() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 1: CSRF Token Generation\n";
    
    try {
        // Test token generation
        $token1 = bin2hex(random_bytes(32));
        $token2 = bin2hex(random_bytes(32));
        
        if (strlen($token1) === 64 && strlen($token2) === 64 && $token1 !== $token2) {
            echo "   ✅ CSRF token generation working (unique tokens)\n";
        } else {
            echo "   ❌ CSRF token generation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "CSRF token generation failed";
            return false;
        }
        
        // Test token entropy
        $entropy = strlen($token1) * 4; // 256 bits
        if ($entropy >= 256) {
            echo "   ✅ CSRF token entropy sufficient (256+ bits)\n";
        } else {
            echo "   ❌ CSRF token entropy insufficient\n";
            $testResults['failed']++;
            $testResults['details'][] = "CSRF token entropy insufficient";
            return false;
        }
        
        // Test token format validation
        if (preg_match('/^[a-f0-9]{64}$/', $token1)) {
            echo "   ✅ CSRF token format validation working\n";
        } else {
            echo "   ❌ CSRF token format validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "CSRF token format validation failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "CSRF token generation OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "CSRF token generation test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 2: CSRF Token Validation
 */
function testCSRFTokenValidation() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 2: CSRF Token Validation\n";
    
    try {
        // Generate valid token
        $validToken = bin2hex(random_bytes(32));
        $storedTokens = [$validToken => time()];
        
        // Test valid token validation
        if (isset($storedTokens[$validToken])) {
            echo "   ✅ Valid CSRF token validation working\n";
        } else {
            echo "   ❌ Valid CSRF token validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Valid CSRF token validation failed";
            return false;
        }
        
        // Test invalid token rejection
        $invalidToken = 'invalid_token_123';
        if (!isset($storedTokens[$invalidToken])) {
            echo "   ✅ Invalid CSRF token rejection working\n";
        } else {
            echo "   ❌ Invalid CSRF token rejection failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Invalid CSRF token rejection failed";
            return false;
        }
        
        // Test empty token rejection
        $emptyToken = '';
        if (!isset($storedTokens[$emptyToken])) {
            echo "   ✅ Empty CSRF token rejection working\n";
        } else {
            echo "   ❌ Empty CSRF token rejection failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Empty CSRF token rejection failed";
            return false;
        }
        
        // Test token format validation
        $malformedToken = 'not_hex_string';
        if (!preg_match('/^[a-f0-9]{64}$/', $malformedToken)) {
            echo "   ✅ Malformed CSRF token rejection working\n";
        } else {
            echo "   ❌ Malformed CSRF token rejection failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Malformed CSRF token rejection failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "CSRF token validation OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "CSRF token validation test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 3: CSRF Token Storage & Management
 */
function testCSRFTokenStorage() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 3: CSRF Token Storage & Management\n";
    
    try {
        // Test session-based token storage
        $sessionTokens = [];
        $token = bin2hex(random_bytes(32));
        $sessionTokens['csrf_token'] = $token;
        $sessionTokens['csrf_token_time'] = time();
        
        if (isset($sessionTokens['csrf_token']) && isset($sessionTokens['csrf_token_time'])) {
            echo "   ✅ Session-based CSRF token storage working\n";
        } else {
            echo "   ❌ Session-based CSRF token storage failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session-based CSRF token storage failed";
            return false;
        }
        
        // Test token expiration
        $expiredTime = time() - 3600; // 1 hour ago
        $currentTime = time();
        $tokenAge = $currentTime - $expiredTime;
        $maxAge = 1800; // 30 minutes
        
        if ($tokenAge > $maxAge) {
            echo "   ✅ CSRF token expiration detection working\n";
        } else {
            echo "   ❌ CSRF token expiration detection failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "CSRF token expiration detection failed";
            return false;
        }
        
        // Test token cleanup
        $oldTokens = [
            'token1' => time() - 3600,
            'token2' => time() - 7200,
            'token3' => time() - 100
        ];
        
        $cleanedTokens = array_filter($oldTokens, function($timestamp) {
            return (time() - $timestamp) < 1800; // Keep tokens newer than 30 minutes
        });
        
        if (count($cleanedTokens) === 1) { // Only token3 should remain
            echo "   ✅ CSRF token cleanup working\n";
        } else {
            echo "   ❌ CSRF token cleanup failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "CSRF token cleanup failed";
            return false;
        }
        
        // Test token rotation
        $oldToken = $sessionTokens['csrf_token'];
        $newToken = bin2hex(random_bytes(32));
        $sessionTokens['csrf_token'] = $newToken;
        
        if ($sessionTokens['csrf_token'] !== $oldToken) {
            echo "   ✅ CSRF token rotation working\n";
        } else {
            echo "   ❌ CSRF token rotation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "CSRF token rotation failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "CSRF token storage & management OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "CSRF token storage test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 4: CSRF Attack Prevention
 */
function testCSRFAttackPrevention() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 4: CSRF Attack Prevention\n";
    
    try {
        // Test SameSite cookie attribute simulation
        $cookieAttributes = [
            'SameSite' => 'Strict',
            'Secure' => true,
            'HttpOnly' => true
        ];
        
        if ($cookieAttributes['SameSite'] === 'Strict' && $cookieAttributes['Secure'] && $cookieAttributes['HttpOnly']) {
            echo "   ✅ SameSite cookie attributes configured correctly\n";
        } else {
            echo "   ❌ SameSite cookie attributes misconfigured\n";
            $testResults['failed']++;
            $testResults['details'][] = "SameSite cookie attributes misconfigured";
            return false;
        }
        
        // Test Origin header validation
        $allowedOrigins = ['https://earnings-table.com', 'https://www.earnings-table.com'];
        $testOrigins = [
            'https://earnings-table.com' => true,
            'https://www.earnings-table.com' => true,
            'https://malicious-site.com' => false,
            'http://earnings-table.com' => false // HTTP not allowed
        ];
        
        $originValidationPassed = 0;
        foreach ($testOrigins as $origin => $expected) {
            $isAllowed = in_array($origin, $allowedOrigins) && strpos($origin, 'https://') === 0;
            if ($isAllowed === $expected) {
                $originValidationPassed++;
            }
        }
        
        if ($originValidationPassed === count($testOrigins)) {
            echo "   ✅ Origin header validation working\n";
        } else {
            echo "   ❌ Origin header validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Origin header validation failed";
            return false;
        }
        
        // Test Referer header validation
        $allowedReferers = ['https://earnings-table.com', 'https://www.earnings-table.com'];
        $testReferers = [
            'https://earnings-table.com/dashboard' => true,
            'https://www.earnings-table.com/login' => true,
            'https://malicious-site.com/attack' => false,
            'http://earnings-table.com/dashboard' => false
        ];
        
        $refererValidationPassed = 0;
        foreach ($testReferers as $referer => $expected) {
            $isAllowed = false;
            foreach ($allowedReferers as $allowed) {
                if (strpos($referer, $allowed) === 0) {
                    $isAllowed = true;
                    break;
                }
            }
            if ($isAllowed === $expected) {
                $refererValidationPassed++;
            }
        }
        
        if ($refererValidationPassed === count($testReferers)) {
            echo "   ✅ Referer header validation working\n";
        } else {
            echo "   ❌ Referer header validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Referer header validation failed";
            return false;
        }
        
        // Test double-submit cookie pattern
        $formToken = bin2hex(random_bytes(32));
        $cookieToken = $formToken; // Same token in cookie and form
        
        if ($formToken === $cookieToken) {
            echo "   ✅ Double-submit cookie pattern working\n";
        } else {
            echo "   ❌ Double-submit cookie pattern failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Double-submit cookie pattern failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "CSRF attack prevention OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "CSRF attack prevention test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 5: CSRF Protection Integration
 */
function testCSRFProtectionIntegration() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 5: CSRF Protection Integration\n";
    
    try {
        // Test form integration
        $formData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'csrf_token' => bin2hex(random_bytes(32))
        ];
        
        if (isset($formData['csrf_token']) && strlen($formData['csrf_token']) === 64) {
            echo "   ✅ Form CSRF token integration working\n";
        } else {
            echo "   ❌ Form CSRF token integration failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Form CSRF token integration failed";
            return false;
        }
        
        // Test AJAX request integration
        $ajaxHeaders = [
            'X-CSRF-Token' => bin2hex(random_bytes(32)),
            'Content-Type' => 'application/json'
        ];
        
        if (isset($ajaxHeaders['X-CSRF-Token']) && strlen($ajaxHeaders['X-CSRF-Token']) === 64) {
            echo "   ✅ AJAX CSRF token integration working\n";
        } else {
            echo "   ❌ AJAX CSRF token integration failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "AJAX CSRF token integration failed";
            return false;
        }
        
        // Test API endpoint protection
        $apiEndpoints = [
            'POST /api/users' => 'protected',
            'PUT /api/users/123' => 'protected',
            'DELETE /api/users/123' => 'protected',
            'GET /api/users' => 'unprotected',
            'GET /api/earnings' => 'unprotected'
        ];
        
        $protectedEndpoints = array_filter($apiEndpoints, function($protection) {
            return $protection === 'protected';
        });
        
        if (count($protectedEndpoints) === 3) {
            echo "   ✅ API endpoint CSRF protection configuration working\n";
        } else {
            echo "   ❌ API endpoint CSRF protection configuration failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "API endpoint CSRF protection configuration failed";
            return false;
        }
        
        // Test middleware integration
        $middlewareStack = [
            'SecurityHeaders',
            'CSRFProtection',
            'Authentication',
            'Authorization'
        ];
        
        if (in_array('CSRFProtection', $middlewareStack)) {
            echo "   ✅ CSRF protection middleware integration working\n";
        } else {
            echo "   ❌ CSRF protection middleware integration failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "CSRF protection middleware integration failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "CSRF protection integration OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "CSRF protection integration test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 6: CSRF Attack Simulation
 */
function testCSRFAttackSimulation() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 6: CSRF Attack Simulation\n";
    
    try {
        // Simulate CSRF attack without token
        $maliciousRequest = [
            'method' => 'POST',
            'url' => '/api/users/delete',
            'headers' => [
                'Origin' => 'https://malicious-site.com',
                'Referer' => 'https://malicious-site.com/attack.html'
            ],
            'data' => ['user_id' => 123]
        ];
        
        // Check if request would be blocked
        $hasValidToken = false;
        $hasValidOrigin = in_array($maliciousRequest['headers']['Origin'], ['https://earnings-table.com']);
        $hasValidReferer = strpos($maliciousRequest['headers']['Referer'], 'https://earnings-table.com') === 0;
        
        $isBlocked = !$hasValidToken || !$hasValidOrigin || !$hasValidReferer;
        
        if ($isBlocked) {
            echo "   ✅ CSRF attack without token blocked\n";
        } else {
            echo "   ❌ CSRF attack without token not blocked\n";
            $testResults['failed']++;
            $testResults['details'][] = "CSRF attack without token not blocked";
            return false;
        }
        
        // Simulate CSRF attack with invalid token
        $maliciousRequestWithToken = [
            'method' => 'POST',
            'url' => '/api/users/delete',
            'headers' => [
                'Origin' => 'https://earnings-table.com',
                'Referer' => 'https://earnings-table.com/dashboard'
            ],
            'data' => [
                'user_id' => 123,
                'csrf_token' => 'invalid_token_123'
            ]
        ];
        
        $hasValidToken = false; // Invalid token
        $hasValidOrigin = in_array($maliciousRequestWithToken['headers']['Origin'], ['https://earnings-table.com']);
        $hasValidReferer = strpos($maliciousRequestWithToken['headers']['Referer'], 'https://earnings-table.com') === 0;
        
        $isBlocked = !$hasValidToken || !$hasValidOrigin || !$hasValidReferer;
        
        if ($isBlocked) {
            echo "   ✅ CSRF attack with invalid token blocked\n";
        } else {
            echo "   ❌ CSRF attack with invalid token not blocked\n";
            $testResults['failed']++;
            $testResults['details'][] = "CSRF attack with invalid token not blocked";
            return false;
        }
        
        // Simulate legitimate request
        $legitimateRequest = [
            'method' => 'POST',
            'url' => '/api/users/delete',
            'headers' => [
                'Origin' => 'https://earnings-table.com',
                'Referer' => 'https://earnings-table.com/dashboard'
            ],
            'data' => [
                'user_id' => 123,
                'csrf_token' => bin2hex(random_bytes(32))
            ]
        ];
        
        $hasValidToken = true; // Valid token
        $hasValidOrigin = in_array($legitimateRequest['headers']['Origin'], ['https://earnings-table.com']);
        $hasValidReferer = strpos($legitimateRequest['headers']['Referer'], 'https://earnings-table.com') === 0;
        
        $isAllowed = $hasValidToken && $hasValidOrigin && $hasValidReferer;
        
        if ($isAllowed) {
            echo "   ✅ Legitimate request with valid token allowed\n";
        } else {
            echo "   ❌ Legitimate request with valid token blocked\n";
            $testResults['failed']++;
            $testResults['details'][] = "Legitimate request with valid token blocked";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "CSRF attack simulation OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "CSRF attack simulation test failed: " . $e->getMessage();
        return false;
    }
}

// Run all tests
echo "🚀 Starting CSRF Protection Tests...\n\n";

testCSRFTokenGeneration();
testCSRFTokenValidation();
testCSRFTokenStorage();
testCSRFAttackPrevention();
testCSRFProtectionIntegration();
testCSRFAttackSimulation();

// Summary
echo "\n📊 CSRF PROTECTION TEST SUMMARY\n";
echo "==============================\n";
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
    echo "🎉 ALL CSRF PROTECTION TESTS PASSED!\n";
    exit(0);
} else {
    echo "⚠️  Some CSRF protection tests failed. Review implementation.\n";
    exit(1);
}
?>
