# 🚦 API Rate Limiting

## 📋 **Prehľad**

API Rate Limiting je bezpečnostný mechanizmus, ktorý obmedzuje počet API volaní za určitý čas. Chráni pred nadmerným používaním API, DDoS útokmi a prekročením API limitov.

### **✅ Výhody:**
- **Ochrana pred DDoS** - blokuje nadmerné volania
- **Respektovanie API limitov** - neprekračuje limity poskytovateľov
- **Kontrola nákladov** - obmedzuje API náklady
- **Zabezpečenie stability** - zachováva výkon aplikácie

---

## 🚀 **Rýchle použitie**

### **1. Základné použitie:**
```php
// Vytvorenie API wrapper
$polygonApi = ApiFactory::create('polygon');

// Bezpečné API volanie
try {
    $response = $polygonApi->call('https://api.polygon.io/v2/...');
    echo "API volanie úspešné!";
} catch (Exception $e) {
    echo "Chyba: " . $e->getMessage();
}
```

### **2. Kontrola limitov:**
```php
// Získanie štatistík
$stats = $polygonApi->getStats();
echo "Zostávajúce volania: " . $stats['remaining'];

// Kontrola, či môže pokračovať
if ($polygonApi->getRemaining() > 0) {
    // Môžeš vykonať API volanie
}
```

---

## 📁 **Štruktúra súborov**

```
config/
├── rate_limiter.php      # Rate limiter trieda
├── api_wrapper.php       # API wrapper s rate limiting
└── env_loader.php        # Environment loader

scripts/
└── monitor_api_limits.php # Monitoring script

Tests/
└── test_rate_limiting.php # Test rate limiting

storage/
└── rate_limits/          # Úložisko rate limitov
    ├── polygon_default.json
    ├── finnhub_default.json
    └── ...

logs/
└── api_calls.log         # Log API volaní
```

---

## 🔧 **Konfigurácia**

### **Environment premenné:**
```env
# Všeobecné limity
API_RATE_LIMIT=100
API_RATE_WINDOW=60

# Polygon API
POLYGON_RATE_LIMIT=100
POLYGON_RATE_WINDOW=60

# Finnhub API
FINNHUB_RATE_LIMIT=60
FINNHUB_RATE_WINDOW=60

# Yahoo API
YAHOO_RATE_LIMIT=100
YAHOO_RATE_WINDOW=60
```

### **Význam premenných:**
- **RATE_LIMIT** - Počet povolených volaní
- **RATE_WINDOW** - Časové okno v sekundách
- **Príklad:** 100 volaní za 60 sekúnd = 100 volaní za minútu

---

## 🧪 **Testovanie**

### **Test rate limiting:**
```bash
make test-rate

# Alebo priamo
php Tests/test_rate_limiting.php
```

### **Monitor API limitov:**
```bash
make monitor-api

# Alebo priamo
php scripts/monitor_api_limits.php
```

### **JSON export:**
```bash
php scripts/monitor_api_limits.php --json
```

---

## 🔧 **Používanie v kóde**

### **1. Základné API volanie:**
```php
require_once 'config/api_wrapper.php';

// Vytvorenie API wrapper
$polygonApi = ApiFactory::create('polygon');

// Bezpečné volanie
try {
    $response = $polygonApi->call('https://api.polygon.io/v2/...');
    $data = json_decode($response['data'], true);
} catch (Exception $e) {
    // Rate limit prekročený alebo iná chyba
    error_log("API Error: " . $e->getMessage());
}
```

### **2. Polygon API:**
```php
$polygonApi = new PolygonApiWrapper();

// Real-time quotes
$quote = $polygonApi->getQuote('AAPL');

// Company data
$company = $polygonApi->getCompany('AAPL');

// Earnings data
$earnings = $polygonApi->getEarnings('AAPL');
```

### **3. Finnhub API:**
```php
$finnhubApi = new FinnhubApiWrapper();

// Earnings calendar
$calendar = $finnhubApi->getEarningsCalendar();

// Company profile
$profile = $finnhubApi->getCompanyProfile('AAPL');

// Quote
$quote = $finnhubApi->getQuote('AAPL');
```

### **4. Rôzne identifikátory:**
```php
// Rôzne limity pre rôznych používateľov
$user1Limiter = RateLimiterManager::getLimiter('polygon', 'user1');
$user2Limiter = RateLimiterManager::getLimiter('polygon', 'user2');

if ($user1Limiter->canProceed()) {
    // User1 môže vykonať volanie
}
```

---

## 📊 **Monitoring a štatistiky**

### **Získanie štatistík:**
```php
// Štatistiky pre konkrétne API
$stats = $polygonApi->getStats();
echo "Aktuálne: {$stats['current']}/{$stats['limit']}";
echo "Zostávajúce: {$stats['remaining']}";
echo "Reset za: {$stats['reset_time']} sekúnd";

// Všetky API štatistiky
$allStats = ApiFactory::getAllStats();
foreach ($allStats as $api => $stats) {
    echo "$api: {$stats['current']}/{$stats['limit']}";
}
```

### **Monitoring report:**
```bash
php scripts/monitor_api_limits.php
```

**Výstup:**
```
📊 API Limits Monitor
====================

📈 polygon_default:
   Aktuálne volania: 45/100
   Zostávajúce: 55
   Reset za: 23 sekúnd
   ✅ STAV: Normálny (45.0%)

📈 finnhub_default:
   Aktuálne volania: 58/60
   Zostávajúce: 2
   Reset za: 45 sekúnd
   ⚠️ STAV: Vysoký (96.7%)
```

---

## 🚨 **Alerting a notifikácie**

### **Automatické alerting:**
```php
// Kontrola kritického stavu
$stats = $polygonApi->getStats();
$usagePercent = ($stats['current'] / $stats['limit']) * 100;

if ($usagePercent >= 90) {
    // Kritický stav - pošli alert
    sendAlert("Kritické využitie Polygon API: " . round($usagePercent, 1) . "%");
} elseif ($usagePercent >= 75) {
    // Vysoké využitie - upozornenie
    sendWarning("Vysoké využitie Polygon API: " . round($usagePercent, 1) . "%");
}
```

### **Cron job pre monitoring:**
```bash
# Každých 5 minút kontroluj API limity
*/5 * * * * php /path/to/scripts/monitor_api_limits.php
```

---

## 🔒 **Bezpečnosť**

### **DDoS ochrana:**
- **Rate limiting** blokuje nadmerné volania
- **Rôzne limity** pre rôzne API
- **Časové okná** zabraňujú spamovaniu
- **Logovanie** všetkých API volaní

### **API kľúče ochrana:**
- **Environment premenné** pre API kľúče
- **Bezpečné volania** cez wrapper
- **Error handling** pre chybné volania
- **Monitoring** pre podozrivú aktivitu

---

## 📞 **Troubleshooting**

### **Časté problémy:**

#### **1. Rate limit exceeded:**
```bash
# Riešenie: Skontroluj limity
php scripts/monitor_api_limits.php
```

#### **2. API volania zlyhávajú:**
```bash
# Riešenie: Skontroluj logy
tail -f logs/api_calls.log
```

#### **3. Vysoké využitie API:**
```bash
# Riešenie: Zvýš limity v .env
POLYGON_RATE_LIMIT=200
```

#### **4. Pomalé API volania:**
```bash
# Riešenie: Skontroluj rate limiting
php Tests/test_rate_limiting.php
```

---

## 🎯 **Best Practices**

### **✅ Odporúčania:**
1. **Nastav realistické limity** podľa API poskytovateľa
2. **Monitoruj pravidelne** API využitie
3. **Implementuj caching** pre často používané dáta
4. **Používaj rôzne identifikátory** pre rôznych používateľov
5. **Loguj všetky API volania** pre audit

### **❌ Čo sa vyhnúť:**
1. **Nastavovanie príliš vysokých limitov**
2. **Ignorovanie API chýb**
3. **Nepoužívanie rate limiting**
4. **Nemonitorovanie API využitia**
5. **Necachovanie často používaných dát**

---

## 🎉 **Záver**

API Rate Limiting zabezpečuje:
- **Bezpečnosť** pred DDoS útokmi
- **Stabilitu** aplikácie
- **Kontrolu nákladov** na API
- **Respektovanie** API limitov poskytovateľov

**Teraz sú tvoje API volania bezpečné a kontrolované!** 🚦
