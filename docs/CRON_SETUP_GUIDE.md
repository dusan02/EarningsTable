# ⏰ Cron Jobs Setup Guide - mydreams.cz

## 🚨 **DÔLEŽITÉ: Crony sa nespustia automaticky!**

Po uploadu na hosting treba **manuálne nastaviť** cron joby v cPanel.

## 📋 **EXISTUJÚCE CRON SKRIPTY:**

### **Denné skripty (raz denne):**

- `clear_old_data.php` - čistenie starých dát
- `fetch_finnhub_earnings_today_tickers.php` - načítanie earnings pre dnešok
- `fetch_market_cap_polygon_batch.php` - načítanie market cap dát
- `fetch_polygon_batch_earnings.php` - načítanie earnings dát

### **5-minútové skripty (každých 5 minút):**

- `run_5min_updates.php` - hlavný 5-minútový update
- `update_polygon_data_5min.php` - Polygon dáta
- `update_finnhub_data_5min.php` - Finnhub dáta

### **Voliteľné skripty:**

- `fetch_missing_data_yahoo.php` - doplnenie chýbajúcich dát
- `fetch_missing_tickers_yahoo.php` - doplnenie chýbajúcich tickerov
- `fetch_market_data_complete.php` - kompletná aktualizácia

## 🔧 **NASTAVENIE V CPANEL:**

### **1. Prihlásiť sa do cPanel**

- URL: `https://vaša-domena.mydreams.cz/cpanel`
- Používateľské meno a heslo

### **2. Nájsť "Cron Jobs"**

- V cPanel menu hľadať "Cron Jobs" alebo "Scheduled Tasks"
- Kliknúť na "Cron Jobs"

### **3. Pridať cron joby**

#### **Denné zálohovanie (2:00 AM):**

```
Minute: 0
Hour: 2
Day: *
Month: *
Weekday: *
Command: php /home/username/public_html/cron/clear_old_data.php
```

#### **5-minútové aktualizácie:**

```
Minute: */5
Hour: *
Day: *
Month: *
Weekday: *
Command: php /home/username/public_html/cron/run_5min_updates.php
```

#### **Denné načítanie earnings (6:00 AM):**

```
Minute: 0
Hour: 6
Day: *
Month: *
Weekday: *
Command: php /home/username/public_html/cron/fetch_finnhub_earnings_today_tickers.php
```

#### **Denné načítanie market cap (7:00 AM):**

```
Minute: 0
Hour: 7
Day: *
Month: *
Weekday: *
Command: php /home/username/public_html/cron/fetch_market_cap_polygon_batch.php
```

## 📊 **ODPORÚČANÝ ROZVRH:**

| Čas   | Skript                                     | Frekvencia    | Účel                 |
| ----- | ------------------------------------------ | ------------- | -------------------- |
| 02:00 | `clear_old_data.php`                       | Denné         | Čistenie starých dát |
| 06:00 | `fetch_finnhub_earnings_today_tickers.php` | Denné         | Earnings pre dnešok  |
| 07:00 | `fetch_market_cap_polygon_batch.php`       | Denné         | Market cap dáta      |
| \*/5  | `run_5min_updates.php`                     | Každých 5 min | Aktualizácia cien    |

## 🧪 **TESTOVANIE CRON JOBOV:**

### **1. Manuálne spustenie:**

```bash
# V cPanel -> Terminal alebo cez SSH
cd /home/username/public_html
php cron/clear_old_data.php
php cron/run_5min_updates.php
```

### **2. Kontrola logov:**

```bash
# Skontrolovať logy
tail -f logs/app.log
tail -f logs/cron.log
```

### **3. Test cez web:**

```
https://vaša-domena.mydreams.cz/cron/clear_old_data.php
```

## 🚨 **ČASTÉ PROBLÉMY:**

### **Chyba: Permission denied**

- ✅ Skontrolovať cestu k PHP: `which php`
- ✅ Skontrolovať oprávnenia súborov
- ✅ Použiť absolútnu cestu k PHP

### **Chyba: Command not found**

- ✅ Skontrolovať cestu k PHP v cPanel
- ✅ Použiť: `/usr/bin/php` namiesto `php`

### **Chyba: Database connection failed**

- ✅ Skontrolovať .env súbor
- ✅ Overiť databázové pripojenie

## 📝 **PRÍKLADY CRON KOMÁNDOV:**

### **Základné:**

```bash
php /home/username/public_html/cron/clear_old_data.php
```

### **S logovaním:**

```bash
php /home/username/public_html/cron/clear_old_data.php >> /home/username/public_html/logs/cron.log 2>&1
```

### **S absolútnou cestou k PHP:**

```bash
/usr/bin/php /home/username/public_html/cron/clear_old_data.php
```

## ✅ **CHECKLIST:**

- [ ] Cron Jobs sekcia v cPanel
- [ ] Pridané denné skripty (02:00, 06:00, 07:00)
- [ ] Pridané 5-minútové skripty (\*/5)
- [ ] Testované manuálne spustenie
- [ ] Skontrolované logy
- [ ] Overené, že sa spúšťajú automaticky

## 🎯 **PO NASTAVENÍ:**

1. **Počkať 5-10 minút** na prvé spustenie
2. **Skontrolovať logy** v `logs/` priečinku
3. **Overiť dáta** v databáze
4. **Monitorovať** výkon a chyby

---

**⚠️ DÔLEŽITÉ: Bez nastavenia cron jobov sa dáta nebudú automaticky aktualizovať!**
