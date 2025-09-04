<?php
/**
 * 🔒 TEST: Input Validation
 * Testuje validáciu vstupných dát
 */

require_once __DIR__ . '/test_config.php';

echo "🔒 TEST: Input Validation\n";
echo "========================\n";
echo "Dátum: " . date('Y-m-d H:i:s') . "\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

// Test 1: Validácia ticker formátu
echo "🔒 Test 1: Validácia ticker formátu\n";
echo "----------------------------------\n";

$totalTests++;
try {
    $testTickers = [
        // Valid tickers
        'AAPL' => true,
        'MSFT' => true,
        'GOOGL' => true,
        'TSLA' => true,
        'AMZN' => true,
        
        // Invalid tickers
        '' => false,
        'A' => false,
        'AA' => false,
        'AAAAA' => false,
        '123' => false,
        'AAPL123' => false,
        'aapl' => false,
        'AAPL!' => false,
        'AAPL@' => false,
        'AAPL#' => false,
        'AAPL$' => false,
        'AAPL%' => false,
        'AAPL^' => false,
        'AAPL&' => false,
        'AAPL*' => false,
        'AAPL(' => false,
        'AAPL)' => false,
        'AAPL-' => false,
        'AAPL_' => false,
        'AAPL+' => false,
        'AAPL=' => false,
        'AAPL[' => false,
        'AAPL]' => false,
        'AAPL{' => false,
        'AAPL}' => false,
        'AAPL|' => false,
        'AAPL\\' => false,
        'AAPL:' => false,
        'AAPL;' => false,
        'AAPL"' => false,
        "AAPL'" => false,
        'AAPL<' => false,
        'AAPL>' => false,
        'AAPL,' => false,
        'AAPL.' => false,
        'AAPL?' => false,
        'AAPL/' => false,
        'AAPL ' => false,
        ' AAPL' => false,
        'AA PL' => false,
        'AA\tPL' => false,
        'AA\nPL' => false,
        'AA\rPL' => false
    ];
    
    $validatedTickers = 0;
    $totalTickers = count($testTickers);
    
    echo "   📋 Testovanie {$totalTickers} ticker formátov:\n";
    
    foreach ($testTickers as $ticker => $expectedValid) {
        // Ticker validation function
        $isValid = validateTicker($ticker);
        
        if ($isValid === $expectedValid) {
            $validatedTickers++;
            $statusIcon = $isValid ? '✅' : '❌';
            echo "   {$statusIcon} '{$ticker}': " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        } else {
            $statusIcon = '⚠️';
            echo "   {$statusIcon} '{$ticker}': Expected " . ($expectedValid ? 'Valid' : 'Invalid') . ", Got " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        }
    }
    
    $validationRate = round(($validatedTickers / $totalTickers) * 100, 1);
    echo "   📊 Validation rate: {$validatedTickers}/{$totalTickers} ({$validationRate}%)\n";
    
    if ($validationRate >= 95) {
        echo "   ✅ Vysoká validácia ticker formátu\n";
        $passedTests++;
        $testResults[] = ['test' => 'Ticker Format Validation', 'status' => 'PASS', 'value' => $validationRate];
    } elseif ($validationRate >= 80) {
        echo "   ⚠️  Priemerná validácia ticker formátu\n";
        $testResults[] = ['test' => 'Ticker Format Validation', 'status' => 'WARNING', 'value' => $validationRate];
    } else {
        echo "   ❌ Slabá validácia ticker formátu\n";
        $testResults[] = ['test' => 'Ticker Format Validation', 'status' => 'FAIL', 'value' => $validationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Ticker Format Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 2: Validácia fiscal period formátu
echo "\n🔒 Test 2: Validácia fiscal period formátu\n";
echo "-----------------------------------------\n";

$totalTests++;
try {
    $testPeriods = [
        // Valid periods
        'Q1' => true,
        'Q2' => true,
        'Q3' => true,
        'Q4' => true,
        'FY' => true,
        'H1' => true,
        'H2' => true,
        
        // Invalid periods
        '' => false,
        'Q' => false,
        'Q0' => false,
        'Q5' => false,
        'Q10' => false,
        'q1' => false,
        'q2' => false,
        'fy' => false,
        'FY ' => false,
        ' FY' => false,
        'F Y' => false,
        'Q 1' => false,
        'Q1!' => false,
        'Q1@' => false,
        'Q1#' => false,
        'Q1$' => false,
        'Q1%' => false,
        'Q1^' => false,
        'Q1&' => false,
        'Q1*' => false,
        'Q1(' => false,
        'Q1)' => false,
        'Q1-' => false,
        'Q1_' => false,
        'Q1+' => false,
        'Q1=' => false,
        'Q1[' => false,
        'Q1]' => false,
        'Q1{' => false,
        'Q1}' => false,
        'Q1|' => false,
        'Q1\\' => false,
        'Q1:' => false,
        'Q1;' => false,
        'Q1"' => false,
        "Q1'" => false,
        'Q1<' => false,
        'Q1>' => false,
        'Q1,' => false,
        'Q1.' => false,
        'Q1?' => false,
        'Q1/' => false,
        'Q1 ' => false,
        ' Q1' => false,
        'Q1\t' => false,
        'Q1\n' => false,
        'Q1\r' => false,
        '1Q' => false,
        '2Q' => false,
        '3Q' => false,
        '4Q' => false,
        '1H' => false,
        '2H' => false
    ];
    
    $validatedPeriods = 0;
    $totalPeriods = count($testPeriods);
    
    echo "   📋 Testovanie {$totalPeriods} fiscal period formátov:\n";
    
    foreach ($testPeriods as $period => $expectedValid) {
        // Fiscal period validation function
        $isValid = validateFiscalPeriod($period);
        
        if ($isValid === $expectedValid) {
            $validatedPeriods++;
            $statusIcon = $isValid ? '✅' : '❌';
            echo "   {$statusIcon} '{$period}': " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        } else {
            $statusIcon = '⚠️';
            echo "   {$statusIcon} '{$period}': Expected " . ($expectedValid ? 'Valid' : 'Invalid') . ", Got " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        }
    }
    
    $validationRate = round(($validatedPeriods / $totalPeriods) * 100, 1);
    echo "   📊 Validation rate: {$validatedPeriods}/{$totalPeriods} ({$validationRate}%)\n";
    
    if ($validationRate >= 95) {
        echo "   ✅ Vysoká validácia fiscal period formátu\n";
        $passedTests++;
        $testResults[] = ['test' => 'Fiscal Period Format Validation', 'status' => 'PASS', 'value' => $validationRate];
    } elseif ($validationRate >= 80) {
        echo "   ⚠️  Priemerná validácia fiscal period formátu\n";
        $testResults[] = ['test' => 'Fiscal Period Format Validation', 'status' => 'WARNING', 'value' => $validationRate];
    } else {
        echo "   ❌ Slabá validácia fiscal period formátu\n";
        $testResults[] = ['test' => 'Fiscal Period Format Validation', 'status' => 'FAIL', 'value' => $validationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Fiscal Period Format Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 3: Validácia fiscal year formátu
echo "\n🔒 Test 3: Validácia fiscal year formátu\n";
echo "---------------------------------------\n";

$totalTests++;
try {
    $testYears = [
        // Valid years
        2020 => true,
        2021 => true,
        2022 => true,
        2023 => true,
        2024 => true,
        2025 => true,
        2026 => true,
        2027 => true,
        2028 => true,
        2029 => true,
        2030 => true,
        
        // Invalid years
        0 => false,
        1 => false,
        100 => false,
        1999 => false,
        2031 => false,
        2032 => false,
        3000 => false,
        -1 => false,
        -2024 => false,
        '2024' => false,
        '2024.0' => false,
        '2024.5' => false,
        '2024a' => false,
        'a2024' => false,
        '2024!' => false,
        '2024@' => false,
        '2024#' => false,
        '2024$' => false,
        '2024%' => false,
        '2024^' => false,
        '2024&' => false,
        '2024*' => false,
        '2024(' => false,
        '2024)' => false,
        '2024-' => false,
        '2024_' => false,
        '2024+' => false,
        '2024=' => false,
        '2024[' => false,
        '2024]' => false,
        '2024{' => false,
        '2024}' => false,
        '2024|' => false,
        '2024\\' => false,
        '2024:' => false,
        '2024;' => false,
        '2024"' => false,
        "2024'" => false,
        '2024<' => false,
        '2024>' => false,
        '2024,' => false,
        '2024.' => false,
        '2024?' => false,
        '2024/' => false,
        '2024 ' => false,
        ' 2024' => false,
        '2024\t' => false,
        '2024\n' => false,
        '2024\r' => false,
        '' => false,
        null => false,
        false => false,
        true => false,
        [] => false,
        (object)[] => false
    ];
    
    $validatedYears = 0;
    $totalYears = count($testYears);
    
    echo "   📋 Testovanie {$totalYears} fiscal year formátov:\n";
    
    foreach ($testYears as $year => $expectedValid) {
        // Fiscal year validation function
        $isValid = validateFiscalYear($year);
        
        if ($isValid === $expectedValid) {
            $validatedYears++;
            $statusIcon = $isValid ? '✅' : '❌';
            echo "   {$statusIcon} '{$year}': " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        } else {
            $statusIcon = '⚠️';
            echo "   {$statusIcon} '{$year}': Expected " . ($expectedValid ? 'Valid' : 'Invalid') . ", Got " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        }
    }
    
    $validationRate = round(($validatedYears / $totalYears) * 100, 1);
    echo "   📊 Validation rate: {$validatedYears}/{$totalYears} ({$validationRate}%)\n";
    
    if ($validationRate >= 95) {
        echo "   ✅ Vysoká validácia fiscal year formátu\n";
        $passedTests++;
        $testResults[] = ['test' => 'Fiscal Year Format Validation', 'status' => 'PASS', 'value' => $validationRate];
    } elseif ($validationRate >= 80) {
        echo "   ⚠️  Priemerná validácia fiscal year formátu\n";
        $testResults[] = ['test' => 'Fiscal Year Format Validation', 'status' => 'WARNING', 'value' => $validationRate];
    } else {
        echo "   ❌ Slabá validácia fiscal year formátu\n";
        $testResults[] = ['test' => 'Fiscal Year Format Validation', 'status' => 'FAIL', 'value' => $validationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Fiscal Year Format Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 4: Validácia numerických hodnôt
echo "\n🔒 Test 4: Validácia numerických hodnôt\n";
echo "--------------------------------------\n";

$totalTests++;
try {
    $testNumbers = [
        // Valid numbers
        0 => true,
        1 => true,
        -1 => true,
        1.5 => true,
        -1.5 => true,
        100 => true,
        -100 => true,
        100.25 => true,
        -100.25 => true,
        1000 => true,
        -1000 => true,
        1000.75 => true,
        -1000.75 => true,
        1000000 => true,
        -1000000 => true,
        1000000.99 => true,
        -1000000.99 => true,
        
        // Invalid numbers
        '' => false,
        '0' => false,
        '1' => false,
        '-1' => false,
        '1.5' => false,
        '-1.5' => false,
        '100' => false,
        '-100' => false,
        '100.25' => false,
        '-100.25' => false,
        '1000' => false,
        '-1000' => false,
        '1000.75' => false,
        '-1000.75' => false,
        '1000000' => false,
        '-1000000' => false,
        '1000000.99' => false,
        '-1000000.99' => false,
        '0a' => false,
        'a0' => false,
        '0!' => false,
        '0@' => false,
        '0#' => false,
        '0$' => false,
        '0%' => false,
        '0^' => false,
        '0&' => false,
        '0*' => false,
        '0(' => false,
        '0)' => false,
        '0-' => false,
        '0_' => false,
        '0+' => false,
        '0=' => false,
        '0[' => false,
        '0]' => false,
        '0{' => false,
        '0}' => false,
        '0|' => false,
        '0\\' => false,
        '0:' => false,
        '0;' => false,
        '0"' => false,
        "0'" => false,
        '0<' => false,
        '0>' => false,
        '0,' => false,
        '0.' => false,
        '0?' => false,
        '0/' => false,
        '0 ' => false,
        ' 0' => false,
        '0\t' => false,
        '0\n' => false,
        '0\r' => false,
        null => false,
        false => false,
        true => false,
        [] => false,
        (object)[] => false,
        INF => false,
        -INF => false,
        NAN => false
    ];
    
    $validatedNumbers = 0;
    $totalNumbers = count($testNumbers);
    
    echo "   📋 Testovanie {$totalNumbers} numerických formátov:\n";
    
    foreach ($testNumbers as $number => $expectedValid) {
        // Numeric validation function
        $isValid = validateNumeric($number);
        
        if ($isValid === $expectedValid) {
            $validatedNumbers++;
            $statusIcon = $isValid ? '✅' : '❌';
            echo "   {$statusIcon} '{$number}': " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        } else {
            $statusIcon = '⚠️';
            echo "   {$statusIcon} '{$number}': Expected " . ($expectedValid ? 'Valid' : 'Invalid') . ", Got " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        }
    }
    
    $validationRate = round(($validatedNumbers / $totalNumbers) * 100, 1);
    echo "   📊 Validation rate: {$validatedNumbers}/{$totalNumbers} ({$validationRate}%)\n";
    
    if ($validationRate >= 95) {
        echo "   ✅ Vysoká validácia numerických hodnôt\n";
        $passedTests++;
        $testResults[] = ['test' => 'Numeric Value Validation', 'status' => 'PASS', 'value' => $validationRate];
    } elseif ($validationRate >= 80) {
        echo "   ⚠️  Priemerná validácia numerických hodnôt\n";
        $testResults[] = ['test' => 'Numeric Value Validation', 'status' => 'WARNING', 'value' => $validationRate];
    } else {
        echo "   ❌ Slabá validácia numerických hodnôt\n";
        $testResults[] = ['test' => 'Numeric Value Validation', 'status' => 'FAIL', 'value' => $validationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Numeric Value Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 5: Validácia dátumov
echo "\n🔒 Test 5: Validácia dátumov\n";
echo "---------------------------\n";

$totalTests++;
try {
    $testDates = [
        // Valid dates
        '2024-01-01' => true,
        '2024-01-31' => true,
        '2024-02-29' => true, // Leap year
        '2024-03-31' => true,
        '2024-04-30' => true,
        '2024-05-31' => true,
        '2024-06-30' => true,
        '2024-07-31' => true,
        '2024-08-31' => true,
        '2024-09-30' => true,
        '2024-10-31' => true,
        '2024-11-30' => true,
        '2024-12-31' => true,
        '2023-02-28' => true, // Non-leap year
        '2020-02-29' => true, // Leap year
        '2021-02-28' => true, // Non-leap year
        
        // Invalid dates
        '' => false,
        '2024-01-00' => false,
        '2024-01-32' => false,
        '2024-00-01' => false,
        '2024-13-01' => false,
        '2024-02-30' => false,
        '2024-04-31' => false,
        '2024-06-31' => false,
        '2024-09-31' => false,
        '2024-11-31' => false,
        '2023-02-29' => false, // Non-leap year
        '2021-02-29' => false, // Non-leap year
        '2024-01-01 00:00:00' => false,
        '2024-01-01T00:00:00' => false,
        '01/01/2024' => false,
        '01-01-2024' => false,
        '2024/01/01' => false,
        '2024.01.01' => false,
        '2024 01 01' => false,
        '2024-1-1' => false,
        '2024-01-1' => false,
        '2024-1-01' => false,
        '24-01-01' => false,
        '2024-1-1' => false,
        '2024-01-01!' => false,
        '2024-01-01@' => false,
        '2024-01-01#' => false,
        '2024-01-01$' => false,
        '2024-01-01%' => false,
        '2024-01-01^' => false,
        '2024-01-01&' => false,
        '2024-01-01*' => false,
        '2024-01-01(' => false,
        '2024-01-01)' => false,
        '2024-01-01-' => false,
        '2024-01-01_' => false,
        '2024-01-01+' => false,
        '2024-01-01=' => false,
        '2024-01-01[' => false,
        '2024-01-01]' => false,
        '2024-01-01{' => false,
        '2024-01-01}' => false,
        '2024-01-01|' => false,
        '2024-01-01\\' => false,
        '2024-01-01:' => false,
        '2024-01-01;' => false,
        '2024-01-01"' => false,
        "2024-01-01'" => false,
        '2024-01-01<' => false,
        '2024-01-01>' => false,
        '2024-01-01,' => false,
        '2024-01-01.' => false,
        '2024-01-01?' => false,
        '2024-01-01/' => false,
        '2024-01-01 ' => false,
        ' 2024-01-01' => false,
        '2024-01-01\t' => false,
        '2024-01-01\n' => false,
        '2024-01-01\r' => false,
        null => false,
        false => false,
        true => false,
        [] => false,
        (object)[] => false
    ];
    
    $validatedDates = 0;
    $totalDates = count($testDates);
    
    echo "   📋 Testovanie {$totalDates} dátumových formátov:\n";
    
    foreach ($testDates as $date => $expectedValid) {
        // Date validation function
        $isValid = validateDate($date);
        
        if ($isValid === $expectedValid) {
            $validatedDates++;
            $statusIcon = $isValid ? '✅' : '❌';
            echo "   {$statusIcon} '{$date}': " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        } else {
            $statusIcon = '⚠️';
            echo "   {$statusIcon} '{$date}': Expected " . ($expectedValid ? 'Valid' : 'Invalid') . ", Got " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        }
    }
    
    $validationRate = round(($validatedDates / $totalDates) * 100, 1);
    echo "   📊 Validation rate: {$validatedDates}/{$totalDates} ({$validationRate}%)\n";
    
    if ($validationRate >= 95) {
        echo "   ✅ Vysoká validácia dátumov\n";
        $passedTests++;
        $testResults[] = ['test' => 'Date Format Validation', 'status' => 'PASS', 'value' => $validationRate];
    } elseif ($validationRate >= 80) {
        echo "   ⚠️  Priemerná validácia dátumov\n";
        $testResults[] = ['test' => 'Date Format Validation', 'status' => 'WARNING', 'value' => $validationRate];
    } else {
        echo "   ❌ Slabá validácia dátumov\n";
        $testResults[] = ['test' => 'Date Format Validation', 'status' => 'FAIL', 'value' => $validationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'Date Format Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Test 6: Validácia string dĺžky
echo "\n🔒 Test 6: Validácia string dĺžky\n";
echo "--------------------------------\n";

$totalTests++;
try {
    $testStrings = [
        // Valid strings (1-255 characters)
        'A' => true,
        'AB' => true,
        'ABC' => true,
        'ABCD' => true,
        'ABCDE' => true,
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ' => true,
        'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ' => true, // 250 chars
        
        // Invalid strings
        '' => false,
        'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZA' => false, // 251 chars
        'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZAB' => false, // 252 chars
        'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABC' => false, // 253 chars
        'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCD' => false, // 254 chars
        'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDE' => false, // 255 chars
        'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZABCDEF' => false, // 256 chars
        null => false,
        false => false,
        true => false,
        [] => false,
        (object)[] => false
    ];
    
    $validatedStrings = 0;
    $totalStrings = count($testStrings);
    
    echo "   📋 Testovanie {$totalStrings} string dĺžok:\n";
    
    foreach ($testStrings as $string => $expectedValid) {
        // String length validation function
        $isValid = validateStringLength($string);
        
        if ($isValid === $expectedValid) {
            $validatedStrings++;
            $statusIcon = $isValid ? '✅' : '❌';
            $length = is_string($string) ? strlen($string) : 'N/A';
            echo "   {$statusIcon} Length {$length}: " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        } else {
            $statusIcon = '⚠️';
            $length = is_string($string) ? strlen($string) : 'N/A';
            echo "   {$statusIcon} Length {$length}: Expected " . ($expectedValid ? 'Valid' : 'Invalid') . ", Got " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        }
    }
    
    $validationRate = round(($validatedStrings / $totalStrings) * 100, 1);
    echo "   📊 Validation rate: {$validatedStrings}/{$totalStrings} ({$validationRate}%)\n";
    
    if ($validationRate >= 95) {
        echo "   ✅ Vysoká validácia string dĺžky\n";
        $passedTests++;
        $testResults[] = ['test' => 'String Length Validation', 'status' => 'PASS', 'value' => $validationRate];
    } elseif ($validationRate >= 80) {
        echo "   ⚠️  Priemerná validácia string dĺžky\n";
        $testResults[] = ['test' => 'String Length Validation', 'status' => 'WARNING', 'value' => $validationRate];
    } else {
        echo "   ❌ Slabá validácia string dĺžky\n";
        $testResults[] = ['test' => 'String Length Validation', 'status' => 'FAIL', 'value' => $validationRate];
    }
    
} catch (Exception $e) {
    echo "   ❌ Chyba: " . $e->getMessage() . "\n";
    $testResults[] = ['test' => 'String Length Validation', 'status' => 'ERROR', 'value' => $e->getMessage()];
}

// Validation functions
function validateTicker($ticker) {
    if (!is_string($ticker)) return false;
    if (strlen($ticker) < 3 || strlen($ticker) > 4) return false;
    if (!preg_match('/^[A-Z]+$/', $ticker)) return false;
    return true;
}

function validateFiscalPeriod($period) {
    if (!is_string($period)) return false;
    $validPeriods = ['Q1', 'Q2', 'Q3', 'Q4', 'FY', 'H1', 'H2'];
    return in_array($period, $validPeriods);
}

function validateFiscalYear($year) {
    if (!is_int($year)) return false;
    return $year >= 2020 && $year <= 2030;
}

function validateNumeric($number) {
    if (!is_numeric($number)) return false;
    if (!is_finite($number)) return false;
    return true;
}

function validateDate($date) {
    if (!is_string($date)) return false;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    return $dateTime && $dateTime->format('Y-m-d') === $date;
}

function validateStringLength($string) {
    if (!is_string($string)) return false;
    $length = strlen($string);
    return $length >= 1 && $length <= 255;
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
    echo "🏆 VÝBORNE! Input validation je výborná!\n";
} elseif ($successRate >= 75) {
    echo "✅ DOBRE! Väčšina input validation testov prešla úspešne.\n";
} elseif ($successRate >= 50) {
    echo "⚠️  PRIJATEĽNÉ! Polovica input validation testov prešla úspešne.\n";
} else {
    echo "❌ PROBLEMATICKÉ! Mnoho input validation testov zlyhalo.\n";
}

echo "\n🎉 Test input validation dokončený!\n";
?>
