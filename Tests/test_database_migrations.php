<?php
/**
 * 🔧 TEST: Database Migrations
 * Testuje migračné skripty
 */

require_once __DIR__ . '/test_config.php';

echo "🔧 TEST: Database Migrations\n";
echo "============================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Kontrola existencie migračných súborov
echo "📊 Test 1: Kontrola existencie migračných súborov\n";
echo "------------------------------------------------\n";

$totalTests++;
try {
    $migrationFiles = [
        'sql/add_fiscal_periods_to_ett.sql',
        'sql/add_fiscal_periods.php',
        'sql/collation_migration.sql',
        'sql/simple_collation_migration.sql',
        'sql/complete_collation_migration.php',
        'sql/setup_database.sql',
        'sql/setup_all_tables.sql'
    ];
    
    $existingFiles = 0;
    $totalFiles = count($migrationFiles);
    
    echo "   📋 Kontrola {$totalFiles} migračných súborov:\n";
    
    foreach ($migrationFiles as $file) {
        if (file_exists($file)) {
            $existingFiles++;
            $size = filesize($file);
            echo "   ✅ {$file} ({$size} bytes)\n";
        } else {
            echo "   ❌ {$file} (neexistuje)\n";
        }
    }
    
    $existenceRate = round(($existingFiles / $totalFiles) * 100, 1);
    echo "   📊 Existencia súborov: {$existingFiles}/{$totalFiles} ({$existenceRate}%)\n";
    
    if ($existenceRate >= 80) {
        echo "   ✅ Väčšina migračných súborov existuje\n";
        $passedTests++;
        $testResults[] = ['test' => 'Migration Files Existence', 'status' => 'PASS', 'value' => $existenceRate];
    } else {
        echo "   ❌ Mnoho migračných súborov chýba\n";
        $testResults[] = ['test' => 'Migration Files Existence', 'status' => 'FAIL', 'value' => $existenceRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Migration Files Existence', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Kontrola syntaxe SQL migračných súborov
echo "\n📊 Test 2: Kontrola syntaxe SQL migračných súborov\n";
echo "--------------------------------------------------\n";

$totalTests++;
try {
    $sqlFiles = [
        'sql/add_fiscal_periods_to_ett.sql',
        'sql/collation_migration.sql',
        'sql/simple_collation_migration.sql',
        'sql/setup_database.sql',
        'sql/setup_all_tables.sql'
    ];
    
    $validFiles = 0;
    $totalFiles = 0;
    
    echo "   📋 Kontrola syntaxe SQL súborov:\n";
    
    foreach ($sqlFiles as $file) {
        if (file_exists($file)) {
            $totalFiles++;
            $content = file_get_contents($file);
            
            // Základná kontrola syntaxe
            $hasSemicolon = strpos($content, ';') !== false;
            $hasAlterTable = strpos($content, 'ALTER TABLE') !== false || strpos($content, 'CREATE TABLE') !== false;
            $hasValidKeywords = preg_match('/\b(ALTER|CREATE|DROP|INSERT|UPDATE|SELECT)\b/i', $content);
            
            if ($hasSemicolon && $hasValidKeywords) {
                $validFiles++;
                echo "   ✅ {$file} (syntax OK)\n";
            } else {
                echo "   ❌ {$file} (syntax problém)\n";
            }
        }
    }
    
    if ($totalFiles > 0) {
        $validityRate = round(($validFiles / $totalFiles) * 100, 1);
        echo "   📊 Syntax validity: {$validFiles}/{$totalFiles} ({$validityRate}%)\n";
        
        if ($validityRate >= 80) {
            echo "   ✅ Väčšina SQL súborov má správnu syntax\n";
            $passedTests++;
            $testResults[] = ['test' => 'SQL Syntax Validation', 'status' => 'PASS', 'value' => $validityRate];
        } else {
            echo "   ❌ Mnoho SQL súborov má syntax problémy\n";
            $testResults[] = ['test' => 'SQL Syntax Validation', 'status' => 'FAIL', 'value' => $validityRate];
        }
    } else {
        echo "   ⚠️  Žiadne SQL súbory na kontrolu\n";
        $testResults[] = ['test' => 'SQL Syntax Validation', 'status' => 'SKIP', 'value' => 'NO_FILES'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'SQL Syntax Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Test migrácie fiscal periods
echo "\n📊 Test 3: Test migrácie fiscal periods\n";
echo "--------------------------------------\n";

$totalTests++;
try {
    // Kontrola, či existujú fiscal_period a fiscal_year stĺpce
    $stmt = $pdo->query("
        SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'earningstickerstoday'
        AND COLUMN_NAME IN ('fiscal_period', 'fiscal_year')
        ORDER BY COLUMN_NAME
    ");
    
    $fiscalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($fiscalColumns) === 2) {
        echo "   ✅ Fiscal period stĺpce existujú:\n";
        foreach ($fiscalColumns as $col) {
            echo "   📋 {$col['COLUMN_NAME']}: {$col['DATA_TYPE']} (nullable: {$col['IS_NULLABLE']})\n";
        }
        
        // Kontrola, či sú stĺpce vyplnené
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                COUNT(fiscal_period) as period_count,
                COUNT(fiscal_year) as year_count
            FROM earningstickerstoday
        ");
        
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        $periodRate = $counts['total'] > 0 ? round(($counts['period_count'] / $counts['total']) * 100, 1) : 0;
        $yearRate = $counts['total'] > 0 ? round(($counts['year_count'] / $counts['total']) * 100, 1) : 0;
        
        echo "   📊 Fiscal period data: {$counts['period_count']}/{$counts['total']} ({$periodRate}%)\n";
        echo "   📊 Fiscal year data: {$counts['year_count']}/{$counts['total']} ({$yearRate}%)\n";
        
        if ($periodRate >= 80 && $yearRate >= 80) {
            echo "   ✅ Fiscal period migrácia je úspešná\n";
            $passedTests++;
            $testResults[] = ['test' => 'Fiscal Period Migration', 'status' => 'PASS', 'value' => $periodRate];
        } else {
            echo "   ⚠️  Fiscal period migrácia je čiastočná\n";
            $testResults[] = ['test' => 'Fiscal Period Migration', 'status' => 'WARNING', 'value' => $periodRate];
        }
    } else {
        echo "   ❌ Fiscal period stĺpce neexistujú alebo sú neúplné\n";
        $testResults[] = ['test' => 'Fiscal Period Migration', 'status' => 'FAIL', 'value' => count($fiscalColumns)];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Fiscal Period Migration', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Test collation migrácie
echo "\n📊 Test 4: Test collation migrácie\n";
echo "----------------------------------\n";

$totalTests++;
try {
    // Kontrola collation všetkých tabuliek
    $stmt = $pdo->query("
        SELECT TABLE_NAME, TABLE_COLLATION 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_TYPE = 'BASE TABLE'
    ");
    
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $targetCollation = 'utf8mb4_unicode_ci';
    
    $migratedTables = 0;
    $totalTables = count($tables);
    
    echo "   📋 Kontrola collation migrácie pre {$totalTables} tabuliek:\n";
    
    foreach ($tables as $table) {
        if ($table['TABLE_COLLATION'] === $targetCollation) {
            $migratedTables++;
            echo "   ✅ {$table['TABLE_NAME']}: {$table['TABLE_COLLATION']}\n";
        } else {
            echo "   ❌ {$table['TABLE_NAME']}: {$table['TABLE_COLLATION']}\n";
        }
    }
    
    $migrationRate = round(($migratedTables / $totalTables) * 100, 1);
    echo "   📊 Collation migrácia: {$migratedTables}/{$totalTables} ({$migrationRate}%)\n";
    
    if ($migrationRate >= 100) {
        echo "   ✅ Collation migrácia je úspešná\n";
        $passedTests++;
        $testResults[] = ['test' => 'Collation Migration', 'status' => 'PASS', 'value' => $migrationRate];
    } elseif ($migrationRate >= 80) {
        echo "   ⚠️  Collation migrácia je čiastočná\n";
        $testResults[] = ['test' => 'Collation Migration', 'status' => 'WARNING', 'value' => $migrationRate];
    } else {
        echo "   ❌ Collation migrácia zlyhala\n";
        $testResults[] = ['test' => 'Collation Migration', 'status' => 'FAIL', 'value' => $migrationRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Collation Migration', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Test migračných skriptov spustiteľnosť
echo "\n📊 Test 5: Test migračných skriptov spustiteľnosť\n";
echo "------------------------------------------------\n";

$totalTests++;
try {
    $phpMigrationFiles = [
        'sql/add_fiscal_periods.php',
        'sql/complete_collation_migration.php'
    ];
    
    $executableFiles = 0;
    $totalFiles = 0;
    
    echo "   📋 Kontrola spustiteľnosti PHP migračných súborov:\n";
    
    foreach ($phpMigrationFiles as $file) {
        if (file_exists($file)) {
            $totalFiles++;
            $content = file_get_contents($file);
            
            // Kontrola základných PHP konštrukcií
            $hasPhpTag = strpos($content, '<?php') !== false;
            $hasRequire = strpos($content, 'require_once') !== false;
            $hasPdo = strpos($content, '$pdo') !== false;
            $hasTryCatch = strpos($content, 'try') !== false && strpos($content, 'catch') !== false;
            
            if ($hasPhpTag && $hasRequire && $hasPdo) {
                $executableFiles++;
                echo "   ✅ {$file} (spustiteľný)\n";
            } else {
                echo "   ❌ {$file} (spustiteľnosť problém)\n";
            }
        }
    }
    
    if ($totalFiles > 0) {
        $executabilityRate = round(($executableFiles / $totalFiles) * 100, 1);
        echo "   📊 Spustiteľnosť: {$executableFiles}/{$totalFiles} ({$executabilityRate}%)\n";
        
        if ($executabilityRate >= 80) {
            echo "   ✅ Väčšina migračných skriptov je spustiteľná\n";
            $passedTests++;
            $testResults[] = ['test' => 'Migration Scripts Executability', 'status' => 'PASS', 'value' => $executabilityRate];
        } else {
            echo "   ❌ Mnoho migračných skriptov nie je spustiteľných\n";
            $testResults[] = ['test' => 'Migration Scripts Executability', 'status' => 'FAIL', 'value' => $executabilityRate];
        }
    } else {
        echo "   ⚠️  Žiadne PHP migračné súbory na kontrolu\n";
        $testResults[] = ['test' => 'Migration Scripts Executability', 'status' => 'SKIP', 'value' => 'NO_FILES'];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Migration Scripts Executability', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Test rollback možností
echo "\n📊 Test 6: Test rollback možností\n";
echo "---------------------------------\n";

$totalTests++;
try {
    // Kontrola, či existujú backup súbory alebo rollback skripty
    $rollbackFiles = [
        'archive/',
        'deploy/rollback.sh',
        'sql/reset_root.sql'
    ];
    
    $existingRollbacks = 0;
    $totalRollbacks = count($rollbackFiles);
    
    echo "   📋 Kontrola rollback možností:\n";
    
    foreach ($rollbackFiles as $file) {
        if (file_exists($file)) {
            $existingRollbacks++;
            if (is_dir($file)) {
                $files = glob($file . '*');
                echo "   ✅ {$file} (adresár s " . count($files) . " súbormi)\n";
            } else {
                $size = filesize($file);
                echo "   ✅ {$file} ({$size} bytes)\n";
            }
        } else {
            echo "   ❌ {$file} (neexistuje)\n";
        }
    }
    
    $rollbackRate = round(($existingRollbacks / $totalRollbacks) * 100, 1);
    echo "   📊 Rollback možnosti: {$existingRollbacks}/{$totalRollbacks} ({$rollbackRate}%)\n";
    
    if ($rollbackRate >= 50) {
        echo "   ✅ Existujú rollback možnosti\n";
        $passedTests++;
        $testResults[] = ['test' => 'Rollback Options', 'status' => 'PASS', 'value' => $rollbackRate];
    } else {
        echo "   ⚠️  Obmedzené rollback možnosti\n";
        $testResults[] = ['test' => 'Rollback Options', 'status' => 'WARNING', 'value' => $rollbackRate];
    }
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Rollback Options', 'status' => 'ERROR', 'value' => $e->getMessage()];
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
    echo "   $statusIcon {$result['test']}: {$result['value']}\n";
}

echo "\n";
if ($successRate >= 90) {
    echo "🏆 VÝBORNE! Všetky migrácie sú v poriadku!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina migračných testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica migračných testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho migračných testov zlyhalo.\n";
}

echo "\n🎉 Test database migrations dokončený!\n";
?>
