# 🔐 Environment Variables Setup

## 📋 **Prehľad**

Environment Variables (premenné prostredia) sú bezpečný spôsob, ako ukladať citlivé údaje ako API kľúče, databázové hesla a konfiguračné nastavenia.

### **✅ Výhody:**

- **Bezpečnosť** - citlivé údaje nie sú v kóde
- **Flexibilita** - rôzne nastavenia pre rôzne prostredia
- **Jednoduchosť** - ľahká zmena nastavení bez úpravy kódu

---

## 🚀 **Rýchle nastavenie**

### **1. Automatické nastavenie:**

```bash
# Spustenie setup scriptu
make setup-env

# Alebo priamo
php scripts/setup_env.php
```

### **2. Manuálne nastavenie:**

```bash
# Skopíruj env.example na .env
cp env.example .env

# Uprav .env súbor podľa potreby
nano .env
```

---

## 📁 **Štruktúra súborov**

```
config/
├── config.php          # Hlavná konfigurácia (načítava z .env)
├── env_loader.php      # Loader pre .env súbory
└── production.env      # Produkčné nastavenia

scripts/
└── setup_env.php       # Setup script

Tests/
└── test_env.php        # Test environment premenných

.env                    # Lokálne nastavenia (NIKDY necommitovať!)
env.example             # Príklad nastavení
```

---

## 🔧 **Konfigurácia**

### **🗄️ Database:**

```env
DB_HOST=localhost
DB_NAME=earnings_db
DB_USER=root
DB_PASS=your_password
```

### **🔑 API Keys:**

```env
POLYGON_API_KEY=your_polygon_api_key
FINNHUB_API_KEY=your_finnhub_api_key
YAHOO_API_KEY=your_yahoo_api_key
```

### **🌍 Environment:**

```env
APP_ENV=development          # development/production
APP_DEBUG=true              # true/false
APP_URL=http://localhost    # URL aplikácie
```

### **🔒 Security:**

```env
APP_KEY=your_32_character_random_key
SESSION_SECURE=false        # true pre HTTPS
COOKIE_SECURE=false         # true pre HTTPS
```

### **📊 API Limits:**

```env
API_RATE_LIMIT=100         # Požiadavky za minútu
API_RATE_WINDOW=60         # Časové okno v sekundách
```

---

## 🧪 **Testovanie**

### **Test environment premenných:**

```bash
make test-env

# Alebo priamo
php Tests/test_env.php
```

### **Čo test robí:**

1. ✅ Kontroluje existenciu .env súboru
2. ✅ Testuje načítanie premenných
3. ✅ Overuje databázové pripojenie
4. ✅ Kontroluje API kľúče
5. ✅ Testuje helper funkcie

---

## 🔄 **Rôzne prostredia**

### **Development:**

```env
APP_ENV=development
APP_DEBUG=true
SESSION_SECURE=false
COOKIE_SECURE=false
```

### **Production:**

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=true
COOKIE_SECURE=true
```

---

## 🚨 **Bezpečnosť**

### **✅ Čo robiť:**

- **Vždy** používaj .env pre citlivé údaje
- **Nikdy** necommituj .env súbor do Git
- **Pravidelne** meniť API kľúče
- **Používaj** rôzne kľúče pre development/produkciu

### **❌ Čo sa vyhnúť:**

- **Nikdy** nedávaj API kľúče do kódu
- **Nikdy** necommituj .env súbor
- **Nikdy** nepoužívaj rovnaké kľúče všade
- **Nikdy** nepoužívaj predvolené hesla

---

## 🔧 **Používanie v kóde**

### **Načítanie premenných:**

```php
// Automaticky sa načítajú pri include config.php
require_once 'config/config.php';

// Použitie
echo DB_HOST;           // localhost
echo POLYGON_API_KEY;   // your_api_key
```

### **Pomocou EnvLoader:**

```php
require_once 'config/env_loader.php';

// Získanie hodnoty
$apiKey = EnvLoader::get('POLYGON_API_KEY');
$dbHost = EnvLoader::get('DB_HOST', 'localhost'); // s default hodnotou

// Helper funkcie
if (EnvLoader::isDevelopment()) {
    // Development kód
}

if (EnvLoader::isDebug()) {
    // Debug kód
}
```

---

## 📞 **Troubleshooting**

### **Časté problémy:**

#### **1. .env súbor neexistuje:**

```bash
# Riešenie: Vytvor .env súbor
make setup-env
```

#### **2. API kľúče nefungujú:**

```bash
# Riešenie: Skontroluj .env súbor
cat .env | grep API_KEY
```

#### **3. Databáza sa nepripojí:**

```bash
# Riešenie: Skontroluj DB nastavenia
php Tests/test_env.php
```

#### **4. Premenné sa nenačítajú:**

```bash
# Riešenie: Skontroluj env_loader.php
php -l config/env_loader.php
```

---

## 🎯 **Best Practices**

### **✅ Odporúčania:**

1. **Vždy** používaj .env pre citlivé údaje
2. **Pravidelne** testuj environment
3. **Dokumentuj** všetky premenné
4. **Používaj** rôzne prostredia
5. **Backupuj** .env súbory bezpečne

### **🔒 Bezpečnosť:**

1. **Nikdy** necommituj .env do Git
2. **Používaj** silné API kľúče
3. **Pravidelne** meniť hesla
4. **Omedzuj** prístup k .env súborom
5. **Monitoruj** prístupy k API

---

## 🎉 **Záver**

Environment Variables zabezpečujú:

- **Bezpečnosť** citlivých údajov
- **Flexibilitu** konfigurácie
- **Jednoduchosť** údržby
- **Profesionálny** prístup

**Teraz sú tvoje API kľúče a citlivé údaje bezpečné!** 🔐
