# 🚀 DEPLOYMENT GUIDE - EARNINGS TABLE OPTIMIZATION

## 📋 Pre-Deployment Checklist

### 1. Lokálne testy
```bash
# Performance test
php test_performance_comparison.php

# Deployment checklist
php scripts/deployment_checklist.php

# Data validation
php scripts/validate_data_integrity.php
```

### 2. Backup existujúcich dát
```sql
-- Vytvoriť backup tabuľky
CREATE TABLE TodayEarningsMovements_old AS SELECT * FROM TodayEarningsMovements;
```

## 📦 Súbory pre upload na Websupport

### Core Files:
- `config.php` - Konfigurácia (API keys, DB)
- `utils/polygon_api_optimized.php` - Batch API wrapper
- `cron/update_movements_production.php` - Nový cron script
- `public/api/today-earnings-movements.php` - JSON API endpoint

### Scripts:
- `scripts/deployment_checklist.php` - Deployment validation
- `scripts/validate_data_integrity.php` - Data integrity check
- `sql/optimize_indexes.sql` - Database optimization

## 🔧 Deployment Steps

### Step 1: Upload súborov
```bash
# Upload všetkých súborov na Websupport
# Preskontrolovať permissions (644 pre súbory, 755 pre adresáre)
```

### Step 2: Database optimization
```sql
-- Spustiť v phpMyAdmin alebo cez SSH
SOURCE sql/optimize_indexes.sql;
```

### Step 3: Backup existujúcich dát
```sql
-- Vytvoriť backup
CREATE TABLE TodayEarningsMovements_old AS SELECT * FROM TodayEarningsMovements;
```

### Step 4: Test nového cronu
```bash
# Spustiť manuálne
php cron/update_movements_production.php

# Skontrolovať log
tail -f logs/movements_fast.log
```

### Step 5: Aktualizácia cron job
```bash
# Websupport Admin → Cron Jobs
# Zmeniť príkaz na:
php ~/earnings-table/cron/update_movements_production.php >> ~/logs/movements_fast.log 2>&1

# Nastaviť "prevent overlapping" na YES
```

## 📊 Monitoring po deployment

### 1. Cron execution time
```bash
# Sledovať log
tail -f logs/movements_fast.log

# Hľadať:
# - "Execution time: X seconds"
# - "Memory peak: X MB"
# - "SUCCESS: X tickers updated"
```

### 2. API usage
- Polygon dashboard → Usage metrics
- Cieľ: ≤ 288 hits/deň

### 3. Frontend performance
- JSON endpoint response time
- Cieľ: < 50ms

### 4. Data accuracy
```bash
# Spustiť validation
php scripts/validate_data_integrity.php
```

## ⚠️ Troubleshooting

### Problém: Cron trvá > 5 minút
**Riešenie:**
- Skontrolovať memory_limit v php.ini
- Znížiť batch size v polygon_api_optimized.php
- Skontrolovať API rate limits

### Problém: Memory usage > 350 MB
**Riešenie:**
- Optimalizovať data filtering
- Implementovať streaming processing
- Zvýšiť memory_limit (ak je možné)

### Problém: API connection failed
**Riešenie:**
- Overiť API key
- Skontrolovať network connectivity
- Overiť rate limits

## 📈 Expected Results

| Metrika | Cieľ | Ako overiť |
|---------|------|------------|
| Cron runtime | ≤ 180s | `tail -f logs/movements_fast.log` |
| API hits | ≤ 288/day | Polygon dashboard |
| Memory peak | < 350 MB | Log file |
| Frontend latency | < 50ms | Browser dev tools |

## 🔄 Rollback Plan

Ak sa vyskytnú problémy:

1. **Zastaviť nový cron**
2. **Obnoviť starý cron script**
3. **Obnoviť dáta z backupu**
```sql
TRUNCATE TABLE TodayEarningsMovements;
INSERT INTO TodayEarningsMovements SELECT * FROM TodayEarningsMovements_old;
```

## 📞 Support

Pri problémoch:
1. Skontrolovať log súbory
2. Spustiť deployment checklist
3. Overiť API connectivity
4. Kontaktovať support s log súbormi

---

**✅ Deployment Ready!** 