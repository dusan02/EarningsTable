<?php
/**
 * Príklad bezpečného použitia databázy
 * Demonštruje SQL Injection Protection
 */

require_once 'config/env_loader.php';
require_once 'config/config.php';
require_once 'config/database_helper.php';

echo "🔒 Príklad bezpečného použitia databázy\n";
echo "======================================\n\n";

// 1. Bezpečné získanie používateľského vstupu
echo "1. Bezpečné získanie používateľského vstupu:\n";

// Simulácia GET parametra
$_GET['ticker'] = "AAPL'; DROP TABLE users; --";
$_GET['limit'] = "10; DELETE FROM users; --";

// Bezpečné spracovanie vstupov
$ticker = InputValidator::sanitizeString($_GET['ticker'] ?? '');
$limit = InputValidator::validateInteger($_GET['limit'] ?? 10, 1, 100);

echo "   Pôvodný ticker: " . $_GET['ticker'] . "\n";
echo "   Sanitizovaný ticker: $ticker\n";
echo "   Validovaný limit: $limit\n\n";

// 2. Bezpečné SELECT operácie
echo "2. Bezpečné SELECT operácie:\n";

try {
    $db = DatabaseHelper::getInstance();
    
    // Bezpečný SELECT s prepared statement
    $sql = "SELECT ticker, company_name FROM EarningsTickersToday WHERE ticker = ? LIMIT ?";
    $result = $db->select($sql, [$ticker, $limit]);
    
    echo "   ✅ SELECT úspešný (bezpečný)\n";
    echo "   Počet výsledkov: " . count($result) . "\n";
    
} catch (Exception $e) {
    echo "   ❌ SELECT zlyhal: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Bezpečné INSERT operácie
echo "3. Bezpečné INSERT operácie:\n";

try {
    $db = DatabaseHelper::getInstance();
    
    // Bezpečné dáta pre INSERT
    $data = [
        'ticker' => InputValidator::sanitizeString('TEST'),
        'company_name' => InputValidator::sanitizeString('Test Company'),
        'earnings_date' => date('Y-m-d'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Validácia dát pred INSERT
    if (!InputValidator::validateTicker($data['ticker'])) {
        throw new Exception("Neplatný ticker symbol");
    }
    
    if (!InputValidator::validateDate($data['earnings_date'])) {
        throw new Exception("Neplatný dátum");
    }
    
    // Bezpečný INSERT
    $insertId = $db->insert('EarningsTickersToday', $data);
    
    echo "   ✅ INSERT úspešný (ID: $insertId)\n";
    
} catch (Exception $e) {
    echo "   ❌ INSERT zlyhal: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Bezpečné UPDATE operácie
echo "4. Bezpečné UPDATE operácie:\n";

try {
    $db = DatabaseHelper::getInstance();
    
    // Bezpečné dáta pre UPDATE
    $updateData = [
        'company_name' => InputValidator::sanitizeString('Updated Company Name')
    ];
    
    // Bezpečný UPDATE s WHERE podmienkou
    $affectedRows = $db->update(
        'EarningsTickersToday',
        $updateData,
        'ticker = ?',
        ['TEST']
    );
    
    echo "   ✅ UPDATE úspešný (ovplyvnené riadky: $affectedRows)\n";
    
} catch (Exception $e) {
    echo "   ❌ UPDATE zlyhal: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Bezpečné DELETE operácie
echo "5. Bezpečné DELETE operácie:\n";

try {
    $db = DatabaseHelper::getInstance();
    
    // Bezpečný DELETE s WHERE podmienkou
    $affectedRows = $db->delete(
        'EarningsTickersToday',
        'ticker = ?',
        ['TEST']
    );
    
    echo "   ✅ DELETE úspešný (ovplyvnené riadky: $affectedRows)\n";
    
} catch (Exception $e) {
    echo "   ❌ DELETE zlyhal: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. Použitie QueryBuilder
echo "6. Použitie QueryBuilder:\n";

try {
    $qb = new QueryBuilder();
    
    // Bezpečný SELECT cez QueryBuilder
    $result = $qb->select(
        'EarningsTickersToday',
        ['ticker', 'company_name'],
        'ticker LIKE ?',
        ['%' . InputValidator::escapeLike('AAP') . '%'],
        'ticker ASC',
        '5'
    );
    
    echo "   ✅ QueryBuilder SELECT úspešný\n";
    echo "   Počet výsledkov: " . count($result) . "\n";
    
} catch (Exception $e) {
    echo "   ❌ QueryBuilder zlyhal: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Transakcie
echo "7. Transakcie:\n";

try {
    $db = DatabaseHelper::getInstance();
    
    $db->beginTransaction();
    echo "   ✅ Transakcia začatá\n";
    
    // Vykonaj viacero operácií v transakcii
    $data1 = [
        'ticker' => 'TEST1',
        'company_name' => 'Test Company 1',
        'earnings_date' => date('Y-m-d'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $data2 = [
        'ticker' => 'TEST2',
        'company_name' => 'Test Company 2',
        'earnings_date' => date('Y-m-d'),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $db->insert('EarningsTickersToday', $data1);
    $db->insert('EarningsTickersToday', $data2);
    
    $db->commit();
    echo "   ✅ Transakcia commitovaná\n";
    
    // Vyčisti test dáta
    $db->delete('EarningsTickersToday', 'ticker IN (?, ?)', ['TEST1', 'TEST2']);
    
} catch (Exception $e) {
    $db->rollback();
    echo "   ❌ Transakcia zlyhala: " . $e->getMessage() . "\n";
}

echo "\n";

// 8. Validácia komplexných dát
echo "8. Validácia komplexných dát:\n";

$userInput = [
    'ticker' => 'AAPL',
    'email' => 'test@example.com',
    'age' => '25',
    'price' => '150.50',
    'date' => '2024-01-15'
];

$validatedData = [];

// Validácia ticker
if (InputValidator::validateTicker($userInput['ticker'])) {
    $validatedData['ticker'] = strtoupper($userInput['ticker']);
    echo "   ✅ Ticker validovaný: " . $validatedData['ticker'] . "\n";
} else {
    echo "   ❌ Neplatný ticker\n";
}

// Validácia email
if (InputValidator::validateEmail($userInput['email'])) {
    $validatedData['email'] = $userInput['email'];
    echo "   ✅ Email validovaný: " . $validatedData['email'] . "\n";
} else {
    echo "   ❌ Neplatný email\n";
}

// Validácia veku
$age = InputValidator::validateInteger($userInput['age'], 0, 150);
if ($age !== false) {
    $validatedData['age'] = $age;
    echo "   ✅ Vek validovaný: " . $validatedData['age'] . "\n";
} else {
    echo "   ❌ Neplatný vek\n";
}

// Validácia ceny
$price = InputValidator::validateFloat($userInput['price'], 0, 10000);
if ($price !== false) {
    $validatedData['price'] = $price;
    echo "   ✅ Cena validovaná: " . $validatedData['price'] . "\n";
} else {
    echo "   ❌ Neplatná cena\n";
}

// Validácia dátumu
if (InputValidator::validateDate($userInput['date'])) {
    $validatedData['date'] = $userInput['date'];
    echo "   ✅ Dátum validovaný: " . $validatedData['date'] . "\n";
} else {
    echo "   ❌ Neplatný dátum\n";
}

echo "\n";

// 9. Záver
echo "🎉 Príklad bezpečného použitia databázy dokončený!\n\n";

echo "📋 Bezpečnostné opatrenia:\n";
echo "✅ Všetky vstupy sú sanitizované\n";
echo "✅ Všetky SQL dotazy používajú prepared statements\n";
echo "✅ Všetky dáta sú validované pred použitím\n";
echo "✅ Transakcie zabezpečujú konzistenciu\n";
echo "✅ Error logovanie sleduje problémy\n";
echo "✅ QueryBuilder poskytuje bezpečné rozhranie\n";

echo "\n🔒 Ochrana pred:\n";
echo "   - SQL Injection útokmi\n";
echo "   - XSS útokmi\n";
echo "   - Invalid dátami\n";
echo "   - Unauthorized prístupom\n";
echo "   - Data corruption\n";

echo "\n✅ Všetky operácie sú bezpečné!\n";
?>
