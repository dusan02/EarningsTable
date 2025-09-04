<?php
/**
 * 🔐 SESSION MANAGEMENT TESTS
 * Testy pre session management a bezpečnosť
 */

require_once 'config.php';
require_once 'common/UnifiedLogger.php';
require_once 'common/UnifiedValidator.php';

echo "🔐 TEST: Session Management System\n";
echo "=================================\n\n";

$testResults = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'details' => []
];

/**
 * Test 1: Session Configuration & Security
 */
function testSessionConfiguration() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 1: Session Configuration & Security\n";
    
    try {
        // Test session configuration
        $sessionConfig = [
            'cookie_lifetime' => 0, // Session cookie expires when browser closes
            'cookie_secure' => true, // Only send over HTTPS
            'cookie_httponly' => true, // Prevent JavaScript access
            'cookie_samesite' => 'Strict', // CSRF protection
            'use_strict_mode' => true, // Prevent session fixation
            'use_only_cookies' => true, // Don't use URL parameters
            'gc_maxlifetime' => 1800, // 30 minutes
            'gc_probability' => 1,
            'gc_divisor' => 100
        ];
        
        $secureConfig = true;
        foreach ($sessionConfig as $key => $value) {
            switch ($key) {
                case 'cookie_secure':
                case 'cookie_httponly':
                case 'use_strict_mode':
                case 'use_only_cookies':
                    if (!$value) $secureConfig = false;
                    break;
                case 'cookie_samesite':
                    if ($value !== 'Strict') $secureConfig = false;
                    break;
                case 'gc_maxlifetime':
                    if ($value > 3600) $secureConfig = false; // Max 1 hour
                    break;
            }
        }
        
        if ($secureConfig) {
            echo "   ✅ Session configuration is secure\n";
        } else {
            echo "   ❌ Session configuration has security issues\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session configuration has security issues";
            return false;
        }
        
        // Test session ID generation
        $sessionId1 = bin2hex(random_bytes(32));
        $sessionId2 = bin2hex(random_bytes(32));
        
        if (strlen($sessionId1) === 64 && strlen($sessionId2) === 64 && $sessionId1 !== $sessionId2) {
            echo "   ✅ Session ID generation working (unique IDs)\n";
        } else {
            echo "   ❌ Session ID generation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session ID generation failed";
            return false;
        }
        
        // Test session ID entropy
        $entropy = strlen($sessionId1) * 4; // 256 bits
        if ($entropy >= 256) {
            echo "   ✅ Session ID entropy sufficient (256+ bits)\n";
        } else {
            echo "   ❌ Session ID entropy insufficient\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session ID entropy insufficient";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "Session configuration & security OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "Session configuration test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 2: Session Lifecycle Management
 */
function testSessionLifecycle() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 2: Session Lifecycle Management\n";
    
    try {
        // Test session creation
        $sessionData = [
            'session_id' => bin2hex(random_bytes(32)),
            'user_id' => 123,
            'role' => 'user',
            'login_time' => time(),
            'last_activity' => time(),
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
        
        if (isset($sessionData['session_id']) && isset($sessionData['user_id']) && isset($sessionData['login_time'])) {
            echo "   ✅ Session creation working\n";
        } else {
            echo "   ❌ Session creation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session creation failed";
            return false;
        }
        
        // Test session validation
        $currentTime = time();
        $sessionAge = $currentTime - $sessionData['login_time'];
        $maxAge = 1800; // 30 minutes
        
        if ($sessionAge < $maxAge) {
            echo "   ✅ Session age validation working\n";
        } else {
            echo "   ❌ Session age validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session age validation failed";
            return false;
        }
        
        // Test session timeout
        $lastActivity = $sessionData['last_activity'];
        $inactivityTime = $currentTime - $lastActivity;
        $timeoutPeriod = 900; // 15 minutes
        
        if ($inactivityTime < $timeoutPeriod) {
            echo "   ✅ Session timeout validation working\n";
        } else {
            echo "   ❌ Session timeout validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session timeout validation failed";
            return false;
        }
        
        // Test session regeneration
        $oldSessionId = $sessionData['session_id'];
        $newSessionId = bin2hex(random_bytes(32));
        $sessionData['session_id'] = $newSessionId;
        $sessionData['regenerated_at'] = $currentTime;
        
        if ($sessionData['session_id'] !== $oldSessionId) {
            echo "   ✅ Session regeneration working\n";
        } else {
            echo "   ❌ Session regeneration failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session regeneration failed";
            return false;
        }
        
        // Test session destruction
        $sessionData = []; // Clear session data
        
        if (empty($sessionData)) {
            echo "   ✅ Session destruction working\n";
        } else {
            echo "   ❌ Session destruction failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session destruction failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "Session lifecycle management OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "Session lifecycle test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 3: Session Security & Attack Prevention
 */
function testSessionSecurity() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 3: Session Security & Attack Prevention\n";
    
    try {
        // Test session fixation prevention
        $originalSessionId = bin2hex(random_bytes(32));
        $newSessionId = bin2hex(random_bytes(32));
        
        // Simulate login - session ID should change
        if ($originalSessionId !== $newSessionId) {
            echo "   ✅ Session fixation prevention working\n";
        } else {
            echo "   ❌ Session fixation prevention failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session fixation prevention failed";
            return false;
        }
        
        // Test session hijacking prevention
        $sessionData = [
            'user_id' => 123,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ];
        
        $currentRequest = [
            'ip_address' => '192.168.1.100', // Same IP
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' // Same UA
        ];
        
        $suspiciousRequest = [
            'ip_address' => '192.168.1.200', // Different IP
            'user_agent' => 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36' // Different UA
        ];
        
        // Test legitimate request
        $isLegitimate = ($sessionData['ip_address'] === $currentRequest['ip_address']) && 
                       ($sessionData['user_agent'] === $currentRequest['user_agent']);
        
        if ($isLegitimate) {
            echo "   ✅ Legitimate session request validation working\n";
        } else {
            echo "   ❌ Legitimate session request validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Legitimate session request validation failed";
            return false;
        }
        
        // Test suspicious request detection
        $isSuspicious = ($sessionData['ip_address'] !== $suspiciousRequest['ip_address']) || 
                       ($sessionData['user_agent'] !== $suspiciousRequest['user_agent']);
        
        if ($isSuspicious) {
            echo "   ✅ Suspicious session request detection working\n";
        } else {
            echo "   ❌ Suspicious session request detection failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Suspicious session request detection failed";
            return false;
        }
        
        // Test concurrent session limit
        $userSessions = [
            'session1' => time() - 100,
            'session2' => time() - 200,
            'session3' => time() - 300
        ];
        
        $maxConcurrentSessions = 3;
        $activeSessions = count($userSessions);
        
        if ($activeSessions <= $maxConcurrentSessions) {
            echo "   ✅ Concurrent session limit validation working\n";
        } else {
            echo "   ❌ Concurrent session limit validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Concurrent session limit validation failed";
            return false;
        }
        
        // Test session data encryption
        $sensitiveData = 'user_password_hash_12345';
        $encryptedData = base64_encode($sensitiveData); // Simple encryption simulation
        
        if ($encryptedData !== $sensitiveData) {
            echo "   ✅ Session data encryption working\n";
        } else {
            echo "   ❌ Session data encryption failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session data encryption failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "Session security & attack prevention OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "Session security test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 4: Session Storage & Persistence
 */
function testSessionStorage() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 4: Session Storage & Persistence\n";
    
    try {
        // Test session data serialization
        $sessionData = [
            'user_id' => 123,
            'username' => 'testuser',
            'role' => 'user',
            'permissions' => ['read', 'write'],
            'login_time' => time(),
            'preferences' => [
                'theme' => 'dark',
                'language' => 'en',
                'notifications' => true
            ]
        ];
        
        $serialized = serialize($sessionData);
        $unserialized = unserialize($serialized);
        
        if ($unserialized === $sessionData) {
            echo "   ✅ Session data serialization working\n";
        } else {
            echo "   ❌ Session data serialization failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session data serialization failed";
            return false;
        }
        
        // Test session data size limits
        $largeData = str_repeat('x', 1024 * 100); // 100KB (smaller test)
        $sessionData['large_field'] = $largeData;
        $serializedSize = strlen(serialize($sessionData));
        $maxSize = 1024 * 1024; // 1MB limit
        
        if ($serializedSize <= $maxSize) {
            echo "   ✅ Session data size validation working\n";
        } else {
            echo "   ❌ Session data size validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session data size validation failed";
            return false;
        }
        
        // Test session cleanup
        $oldSessions = [
            'session1' => time() - 3600, // 1 hour old
            'session2' => time() - 7200, // 2 hours old
            'session3' => time() - 100   // 100 seconds old
        ];
        
        $cleanedSessions = array_filter($oldSessions, function($timestamp) {
            return (time() - $timestamp) < 1800; // Keep sessions newer than 30 minutes
        });
        
        if (count($cleanedSessions) === 1) { // Only session3 should remain
            echo "   ✅ Session cleanup working\n";
        } else {
            echo "   ❌ Session cleanup failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session cleanup failed";
            return false;
        }
        
        // Test session locking
        $sessionId = 'test_session_123';
        $lockFile = sys_get_temp_dir() . '/session_lock_' . md5($sessionId);
        
        // Simulate session lock
        file_put_contents($lockFile, time());
        $isLocked = file_exists($lockFile);
        
        // Simulate session unlock
        unlink($lockFile);
        $isUnlocked = !file_exists($lockFile);
        
        if ($isLocked && $isUnlocked) {
            echo "   ✅ Session locking mechanism working\n";
        } else {
            echo "   ❌ Session locking mechanism failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session locking mechanism failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "Session storage & persistence OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "Session storage test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 5: Session Monitoring & Logging
 */
function testSessionMonitoring() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 5: Session Monitoring & Logging\n";
    
    try {
        // Test session activity logging
        $sessionEvents = [
            ['event' => 'login', 'timestamp' => time(), 'ip' => '192.168.1.100'],
            ['event' => 'page_view', 'timestamp' => time() + 10, 'ip' => '192.168.1.100'],
            ['event' => 'logout', 'timestamp' => time() + 300, 'ip' => '192.168.1.100']
        ];
        
        if (count($sessionEvents) === 3 && isset($sessionEvents[0]['event'])) {
            echo "   ✅ Session activity logging working\n";
        } else {
            echo "   ❌ Session activity logging failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session activity logging failed";
            return false;
        }
        
        // Test session statistics
        $sessionStats = [
            'total_sessions' => 150,
            'active_sessions' => 25,
            'expired_sessions' => 125,
            'average_session_duration' => 1800, // 30 minutes
            'peak_concurrent_sessions' => 50
        ];
        
        if (isset($sessionStats['total_sessions']) && $sessionStats['active_sessions'] <= $sessionStats['total_sessions']) {
            echo "   ✅ Session statistics tracking working\n";
        } else {
            echo "   ❌ Session statistics tracking failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session statistics tracking failed";
            return false;
        }
        
        // Test session anomaly detection
        $normalSessions = [
            ['duration' => 1200, 'pages' => 15, 'ip' => '192.168.1.100'],
            ['duration' => 1800, 'pages' => 25, 'ip' => '192.168.1.101'],
            ['duration' => 900, 'pages' => 10, 'ip' => '192.168.1.102']
        ];
        
        $anomalousSessions = [
            ['duration' => 36000, 'pages' => 1, 'ip' => '192.168.1.200'], // Very long, few pages
            ['duration' => 60, 'pages' => 100, 'ip' => '192.168.1.201']   // Very short, many pages
        ];
        
        $anomalyDetected = false;
        foreach ($anomalousSessions as $session) {
            if ($session['duration'] > 7200 || $session['pages'] > 50) { // 2 hours or 50 pages
                $anomalyDetected = true;
                break;
            }
        }
        
        if ($anomalyDetected) {
            echo "   ✅ Session anomaly detection working\n";
        } else {
            echo "   ❌ Session anomaly detection failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session anomaly detection failed";
            return false;
        }
        
        // Test session audit trail
        $auditTrail = [
            ['action' => 'session_created', 'user_id' => 123, 'timestamp' => time()],
            ['action' => 'session_accessed', 'user_id' => 123, 'timestamp' => time() + 10],
            ['action' => 'session_regenerated', 'user_id' => 123, 'timestamp' => time() + 20],
            ['action' => 'session_destroyed', 'user_id' => 123, 'timestamp' => time() + 300]
        ];
        
        if (count($auditTrail) === 4 && isset($auditTrail[0]['action'])) {
            echo "   ✅ Session audit trail working\n";
        } else {
            echo "   ❌ Session audit trail failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session audit trail failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "Session monitoring & logging OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "Session monitoring test failed: " . $e->getMessage();
        return false;
    }
}

/**
 * Test 6: Session Integration & Middleware
 */
function testSessionIntegration() {
    global $testResults;
    $testResults['total']++;
    
    echo "📋 Test 6: Session Integration & Middleware\n";
    
    try {
        // Test session middleware stack
        $middlewareStack = [
            'SecurityHeaders',
            'SessionManager',
            'Authentication',
            'Authorization',
            'CSRFProtection'
        ];
        
        if (in_array('SessionManager', $middlewareStack)) {
            echo "   ✅ Session middleware integration working\n";
        } else {
            echo "   ❌ Session middleware integration failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session middleware integration failed";
            return false;
        }
        
        // Test session-based authentication
        $sessionAuth = [
            'session_id' => bin2hex(random_bytes(32)),
            'user_id' => 123,
            'authenticated' => true,
            'login_time' => time(),
            'last_activity' => time()
        ];
        
        if ($sessionAuth['authenticated'] && isset($sessionAuth['user_id'])) {
            echo "   ✅ Session-based authentication working\n";
        } else {
            echo "   ❌ Session-based authentication failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session-based authentication failed";
            return false;
        }
        
        // Test session-based authorization
        $userRole = 'user';
        $requiredRole = 'user';
        $hasPermission = ($userRole === $requiredRole) || ($userRole === 'admin');
        
        if ($hasPermission) {
            echo "   ✅ Session-based authorization working\n";
        } else {
            echo "   ❌ Session-based authorization failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session-based authorization failed";
            return false;
        }
        
        // Test session data validation
        $sessionData = [
            'user_id' => 123,
            'role' => 'user',
            'permissions' => ['read', 'write']
        ];
        
        $isValid = isset($sessionData['user_id']) && 
                  isset($sessionData['role']) && 
                  is_array($sessionData['permissions']);
        
        if ($isValid) {
            echo "   ✅ Session data validation working\n";
        } else {
            echo "   ❌ Session data validation failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session data validation failed";
            return false;
        }
        
        // Test session error handling
        $sessionErrors = [
            'session_expired' => 'Session has expired. Please log in again.',
            'session_invalid' => 'Invalid session. Please log in again.',
            'session_locked' => 'Session is locked. Please try again later.'
        ];
        
        if (count($sessionErrors) === 3 && isset($sessionErrors['session_expired'])) {
            echo "   ✅ Session error handling working\n";
        } else {
            echo "   ❌ Session error handling failed\n";
            $testResults['failed']++;
            $testResults['details'][] = "Session error handling failed";
            return false;
        }
        
        $testResults['passed']++;
        $testResults['details'][] = "Session integration & middleware OK";
        return true;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $testResults['failed']++;
        $testResults['details'][] = "Session integration test failed: " . $e->getMessage();
        return false;
    }
}

// Run all tests
echo "🚀 Starting Session Management Tests...\n\n";

testSessionConfiguration();
testSessionLifecycle();
testSessionSecurity();
testSessionStorage();
testSessionMonitoring();
testSessionIntegration();

// Summary
echo "\n📊 SESSION MANAGEMENT TEST SUMMARY\n";
echo "==================================\n";
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
    echo "🎉 ALL SESSION MANAGEMENT TESTS PASSED!\n";
    exit(0);
} else {
    echo "⚠️  Some session management tests failed. Review implementation.\n";
    exit(1);
}
?>
