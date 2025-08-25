# 🔍 SQL Injection Protection

## 📋 **Prehľad**

SQL Injection Protection je kľúčové bezpečnostné vylepšenie, ktoré chráni databázu pred najčastejšími útokmi. Používa prepared statements, validáciu vstupov a sanitizáciu dát.

### **✅ Výhody:**

- **Ochrana pred SQL Injection** - prepared statements blokujú útoky
- **Validácia vstupov** - všetky dáta sú kontrolované
- **Sanitizácia dát** - odstránenie nebezpečných znakov
- **Transakcie** - zabezpečenie konzistencie dát
- **Error logovanie** - sledovanie pokusov o útok

---

## 🚀 **Rýchle použitie**

### **1. Základné použitie:**

```php
// Bezpečné SELECT
$db = DatabaseHelper::getInstance();
$result = $db->select("SELECT * FROM users WHERE id = ?", [$userId]);

// Bezpečný INSERT
$data = ['name' => 'John', 'email' => 'john@example.com'];
$insertId = $db->insert('users', $data);

// Bezpečný UPDATE
$db->update('users', ['name' => 'Jane'], 'id = ?', [$userId]);
```

### **2. Validácia vstupov:**

```php
// Sanitizácia string
$cleanInput = InputValidator::sanitizeString($_POST['name']);

// Validácia email
if (InputValidator::validateEmail($_POST['email'])) {
    // Email je platný
}

// Validácia ticker
if (InputValidator::validateTicker($_GET['ticker'])) {
    // Ticker je platný
}
```

---

## 📁 **Štruktúra súborov**

```
config/
├── database_helper.php    # DatabaseHelper, InputValidator, QueryBuilder
└── config.php            # Hlavná konfigurácia

Tests/
└── test_sql_injection.php # Test SQL injection protection

examples/
└── safe_database_usage.php # Príklad bezpečného použitia

logs/
└── database_errors.log    # Log databázových chýb
```

---

## 🔧 **Konfigurácia**

### **Automatické načítanie:**

```php
require_once 'config/database_helper.php';

// Použitie
$db = DatabaseHelper::getInstance();
$validator = new InputValidator();
```

### **PDO nastavenia:**

```php
// V config.php sú už nastavené bezpečné PDO opcie
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false, // Dôležité pre bezpečnosť
]);
```

---

## 🧪 **Testovanie**

### **Test SQL injection protection:**

```bash
make test-sql

# Alebo priamo
php Tests/test_sql_injection.php
```

### **Príklad použitia:**

```bash
php examples/safe_database_usage.php
```

---

## 🔧 **Používanie v kóde**

### **1. DatabaseHelper - Základné operácie:**

#### **SELECT:**

```php
$db = DatabaseHelper::getInstance();

// Jednoduchý SELECT
$result = $db->select("SELECT * FROM EarningsTickersToday");

// SELECT s parametrami
$result = $db->select(
    "SELECT * FROM EarningsTickersToday WHERE ticker = ? AND earnings_date = ?",
    ['AAPL', '2024-01-15']
);
```

#### **INSERT:**

```php
$data = [
    'ticker' => 'AAPL',
    'company_name' => 'Apple Inc.',
    'earnings_date' => '2024-01-15',
    'created_at' => date('Y-m-d H:i:s')
];

$insertId = $db->insert('EarningsTickersToday', $data);
```

#### **UPDATE:**

```php
$updateData = ['company_name' => 'Apple Inc. Updated'];
$affectedRows = $db->update(
    'EarningsTickersToday',
    $updateData,
    'ticker = ?',
    ['AAPL']
);
```

#### **DELETE:**

```php
$affectedRows = $db->delete(
    'EarningsTickersToday',
    'ticker = ? AND earnings_date < ?',
    ['OLD', '2023-01-01']
);
```

### **2. InputValidator - Validácia vstupov:**

#### **String sanitizácia:**

```php
$userInput = "<script>alert('xss')</script>";
$cleanInput = InputValidator::sanitizeString($userInput);
// Výsledok: &lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;
```

#### **Email validácia:**

```php
$email = "user@example.com";
if (InputValidator::validateEmail($email)) {
    // Email je platný
}
```

#### **Integer validácia:**

```php
$age = InputValidator::validateInteger($_POST['age'], 0, 150);
if ($age !== false) {
    // Vek je platný a v rozsahu 0-150
}
```

#### **Float validácia:**

```php
$price = InputValidator::validateFloat($_POST['price'], 0, 10000);
if ($price !== false) {
    // Cena je platná a v rozsahu 0-10000
}
```

#### **Date validácia:**

```php
$date = "2024-01-15";
if (InputValidator::validateDate($date)) {
    // Dátum je platný
}
```

#### **Ticker validácia:**

```php
$ticker = "AAPL";
if (InputValidator::validateTicker($ticker)) {
    // Ticker je platný (1-10 znakov, len písmená a čísla)
}
```

#### **LIKE escape:**

```php
$searchTerm = "test%_";
$escaped = InputValidator::escapeLike($searchTerm);
// Výsledok: "test\%\_"
```

### **3. QueryBuilder - Pokročilé operácie:**

#### **Komplexný SELECT:**

```php
$qb = new QueryBuilder();

$result = $qb->select(
    'EarningsTickersToday',
    ['ticker', 'company_name', 'earnings_date'],
    'ticker LIKE ? AND earnings_date >= ?',
    ['%' . InputValidator::escapeLike('AAP') . '%', '2024-01-01'],
    'earnings_date ASC',
    '10'
);
```

#### **COUNT:**

```php
$count = $qb->count(
    'EarningsTickersToday',
    'earnings_date = ?',
    ['2024-01-15']
);
```

#### **EXISTS:**

```php
$exists = $qb->exists(
    'EarningsTickersToday',
    'ticker = ?',
    ['AAPL']
);
```

### **4. Transakcie:**

#### **Základné transakcie:**

```php
$db = DatabaseHelper::getInstance();

try {
    $db->beginTransaction();

    // Vykonaj viacero operácií
    $db->insert('table1', $data1);
    $db->insert('table2', $data2);

    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

#### **Komplexné transakcie:**

```php
$db = DatabaseHelper::getInstance();

try {
    $db->beginTransaction();

    // Kontrola existencie
    if (!$db->exists('users', 'email = ?', [$email])) {
        // Vytvor používateľa
        $userId = $db->insert('users', $userData);

        // Vytvor profil
        $profileData['user_id'] = $userId;
        $db->insert('profiles', $profileData);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    error_log("Transaction failed: " . $e->getMessage());
}
```

---

## 📊 **Monitoring a logovanie**

### **Error logovanie:**

```php
// Automaticky sa logujú všetky databázové chyby
$logFile = 'logs/database_errors.log';

// Príklad log záznamu:
{
    "timestamp": "2024-01-15 10:30:00",
    "operation": "SELECT",
    "sql": "SELECT * FROM users WHERE id = ?",
    "params": ["123"],
    "error": "Table 'users' doesn't exist",
    "ip": "192.168.1.100"
}
```

### **Monitoring pokusov o útok:**

```bash
# Sledovanie log súboru
tail -f logs/database_errors.log

# Hľadanie podozrivých aktivít
grep "SQL syntax" logs/database_errors.log
```

---

## 🚨 **Bezpečnostné opatrenia**

### **Prepared Statements:**

- **Všetky SQL dotazy** používajú prepared statements
- **Žiadne string concatenation** v SQL dotazoch
- **Automatické escaping** všetkých parametrov

### **Input Validation:**

- **Všetky vstupy** sú validované pred použitím
- **Sanitizácia** odstraňuje nebezpečné znaky
- **Type checking** zabezpečuje správne typy dát

### **Error Handling:**

- **Žiadne SQL chyby** sa nezobrazujú používateľom
- **Logovanie** všetkých chýb pre audit
- **Graceful degradation** pri chybách

### **Access Control:**

- **Principle of least privilege** pre databázové účty
- **Read-only accounts** pre SELECT operácie
- **Separate accounts** pre rôzne typy operácií

---

## 📞 **Troubleshooting**

### **Časté problémy:**

#### **1. "Table doesn't exist" chyba:**

```bash
# Riešenie: Skontroluj názov tabuľky
php Tests/test_sql_injection.php
```

#### **2. "Invalid parameter" chyba:**

```bash
# Riešenie: Skontroluj validáciu vstupov
$cleanInput = InputValidator::sanitizeString($userInput);
```

#### **3. "Transaction failed" chyba:**

```bash
# Riešenie: Skontroluj logy
tail -f logs/database_errors.log
```

#### **4. "Prepared statement failed" chyba:**

```bash
# Riešenie: Skontroluj SQL syntax
$sql = "SELECT * FROM table WHERE id = ?"; // Správne
$sql = "SELECT * FROM table WHERE id = $id"; // Nesprávne!
```

---

## 🎯 **Best Practices**

### **✅ Odporúčania:**

1. **Vždy používaj prepared statements**
2. **Validuj všetky vstupy** pred použitím
3. **Sanitizuj dáta** pred uložením
4. **Používaj transakcie** pre komplexné operácie
5. **Loguj všetky chyby** pre audit
6. **Používaj QueryBuilder** pre komplexné dotazy
7. **Testuj pravidelne** SQL injection protection

### **❌ Čo sa vyhnúť:**

1. **String concatenation** v SQL dotazoch
2. **Nepoužívanie prepared statements**
3. **Ignorovanie validácie vstupov**
4. **Zobrazovanie SQL chýb** používateľom
5. **Nepoužívanie transakcií** pre viacero operácií
6. **Necachovanie** často používaných dotazov

---

## 🔒 **Bezpečnostné testy**

### **Automatické testy:**

```bash
# Spustenie všetkých bezpečnostných testov
make test-sql
```

### **Manuálne testy:**

```php
// Test malicious input
$maliciousInput = "'; DROP TABLE users; --";
$result = $db->select("SELECT ? as test", [$maliciousInput]);
// Toto by malo byť bezpečné
```

### **Penetration testing:**

```php
// Test rôznych attack vectors
$attackVectors = [
    "'; DROP TABLE users; --",
    "' OR '1'='1",
    "' UNION SELECT * FROM users --",
    "'; INSERT INTO users VALUES ('hacker', 'password'); --"
];

foreach ($attackVectors as $vector) {
    try {
        $result = $db->select("SELECT ? as test", [$vector]);
        echo "Vector '$vector' je bezpečný\n";
    } catch (Exception $e) {
        echo "Vector '$vector' zlyhal: " . $e->getMessage() . "\n";
    }
}
```

---

## 🎉 **Záver**

SQL Injection Protection zabezpečuje:

- **Bezpečnosť** pred SQL injection útokmi
- **Integritu** dát v databáze
- **Konzistenciu** operácií
- **Audit** všetkých aktivít
- **Robustnosť** aplikácie

**Teraz je tvoja databáza chránená pred SQL injection útokmi!** 🔍
