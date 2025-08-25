# 📝 Logging & Monitoring

## 📋 **Prehľad**

Logging & Monitoring je komplexný systém pre sledovanie bezpečnostných udalostí, výkonu aplikácie a audit trail. Poskytuje automatické alerting a podrobné štatistiky.

### **✅ Výhody:**

- **Bezpečnostné logy** - sledovanie všetkých prístupov a aktivít
- **Automatický alerting** - upozornenia pri podozrivej aktivite
- **Audit trail** - kompletný záznam zmien v systéme
- **Výkonnostné monitorovanie** - sledovanie pomalých operácií
- **Štatistiky a reporty** - prehľad aktivít a trendov

---

## 🚀 **Rýchle použitie**

### **1. Základné logovanie:**

```php
$logger = SecurityLogger::getInstance();

// Log udalosti
$logger->log('info', 'user_action', ['action' => 'login']);

// Log prihlásenie
$logger->logLogin('username', true);

// Log API volanie
$logger->logApiCall('/api/data', 'GET', 200, 150);
```

### **2. Audit trail:**

```php
$audit = new AuditTrail();

// Log zmenu dát
$audit->logDataChange('users', 'UPDATE', $oldData, $newData);

// Log zmenu konfigurácie
$audit->logConfigChange('debug_mode', 'false', 'true');
```

### **3. Výkonnostné monitorovanie:**

```php
$monitor = new PerformanceMonitor();
$monitor->start();

// Vykonaj operáciu
$result = performOperation();

$duration = $monitor->end('operation_name');
```

---

## 📁 **Štruktúra súborov**

```
config/
├── logger.php              # SecurityLogger, AuditTrail, PerformanceMonitor
└── config.php             # Hlavná konfigurácia

Tests/
└── test_logging_monitoring.php # Test logging & monitoring

scripts/
└── monitor_security.php   # Security monitoring script

logs/
└── security/              # Bezpečnostné logy
    ├── info.log           # Informačné udalosti
    ├── warning.log        # Varovania
    ├── error.log          # Chyby
    ├── critical.log       # Kritické udalosti
    └── alerts.log         # Alerty
```

---

## 🔧 **Konfigurácia**

### **Automatické načítanie:**

```php
require_once 'config/logger.php';

// Použitie
$logger = SecurityLogger::getInstance();
$audit = new AuditTrail();
$monitor = new PerformanceMonitor();
```

### **Alerting thresholdy:**

```php
// V SecurityLogger triede
private $alertThresholds = [
    'failed_login' => 5,      // 5 neúspešných prihlásení za 15 minút
    'api_abuse' => 100,       // 100 API volaní za minútu
    'sql_injection' => 1,     // 1 pokus o SQL injection
    'xss_attempt' => 1,       // 1 pokus o XSS
    'file_access' => 50,      // 50 prístupov k súborom za minútu
    'error_rate' => 20        // 20 chýb za 5 minút
];
```

---

## 🧪 **Testovanie**

### **Test logging & monitoring:**

```bash
make test-log

# Alebo priamo
php Tests/test_logging_monitoring.php
```

### **Security monitoring:**

```bash
make monitor-security

# Alebo priamo
php scripts/monitor_security.php
```

---

## 🔧 **Používanie v kóde**

### **1. SecurityLogger - Základné logovanie:**

#### **Log udalosti:**

```php
$logger = SecurityLogger::getInstance();

// Rôzne úrovne logovania
$logger->log('info', 'user_action', ['action' => 'view_page']);
$logger->log('warning', 'high_memory', ['memory' => '80MB']);
$logger->log('error', 'database_error', ['error' => 'Connection failed']);
$logger->log('critical', 'security_breach', ['ip' => '192.168.1.100']);
```

#### **Log prihlásenia:**

```php
// Úspešné prihlásenie
$logger->logLogin('john.doe', true);

// Neúspešné prihlásenie
$logger->logLogin('hacker', false);
```

#### **Log API volania:**

```php
$startTime = microtime(true);

// Vykonaj API volanie
$response = makeApiCall('/api/data');

$duration = (microtime(true) - $startTime) * 1000;
$logger->logApiCall('/api/data', 'GET', $response->getStatusCode(), $duration);
```

#### **Log bezpečnostné pokusy:**

```php
// SQL injection pokus
$logger->logSqlInjection($sql, $params);

// XSS pokus
$logger->logXssAttempt($maliciousInput);

// Prístup k súborom
$logger->logFileAccess('config.php', 'read');
$logger->logFileAccess('config.php', 'write');
```

#### **Log databázové zmeny:**

```php
$logger->logDatabaseChange('INSERT', 'users', 1);
$logger->logDatabaseChange('UPDATE', 'users', 5);
$logger->logDatabaseChange('DELETE', 'users', 10);
```

#### **Log chyby:**

```php
try {
    // Kód, ktorý môže zlyhať
} catch (Exception $e) {
    $logger->logError($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
```

### **2. AuditTrail - Sledovanie zmien:**

#### **Zmeny dát:**

```php
$audit = new AuditTrail();

// Pred zmenou
$oldData = ['name' => 'John', 'email' => 'john@old.com'];

// Po zmene
$newData = ['name' => 'John', 'email' => 'john@new.com'];

$audit->logDataChange('users', 'UPDATE', $oldData, $newData, $userId);
```

#### **Zmeny konfigurácie:**

```php
$audit->logConfigChange('debug_mode', 'false', 'true', $adminId);
$audit->logConfigChange('api_rate_limit', '100', '200', $adminId);
```

#### **Prístup k citlivým dátam:**

```php
$audit->logSensitiveDataAccess('user_data', $userId, $accessingUserId);
$audit->logSensitiveDataAccess('financial_data', $recordId, $userId);
```

### **3. PerformanceMonitor - Sledovanie výkonu:**

#### **Meranie času:**

```php
$monitor = new PerformanceMonitor();

$monitor->start();

// Vykonaj operáciu
$result = performComplexOperation();

$duration = $monitor->end('complex_operation');

if ($duration > 1000) {
    echo "Operácia trvala " . round($duration, 2) . "ms";
}
```

#### **Sledovanie pamäte:**

```php
$monitor->logMemoryUsage('data_processing');

// Alebo manuálne
$memory = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);

if ($memory > 50 * 1024 * 1024) { // 50MB
    $logger->log('warning', 'high_memory_usage', [
        'memory' => $memory,
        'peak_memory' => $peakMemory
    ]);
}
```

### **4. Štatistiky a reporty:**

#### **Získanie štatistík:**

```php
$logger = SecurityLogger::getInstance();

// Štatistiky za posledných 24 hodín
$stats = $logger->getStats(24);

echo "Celkových udalostí: " . $stats['total_events'];
echo "Alertov: " . $stats['alerts'];

// Štatistiky podľa úrovne
foreach ($stats['by_level'] as $level => $count) {
    echo "$level: $count";
}

// Štatistiky podľa udalosti
foreach ($stats['by_event'] as $event => $count) {
    echo "$event: $count";
}

// Top IP adresy
foreach ($stats['by_ip'] as $ip => $count) {
    echo "$ip: $count udalostí";
}
```

#### **Cleanup starých logov:**

```php
// Vyčisti logy staršie ako 30 dní
$logger->cleanup(30);
```

---

## 📊 **Monitoring a reporty**

### **Security monitoring script:**

```bash
# Základný report
php scripts/monitor_security.php

# JSON export
php scripts/monitor_security.php --json
```

### **Príklad výstupu:**

```
🔒 Security Monitoring Report
============================

📊 Štatistiky za posledných 24 hodín:
   Celkových udalostí: 1,234
   Alertov: 3

📈 Štatistiky podľa úrovne:
   ℹ️ info: 1,100
   ⚠️ warning: 120
   ❌ error: 10
   🚨 critical: 4

🎯 Štatistiky podľa udalosti:
   🔐 login_failed: 15
   💉 sql_injection_attempt: 2
   🕷️ xss_attempt: 1
   🌐 api_call: 1,200
   📁 file_access: 50
   🗄️ database_change: 25
   💥 application_error: 5

🌍 Top IP adresy:
   192.168.1.100: 500 udalostí - 🚨 VYSOKÁ AKTIVITA
   10.0.0.1: 200 udalostí - ⚠️ STREDNÁ AKTIVITA
   172.16.0.1: 50 udalostí - 🔶 NORMÁLNA AKTIVITA
```

---

## 🚨 **Alerting systém**

### **Automatické alerty:**

- **Neúspešné prihlásenia** - viac ako 5 za 15 minút
- **API abuse** - viac ako 100 volaní za minútu
- **SQL injection pokusy** - každý pokus
- **XSS pokusy** - každý pokus
- **Vysoké využitie súborov** - viac ako 50 prístupov za minútu
- **Vysoká chybovosť** - viac ako 20 chýb za 5 minút

### **Alert súbory:**

```php
// Alerty sa ukladajú do
logs/security/alerts.log

// Príklad alert záznamu:
{
    "timestamp": "2024-01-15 10:30:00",
    "event": "login_failed",
    "ip": "192.168.1.100",
    "count": 6,
    "threshold": 5,
    "message": "Alert: login_failed from IP 192.168.1.100 (6/5)"
}
```

---

## 🔍 **Audit Trail**

### **Sledované zmeny:**

- **Zmeny dát** - INSERT, UPDATE, DELETE operácie
- **Zmeny konfigurácie** - nastavenia aplikácie
- **Prístup k citlivým dátam** - finančné údaje, osobné údaje
- **Bezpečnostné udalosti** - prihlásenia, pokusy o útok

### **Audit záznamy:**

```php
// Príklad audit záznamu:
{
    "timestamp": "2024-01-15 10:30:00",
    "level": "info",
    "event": "data_change",
    "ip": "192.168.1.100",
    "session_id": "abc123",
    "user_agent": "Mozilla/5.0...",
    "data": {
        "table": "users",
        "operation": "UPDATE",
        "old_data": {"name": "John"},
        "new_data": {"name": "John Doe"},
        "user_id": 123
    }
}
```

---

## ⚡ **Výkonnostné monitorovanie**

### **Sledované metriky:**

- **Čas vykonania** - operácie dlhšie ako 1 sekunda
- **Pamäťové využitie** - viac ako 50MB
- **API response time** - pomalé API volania
- **Databázové query time** - pomalé databázové operácie

### **Performance záznamy:**

```php
// Príklad performance záznamu:
{
    "timestamp": "2024-01-15 10:30:00",
    "level": "warning",
    "event": "slow_operation",
    "ip": "192.168.1.100",
    "data": {
        "operation": "complex_calculation",
        "duration": 1250.5
    }
}
```

---

## 📞 **Troubleshooting**

### **Časté problémy:**

#### **1. Log súbory sa nevytvárajú:**

```bash
# Riešenie: Skontroluj oprávnenia
chmod 755 logs/security/
chmod 644 logs/security/*.log
```

#### **2. Alerty sa neposielajú:**

```bash
# Riešenie: Skontroluj alert súbory
tail -f logs/security/alerts.log
```

#### **3. Vysoké využitie disku:**

```bash
# Riešenie: Spusti cleanup
php -r "require 'config/logger.php'; SecurityLogger::getInstance()->cleanup(7);"
```

#### **4. Pomalé logovanie:**

```bash
# Riešenie: Skontroluj I/O operácie
iostat -x 1
```

---

## 🎯 **Best Practices**

### **✅ Odporúčania:**

1. **Loguj všetky bezpečnostné udalosti** - prihlásenia, pokusy o útok
2. **Používaj audit trail** pre všetky zmeny dát
3. **Monitoruj výkon** pravidelne
4. **Nastav alerting** pre kritické udalosti
5. **Pravidelne čisti staré logy** - automaticky
6. **Backupuj log súbory** - pre audit účely
7. **Analyzuj trendy** - týždenné/mesačné reporty

### **❌ Čo sa vyhnúť:**

1. **Logovanie citlivých údajov** - heslá, API kľúče
2. **Príliš detailné logovanie** - môže spomaliť aplikáciu
3. **Ignorovanie alertov** - vždy reaguj na kritické udalosti
4. **Necachovanie logov** - pre vysokú frekvenciu udalostí
5. **Nemonitorovanie výkonu** - môže viesť k problémom

---

## 🔒 **Bezpečnostné aspekty**

### **Ochrana log súborov:**

- **Oprávnenia** - len aplikácia môže zapisovať
- **Šifrovanie** - citlivé logy by mali byť šifrované
- **Rotácia** - automatické rotovanie log súborov
- **Backup** - bezpečné zálohovanie logov

### **Ochrana pred útokmi:**

- **Log injection** - validácia všetkých vstupov
- **Log tampering** - integrity checks
- **DoS cez logovanie** - rate limiting pre logovanie
- **Information disclosure** - neukazuj citlivé údaje v logoch

---

## 🎉 **Záver**

Logging & Monitoring zabezpečuje:

- **Bezpečnosť** - sledovanie všetkých aktivít
- **Transparentnosť** - kompletný audit trail
- **Výkon** - monitorovanie a optimalizácia
- **Alerting** - včasné upozornenia
- **Compliance** - splnenie bezpečnostných požiadaviek

**Teraz máš kompletný prehľad o všetkých aktivitách v aplikácii!** 📝
