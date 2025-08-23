# 📊 PROJECT SIZE SUMMARY - EarningsTable

## 📁 **Celková Veľkosť Projektu**

### **Všetky súbory:**
- **Veľkosť:** 0.69 MB (709.72 KB)
- **Kódové súbory:** 0.67 MB (689.64 KB)
- **Počet riadkov kódu:** 17,807 riadkov

---

## 📂 **Rozdelenie podľa Typov Súborov**

### **PHP Súbory (Backend)**
- **Počet súborov:** 83
- **Veľkosť:** ~400-500 KB (odhad)
- **Hlavné kategórie:**
  - Cron joby: 9 súborov
  - API endpoints: 6 súborov
  - Test súbory: 35+ súborov
  - Utility súbory: 10+ súborov
  - Konfiguračné súbory: 5+ súborov

### **HTML Súbory (Frontend)**
- **Počet súborov:** 21
- **Veľkosť:** ~100-150 KB (odhad)
- **Hlavné kategórie:**
  - Earnings tabuľky: 8 súborov
  - Debug stránky: 5 súborov
  - Test stránky: 5 súborov
  - Dashboard: 3 súbory

### **JavaScript Súbory**
- **Počet súborov:** 3
- **Veľkosť:** ~50 KB (odhad)
- **Účel:** Frontend funkcionalita, API volania

### **CSS Súbory**
- **Počet súborov:** 0 (CSS je v HTML súboroch)
- **Veľkosť:** ~20-30 KB (embedded v HTML)

### **SQL Súbory**
- **Počet súborov:** 3
- **Veľkosť:** ~10-15 KB
- **Účel:** Databázové schémy a optimalizácie

### **Konfiguračné Súbory**
- **Batch súbory:** 10+ súborov
- **XML súbory:** 4 (Task Scheduler)
- **Markdown súbory:** 5+ (dokumentácia)

---

## 🏗️ **Architektúra Projektu**

### **Backend (PHP)**
```
📁 cron/           - 9 cron jobov
📁 public/api/     - 6 API endpoints
📁 utils/          - Utility funkcie
📁 common/         - Spoločné triedy
📁 scripts/        - Setup a maintenance
```

### **Frontend (HTML/JS)**
```
📁 public/         - Všetky webové stránky
📁 public/api/     - API endpoints
📁 public/         - HTML tabuľky a dashboard
```

### **Databáza**
```
📁 sql/            - Databázové schémy
📁 tasks/          - Windows Task Scheduler
```

---

## 📈 **Štatistiky Kódu**

### **Najväčšie Súbory:**
1. **`cron/current_prices_mcaps_updates.php`** - 296 riadkov
2. **`cron/fetch_earnings_tickers.php`** - 217 riadkov
3. **`cron/update_earnings_eps_revenues.php`** - 151 riadkov
4. **`cron/update_company_names.php`** - 115 riadkov

### **Najaktívnejšie Kategórie:**
- **Test súbory:** 35+ súborov (40% všetkých PHP súborov)
- **Cron joby:** 9 súborov (11% PHP súborov)
- **API endpoints:** 6 súborov (7% PHP súborov)

---

## 🎯 **Zhrnutie**

### **Veľkosť:**
- **Celková:** 0.69 MB
- **Kód:** 0.67 MB
- **Riadky kódu:** 17,807

### **Súbory:**
- **PHP:** 83 súborov
- **HTML:** 21 súborov
- **JavaScript:** 3 súbory
- **SQL:** 3 súbory
- **Ostatné:** 20+ súborov

### **Typ Aplikácie:**
- **Backend:** PHP s MySQL databázou
- **Frontend:** HTML/JavaScript
- **Scheduler:** Windows Task Scheduler + Batch súbory
- **APIs:** Finnhub, Polygon.io

### **Komplexita:**
- **Stredne komplexná** webová aplikácia
- **Automatizované** cron joby
- **Real-time** dáta z externých API
- **Modulárna** architektúra

---

## 💡 **Poznámky**

- Projekt je **kompaktný** a **efektívny**
- Veľa test súborov pre debugging
- Dobre organizovaná štruktúra
- Optimalizované cron joby
- Komplexná logika pre spracovanie finančných dát
