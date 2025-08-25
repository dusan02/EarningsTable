# 🚀 Hosting Deployment Guide - mydreams.cz

## ⚠️ **POVINNÉ KROKY PRED UPLOADOM**

### 1. **Získať API kľúče**

- **Finnhub API**: https://finnhub.io/ - zaregistrovať sa a získať API key
- **Polygon API**: https://polygon.io/ - zaregistrovať sa a získať API key

### 2. **Nastaviť databázu na mydreams.cz**

- Vytvoriť MySQL databázu v cPanel
- Poznať: DB_HOST, DB_NAME, DB_USER, DB_PASS

### 3. **Upraviť konfiguráciu**

```bash
# Vytvoriť .env súbor z env.example
cp env.example .env

# Upraviť .env súbor s reálnymi údajmi:
DB_HOST=localhost
DB_NAME=vaša_databáza
DB_USER=vaše_db_meno
DB_PASS=vaše_db_heslo
POLYGON_API_KEY=váš_polygon_key
FINNHUB_API_KEY=váš_finnhub_key
APP_URL=https://vaša-domena.mydreams.cz
APP_ENV=production
APP_DEBUG=false
```

### 4. **Vytvoriť databázu**

```sql
-- Spustiť v phpMyAdmin alebo cez MySQL
SOURCE sql/setup_database.sql;
```

## 📁 **STRUKTÚRA PRE UPLOAD**

### ✅ **Povinné súbory:**

```
public/                    # Web root (nastaviť ako Document Root)
├── dashboard-fixed.html   # Hlavný dashboard
├── api/                   # API endpointy
├── adminlte/             # UI framework
└── index.php             # Hlavný súbor

config/
├── config.php            # Konfigurácia (používa .env)
├── env_loader.php        # .env loader
└── database_helper.php   # DB helper

cron/                     # Cron skripty
sql/
└── setup_database.sql    # DB setup

logs/                     # Logy (vytvoriť automaticky)
storage/                  # Storage (vytvoriť automaticky)

.env                      # Environment variables (vytvoriť z env.example)
```

### ❌ **NENAHRAVAŤ:**

- `.git/` priečinok
- `docs/` priečinok
- `Tests/` priečinok
- `scripts/` priečinok (okrem cron)
- `composer.json` (ak nie je potrebný)
- `.gitignore`

## 🔧 **NASTAVENIE NA MYDREAMS.CZ**

### 1. **cPanel nastavenia**

- **Document Root**: nastaviť na `public/` priečinok
- **PHP Version**: 8.0 alebo vyššie
- **MySQL**: vytvoriť databázu

### 2. **Oprávnenia súborov**

```bash
chmod 755 public/
chmod 644 public/*.html
chmod 644 public/*.php
chmod 755 logs/
chmod 755 storage/
chmod 600 config/config.php
```

### 3. **Cron jobs (voliteľné)**

```bash
# V cPanel -> Cron Jobs
# Denné zálohovanie
0 2 * * * php /home/username/public_html/cron/clear_old_data.php

# Aktualizácia dát každých 5 minút
*/5 * * * * php /home/username/public_html/cron/run_5min_updates.php
```

## 🧪 **TESTOVANIE PO UPLOADE**

### 1. **Kontrola konfigurácie**

```
https://vaša-domena.mydreams.cz/check_page.php
```

### 2. **Test databázy**

```php
<?php
require_once '../config/config.php';
echo "Database connection: " . ($pdo ? "OK" : "FAILED");
?>
```

### 3. **Test API**

```php
<?php
require_once '../config/config.php';
echo "Finnhub API: " . (FINNHUB_API_KEY !== 'your_finnhub_api_key_here' ? "OK" : "NOT SET");
echo "Polygon API: " . (POLYGON_API_KEY !== 'your_polygon_api_key_here' ? "OK" : "NOT SET");
?>
```

## 🚨 **ČASTÉ PROBLÉMY**

### **Chyba: Database connection failed**

- ✅ Skontrolovať DB_HOST, DB_NAME, DB_USER, DB_PASS
- ✅ Overiť, či databáza existuje
- ✅ Skontrolovať DB oprávnenia

### **Chyba: API key not configured**

- ✅ Skontrolovať FINNHUB_API_KEY a POLYGON_API_KEY v .env súbore
- ✅ Overiť platnosť API kľúčov
- ✅ Skontrolovať, či .env súbor existuje a je čitateľný

### **Chyba: Permission denied**

- ✅ Nastaviť správne oprávnenia súborov
- ✅ Skontrolovať Document Root nastavenie

### **Chyba: 500 Internal Server Error**

- ✅ Skontrolovať PHP error log
- ✅ Overiť syntax v config.php
- ✅ Skontrolovať .htaccess súbor

## 📞 **PODPORA**

### **mydreams.cz podpora:**

- Email: podpora@mydreams.cz
- Telefón: +420 xxx xxx xxx

### **Projekt dokumentácia:**

- `docs/DATABASE_SECURITY.md` - Bezpečnosť
- `docs/BACKUP_SECURITY.md` - Zálohovanie
- `README.md` - Hlavná dokumentácia

## ✅ **CHECKLIST PRED UPLOADOM**

- [ ] API kľúče získané a nastavené
- [ ] Databáza vytvorená a nastavená
- [ ] config.php upravený s reálnymi údajmi
- [ ] Document Root nastavený na `public/`
- [ ] Oprávnenia súborov nastavené
- [ ] Testovanie lokálne prebehlo úspešne
- [ ] Backup vytvorený
- [ ] DNS nastavené (ak je potrebné)

## 🎯 **PO UPLOADE**

1. **Otestovať dashboard**: `https://vaša-domena.mydreams.cz`
2. **Skontrolovať logy**: `logs/app.log`
3. **Nastaviť cron jobs** (ak je potrebné)
4. **Otestovať API endpointy**
5. **Skontrolovať bezpečnosť**

---

**ÚSPEŠNÉHO NASADENIA! 🚀**
