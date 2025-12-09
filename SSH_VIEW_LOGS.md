# 📊 Zobrazenie logov z databázy

## 🔍 Problém

Logy sa nezobrazujú na obrazovke, ale mali by sa zapisovať do databázy v tabuľkách:
- `CronExecutionLog` - história všetkých cron jobov
- `CronStatus` - aktuálny stav cron jobov

---

## 📋 Príkazy na SSH

### 1. Skontrolovať, či sa logy zapisujú do databázy

```bash
cd /srv/EarningsTable

# Skontrolovať posledných 20 logov
sqlite3 modules/database/prisma/prod.db "SELECT id, jobType, status, datetime(startedAt, 'localtime') as startedAt, datetime(completedAt, 'localtime') as completedAt, duration, recordsProcessed, errorMessage FROM cron_execution_log ORDER BY startedAt DESC LIMIT 20;"
```

### 2. Skontrolovať aktuálny stav cron jobov

```bash
sqlite3 modules/database/prisma/prod.db "SELECT jobType, datetime(lastRunAt, 'localtime') as lastRunAt, status, recordsProcessed, errorMessage FROM cron_status ORDER BY lastRunAt DESC;"
```

### 3. Počítať logy podľa typu (posledných 24 hodín)

```bash
sqlite3 modules/database/prisma/prod.db "SELECT jobType, status, COUNT(*) as count FROM cron_execution_log WHERE startedAt > datetime('now', '-1 day') GROUP BY jobType, status ORDER BY jobType, status;"
```

### 4. Skontrolovať, či sa logy zapisujú (posledná 1 hodina)

```bash
sqlite3 modules/database/prisma/prod.db "SELECT COUNT(*) as recent_logs FROM cron_execution_log WHERE startedAt > datetime('now', '-1 hour');"
```

### 5. Najnovší log

```bash
sqlite3 modules/database/prisma/prod.db "SELECT id, jobType, status, datetime(startedAt, 'localtime') as startedAt, datetime(completedAt, 'localtime') as completedAt, duration, recordsProcessed FROM cron_execution_log ORDER BY startedAt DESC LIMIT 1;"
```

---

## 🔍 Diagnostika

### Ak sa logy nezapisujú:

1. **Skontrolovať, či cron job beží:**
   ```bash
   pm2 list
   pm2 logs earnings-cron --lines 50 --nostream | tail -20
   ```

2. **Skontrolovať, či je tabuľka vytvorená:**
   ```bash
   sqlite3 modules/database/prisma/prod.db ".tables" | grep cron
   ```

3. **Skontrolovať, či má tabuľka dáta:**
   ```bash
   sqlite3 modules/database/prisma/prod.db "SELECT COUNT(*) FROM cron_execution_log;"
   ```

4. **Skontrolovať cron job logy:**
   ```bash
   pm2 logs earnings-cron --err --lines 100 --nostream | grep -iE "error|failed|CronExecutionLog" | tail -20
   ```

---

## 📊 Formátovaný výstup

Pre lepšie zobrazenie môžeš použiť:

```bash
sqlite3 -header -column modules/database/prisma/prod.db "SELECT * FROM cron_execution_log ORDER BY startedAt DESC LIMIT 10;"
```

---

## 🎯 Čo hľadať

### Ak sa logy zapisujú:
- ✅ V tabuľke `cron_execution_log` by mali byť nové záznamy
- ✅ V tabuľke `cron_status` by mali byť aktuálne stavy
- ✅ `lastRunAt` by mal byť nedávny

### Ak sa logy nezapisujú:
- ❌ Tabuľka `cron_execution_log` je prázdna alebo stará
- ❌ V cron job logoch sú chyby
- ❌ Cron job nebeží alebo padá

