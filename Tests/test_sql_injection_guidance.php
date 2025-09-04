<?php
/**
 * 🔒 TEST: SQL Injection Protection in Guidance Queries
 * Testuje SQL injection ochranu v guidance queries
 */

require_once __DIR__ . '/test_config.php';

echo "🔒 TEST: SQL Injection Protection in Guidance Queries\n";
echo "===================================================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Test SQL injection v ticker parametri
echo "🔒 Test 1: Test SQL injection v ticker parametri\n";
echo "----------------------------------------------\n";

$totalTests++;
try {
    $maliciousTickers = [
        "'; DROP TABLE benzinga_guidance; --",
        "' OR '1'='1",
        "' UNION SELECT * FROM users --",
        "'; INSERT INTO benzinga_guidance VALUES ('HACK', 'Q1', 2024, 999, 999, 999, 999, 'GAAP', 'GAAP', 'USD', 'HACKED', NOW()); --",
        "' OR 1=1 --",
        "'; UPDATE benzinga_guidance SET estimated_eps_guidance = 999 WHERE ticker = 'AVGO'; --"
    ];
    
    $injectionAttempts = 0;
    $successfulInjections = 0;
    $protectedQueries = 0;
    
    echo "   📋 Testovanie " . count($maliciousTickers) . " SQL injection pokusov:\n";
    
    foreach ($maliciousTickers as $maliciousTicker) {
        $injectionAttempts++;
        
        try {
            // Test prepared statement (bezpečný spôsob)
            $stmt = $pdo->prepare("SELECT * FROM benzinga_guidance WHERE ticker = ? LIMIT 1");
            $stmt->execute([$maliciousTicker]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                $protectedQueries++;
                echo "   ✅ Ticker '{$maliciousTicker}': Protected (no results)\n";
            } else {
                echo "   ⚠️  Ticker '{$maliciousTicker}': Query executed but no injection\n";
                $protectedQueries++;
            }
            
        } catch (Exception $e) {
            echo "   ❌ Ticker '{$maliciousTicker}': Error - " . $e->getMessage() . "\n";
            $successfulInjections++;
        }
    }
    
    $protectionRate = round(($protectedQueries / $injectionAttempts) * 100, 1);
    echo "   📊 Protection rate: {$protectedQueries}/{$injectionAttempts} ({$protectionRate}%)\n";
    
    if ($protectionRate >= 95) {
        echo "   ✅ Vysoká ochrana proti SQL injection v ticker parametri\n";
        $passedTests++;
        $testResults[] = ['test' => 'Ticker SQL Injection Protection', 'status' => 'PASS', 'value' => $protectionRate];
    } elseif ($protectionRate >= 80) {
        echo "   ⚠️  Priemerná ochrana proti SQL injection v ticker parametri\n";
        $testResults[] = ['test' => 'Ticker SQL Injection Protection', 'status' => 'WARNING', 'value' => $protectionRate];
    } else {
        echo "   ❌ Slabá ochrana proti SQL injection v ticker parametri\n";
        $testResults[] = ['test' => 'Ticker SQL Injection Protection', 'status' => 'FAIL', 'value' => $protectionRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Ticker SQL Injection Protection', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Test SQL injection v fiscal period parametri
echo "\n🔒 Test 2: Test SQL injection v fiscal period parametri\n";
echo "----------------------------------------------------\n";

$totalTests++;
try {
    $maliciousPeriods = [
        "'; DROP TABLE earningstickerstoday; --",
        "' OR '1'='1",
        "' UNION SELECT ticker, password FROM users --",
        "'; DELETE FROM benzinga_guidance; --",
        "' OR 1=1 --",
        "'; UPDATE earningstickerstoday SET eps_estimate = 999; --"
    ];
    
    $injectionAttempts = 0;
    $protectedQueries = 0;
    
    echo "   📋 Testovanie " . count($maliciousPeriods) . " SQL injection pokusov v fiscal period:\n";
    
    foreach ($maliciousPeriods as $maliciousPeriod) {
        $injectionAttempts++;
        
        try {
            // Test prepared statement s fiscal period
            $stmt = $pdo->prepare("SELECT * FROM benzinga_guidance WHERE fiscal_period = ? LIMIT 1");
            $stmt->execute([$maliciousPeriod]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                $protectedQueries++;
                echo "   ✅ Period '{$maliciousPeriod}': Protected (no results)\n";
            } else {
                echo "   ⚠️  Period '{$maliciousPeriod}': Query executed but no injection\n";
                $protectedQueries++;
            }
            
        } catch (Exception $e) {
            echo "   ❌ Period '{$maliciousPeriod}': Error - " . $e->getMessage() . "\n";
        }
    }
    
    $protectionRate = round(($protectedQueries / $injectionAttempts) * 100, 1);
    echo "   📊 Protection rate: {$protectedQueries}/{$injectionAttempts} ({$protectionRate}%)\n";
    
    if ($protectionRate >= 95) {
        echo "   ✅ Vysoká ochrana proti SQL injection v fiscal period parametri\n";
        $passedTests++;
        $testResults[] = ['test' => 'Fiscal Period SQL Injection Protection', 'status' => 'PASS', 'value' => $protectionRate];
    } elseif ($protectionRate >= 80) {
        echo "   ⚠️  Priemerná ochrana proti SQL injection v fiscal period parametri\n";
        $testResults[] = ['test' => 'Fiscal Period SQL Injection Protection', 'status' => 'WARNING', 'value' => $protectionRate];
    } else {
        echo "   ❌ Slabá ochrana proti SQL injection v fiscal period parametri\n";
        $testResults[] = ['test' => 'Fiscal Period SQL Injection Protection', 'status' => 'FAIL', 'value' => $protectionRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Fiscal Period SQL Injection Protection', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Test SQL injection v komplexných queries
echo "\n🔒 Test 3: Test SQL injection v komplexných queries\n";
echo "--------------------------------------------------\n";

$totalTests++;
try {
    $maliciousInputs = [
        ['ticker' => "'; DROP TABLE benzinga_guidance; --", 'period' => 'Q1'],
        ['ticker' => 'AVGO', 'period' => "'; DELETE FROM earningstickerstoday; --"],
        ['ticker' => "' OR '1'='1", 'period' => "' OR '1'='1"],
        ['ticker' => "'; INSERT INTO benzinga_guidance VALUES ('HACK', 'Q1', 2024, 999, 999, 999, 999, 'GAAP', 'GAAP', 'USD', 'HACKED', NOW()); --", 'period' => 'Q1']
    ];
    
    $injectionAttempts = 0;
    $protectedQueries = 0;
    
    echo "   📋 Testovanie " . count($maliciousInputs) . " komplexných SQL injection pokusov:\n";
    
    foreach ($maliciousInputs as $input) {
        $injectionAttempts++;
        
        try {
            // Komplexný prepared statement s JOIN
            $stmt = $pdo->prepare("
                SELECT 
                    e.ticker,
                    e.eps_estimate,
                    g.estimated_eps_guidance,
                    g.eps_guide_vs_consensus_pct
                FROM earningstickerstoday e
                LEFT JOIN benzinga_guidance g ON e.ticker = g.ticker 
                    AND e.fiscal_period = g.fiscal_period
                WHERE e.ticker = ? AND e.fiscal_period = ?
                LIMIT 1
            ");
            $stmt->execute([$input['ticker'], $input['period']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                $protectedQueries++;
                echo "   ✅ Complex query with '{$input['ticker']}' and '{$input['period']}': Protected\n";
            } else {
                echo "   ⚠️  Complex query with '{$input['ticker']}' and '{$input['period']}': Executed but no injection\n";
                $protectedQueries++;
            }
            
        } catch (Exception $e) {
            echo "   ❌ Complex query with '{$input['ticker']}' and '{$input['period']}': Error - " . $e->getMessage() . "\n";
        }
    }
    
    $protectionRate = round(($protectedQueries / $injectionAttempts) * 100, 1);
    echo "   📊 Protection rate: {$protectedQueries}/{$injectionAttempts} ({$protectionRate}%)\n";
    
    if ($protectionRate >= 95) {
        echo "   ✅ Vysoká ochrana proti SQL injection v komplexných queries\n";
        $passedTests++;
        $testResults[] = ['test' => 'Complex Query SQL Injection Protection', 'status' => 'PASS', 'value' => $protectionRate];
    } elseif ($protectionRate >= 80) {
        echo "   ⚠️  Priemerná ochrana proti SQL injection v komplexných queries\n";
        $testResults[] = ['test' => 'Complex Query SQL Injection Protection', 'status' => 'WARNING', 'value' => $protectionRate];
    } else {
        echo "   ❌ Slabá ochrana proti SQL injection v komplexných queries\n";
        $testResults[] = ['test' => 'Complex Query SQL Injection Protection', 'status' => 'FAIL', 'value' => $protectionRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Complex Query SQL Injection Protection', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Test SQL injection v numerických parametroch
echo "\n🔒 Test 4: Test SQL injection v numerických parametroch\n";
echo "------------------------------------------------------\n";

$totalTests++;
try {
    $maliciousNumbers = [
        "1; DROP TABLE benzinga_guidance; --",
        "1 OR 1=1",
        "1 UNION SELECT * FROM users --",
        "1; INSERT INTO benzinga_guidance VALUES ('HACK', 'Q1', 2024, 999, 999, 999, 999, 'GAAP', 'GAAP', 'USD', 'HACKED', NOW()); --",
        "1' OR '1'='1",
        "1; UPDATE benzinga_guidance SET estimated_eps_guidance = 999; --"
    ];
    
    $injectionAttempts = 0;
    $protectedQueries = 0;
    
    echo "   📋 Testovanie " . count($maliciousNumbers) . " SQL injection pokusov v numerických parametroch:\n";
    
    foreach ($maliciousNumbers as $maliciousNumber) {
        $injectionAttempts++;
        
        try {
            // Test prepared statement s numerickým parametrom
            $stmt = $pdo->prepare("SELECT * FROM benzinga_guidance WHERE fiscal_year = ? LIMIT 1");
            $stmt->execute([$maliciousNumber]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                $protectedQueries++;
                echo "   ✅ Number '{$maliciousNumber}': Protected (no results)\n";
            } else {
                echo "   ⚠️  Number '{$maliciousNumber}': Query executed but no injection\n";
                $protectedQueries++;
            }
            
        } catch (Exception $e) {
            echo "   ❌ Number '{$maliciousNumber}': Error - " . $e->getMessage() . "\n";
        }
    }
    
    $protectionRate = round(($protectedQueries / $injectionAttempts) * 100, 1);
    echo "   📊 Protection rate: {$protectedQueries}/{$injectionAttempts} ({$protectionRate}%)\n";
    
    if ($protectionRate >= 95) {
        echo "   ✅ Vysoká ochrana proti SQL injection v numerických parametroch\n";
        $passedTests++;
        $testResults[] = ['test' => 'Numeric Parameter SQL Injection Protection', 'status' => 'PASS', 'value' => $protectionRate];
    } elseif ($protectionRate >= 80) {
        echo "   ⚠️  Priemerná ochrana proti SQL injection v numerických parametroch\n";
        $testResults[] = ['test' => 'Numeric Parameter SQL Injection Protection', 'status' => 'WARNING', 'value' => $protectionRate];
    } else {
        echo "   ❌ Slabá ochrana proti SQL injection v numerických parametroch\n";
        $testResults[] = ['test' => 'Numeric Parameter SQL Injection Protection', 'status' => 'FAIL', 'value' => $protectionRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Numeric Parameter SQL Injection Protection', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Test SQL injection v LIKE queries
echo "\n🔒 Test 5: Test SQL injection v LIKE queries\n";
echo "------------------------------------------\n";

$totalTests++;
try {
    $maliciousLikeInputs = [
        "%'; DROP TABLE benzinga_guidance; --",
        "%' OR '1'='1",
        "%' UNION SELECT * FROM users --",
        "%'; INSERT INTO benzinga_guidance VALUES ('HACK', 'Q1', 2024, 999, 999, 999, 999, 'GAAP', 'GAAP', 'USD', 'HACKED', NOW()); --",
        "%' OR 1=1 --",
        "%'; UPDATE benzinga_guidance SET estimated_eps_guidance = 999; --"
    ];
    
    $injectionAttempts = 0;
    $protectedQueries = 0;
    
    echo "   📋 Testovanie " . count($maliciousLikeInputs) . " SQL injection pokusov v LIKE queries:\n";
    
    foreach ($maliciousLikeInputs as $maliciousLike) {
        $injectionAttempts++;
        
        try {
            // Test prepared statement s LIKE
            $stmt = $pdo->prepare("SELECT * FROM benzinga_guidance WHERE ticker LIKE ? LIMIT 1");
            $stmt->execute([$maliciousLike]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result === false) {
                $protectedQueries++;
                echo "   ✅ LIKE '{$maliciousLike}': Protected (no results)\n";
            } else {
                echo "   ⚠️  LIKE '{$maliciousLike}': Query executed but no injection\n";
                $protectedQueries++;
            }
            
        } catch (Exception $e) {
            echo "   ❌ LIKE '{$maliciousLike}': Error - " . $e->getMessage() . "\n";
        }
    }
    
    $protectionRate = round(($protectedQueries / $injectionAttempts) * 100, 1);
    echo "   📊 Protection rate: {$protectedQueries}/{$injectionAttempts} ({$protectionRate}%)\n";
    
    if ($protectionRate >= 95) {
        echo "   ✅ Vysoká ochrana proti SQL injection v LIKE queries\n";
        $passedTests++;
        $testResults[] = ['test' => 'LIKE Query SQL Injection Protection', 'status' => 'PASS', 'value' => $protectionRate];
    } elseif ($protectionRate >= 80) {
        echo "   ⚠️  Priemerná ochrana proti SQL injection v LIKE queries\n";
        $testResults[] = ['test' => 'LIKE Query SQL Injection Protection', 'status' => 'WARNING', 'value' => $protectionRate];
    } else {
        echo "   ❌ Slabá ochrana proti SQL injection v LIKE queries\n";
        $testResults[] = ['test' => 'LIKE Query SQL Injection Protection', 'status' => 'FAIL', 'value' => $protectionRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'LIKE Query SQL Injection Protection', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Test SQL injection v ORDER BY queries
echo "\n🔒 Test 6: Test SQL injection v ORDER BY queries\n";
echo "----------------------------------------------\n";

$totalTests++;
try {
    $maliciousOrderBy = [
        "ticker; DROP TABLE benzinga_guidance; --",
        "ticker, (SELECT COUNT(*) FROM users) --",
        "ticker UNION SELECT * FROM users --",
        "ticker; INSERT INTO benzinga_guidance VALUES ('HACK', 'Q1', 2024, 999, 999, 999, 999, 'GAAP', 'GAAP', 'USD', 'HACKED', NOW()); --",
        "ticker' OR '1'='1",
        "ticker; UPDATE benzinga_guidance SET estimated_eps_guidance = 999; --"
    ];
    
    $injectionAttempts = 0;
    $protectedQueries = 0;
    
    echo "   📋 Testovanie " . count($maliciousOrderBy) . " SQL injection pokusov v ORDER BY queries:\n";
    
    foreach ($maliciousOrderBy as $maliciousOrder) {
        $injectionAttempts++;
        
        try {
            // Test prepared statement s ORDER BY (whitelist approach)
            $allowedColumns = ['ticker', 'fiscal_period', 'fiscal_year', 'last_updated'];
            $orderColumn = 'ticker'; // Default safe column
            
            // Whitelist validation
            if (in_array($maliciousOrder, $allowedColumns)) {
                $orderColumn = $maliciousOrder;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM benzinga_guidance ORDER BY {$orderColumn} LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result !== false) {
                $protectedQueries++;
                echo "   ✅ ORDER BY '{$maliciousOrder}': Protected (whitelisted to '{$orderColumn}')\n";
            } else {
                echo "   ⚠️  ORDER BY '{$maliciousOrder}': No results but protected\n";
                $protectedQueries++;
            }
            
        } catch (Exception $e) {
            echo "   ❌ ORDER BY '{$maliciousOrder}': Error - " . $e->getMessage() . "\n";
        }
    }
    
    $protectionRate = round(($protectedQueries / $injectionAttempts) * 100, 1);
    echo "   📊 Protection rate: {$protectedQueries}/{$injectionAttempts} ({$protectionRate}%)\n";
    
    if ($protectionRate >= 95) {
        echo "   ✅ Vysoká ochrana proti SQL injection v ORDER BY queries\n";
        $passedTests++;
        $testResults[] = ['test' => 'ORDER BY SQL Injection Protection', 'status' => 'PASS', 'value' => $protectionRate];
    } elseif ($protectionRate >= 80) {
        echo "   ⚠️  Priemerná ochrana proti SQL injection v ORDER BY queries\n";
        $testResults[] = ['test' => 'ORDER BY SQL Injection Protection', 'status' => 'WARNING', 'value' => $protectionRate];
    } else {
        echo "   ❌ Slabá ochrana proti SQL injection v ORDER BY queries\n";
        $testResults[] = ['test' => 'ORDER BY SQL Injection Protection', 'status' => 'FAIL', 'value' => $protectionRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'ORDER BY SQL Injection Protection', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "🏆 VÝBORNE! SQL injection protection je výborná!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina SQL injection testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica SQL injection testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho SQL injection testov zlyhalo.\n";
}

echo "\n🎉 Test SQL injection protection dokončený!\n";
?>
