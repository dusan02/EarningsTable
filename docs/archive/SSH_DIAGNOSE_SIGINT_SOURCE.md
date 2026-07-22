# Diagnostika zdroja SIGINT

## 🔍 Zistenia

- **SIGINT sa spúšťa každých ~5 minút** (299 sekúnd)
- **Proces beží presne 5 minút** predtým, ako dostane SIGINT
- **Stack trace bol neúplný** - opravené v novom kóde

## 📋 Príkazy na SSH

### 1. Pullnúť nový kód a reštartovať
```bash
cd /srv/EarningsTable
git pull origin main
pm2 restart earnings-table
sleep 5
```

### 2. Sledovať detailné SIGINT logy
```bash
pm2 logs earnings-table --err --lines 0 2>&1 | grep -A 20 "SIGINT received"
```

### 3. Skontrolovať, kto posiela SIGINT (po SIGINT evente)
```bash
# Počkať na SIGINT a potom skontrolovať
pm2 logs earnings-table --err --lines 100 --nostream | grep -A 15 "SIGINT received" | tail -20
```

### 4. Skontrolovať PM2 watchdog/healthcheck
```bash
# PM2 interné logy
pm2 logs --lines 100 | grep -iE "watchdog|healthcheck|restart|kill"

# PM2 describe pre detailné info
pm2 describe earnings-table

# PM2 interné nastavenia
pm2 conf earnings-table
```

### 5. Skontrolovať systemd/cron (ak je PM2 spustený cez systemd)
```bash
# Systemd status
systemctl status pm2-* 2>/dev/null || echo "No systemd PM2 service"

# Cron jobs
crontab -l
grep -r "pm2\|earnings" /etc/cron* 2>/dev/null || true
```

### 6. Skontrolovať parent process (kto spúšťa PM2)
```bash
# Zistiť parent process PM2
ps aux | grep pm2 | grep -v grep

# Zistiť parent process earnings-table
ps aux | grep "simple-server.js" | grep -v grep

# Process tree
pstree -p $(pgrep -f "pm2.*earnings-table" | head -1) 2>/dev/null || pstree -p
```

## 🎯 Čo hľadať v nových logoch

Po pullnutí nového kódu uvidíš pri SIGINT:

```
🛑 SIGINT received at [timestamp]
🛑 Process uptime: [seconds]
🛑 Memory usage: {...}
🛑 Full stack trace:
   Error
       at process.on (simple-server.js:XXX)
       ...
🛑 Process ID: [pid]
🛑 Parent process ID: [ppid]
🛑 Environment: {...}
```

**Kľúčové informácie:**
- **Stack trace** - ukáže, odkiaľ prichádza SIGINT
- **Parent process ID** - ukáže, kto je parent proces
- **Process ID** - aktuálny PID procesu

## 🔍 Možné zdroje SIGINT

### 1. PM2 Watchdog
- PM2 môže mať interný watchdog, ktorý kontroluje procesy
- Skontrolovať: `pm2 describe earnings-table` a hľadať watchdog settings

### 2. PM2 Healthcheck
- PM2 môže mať healthcheck, ktorý posiela signály
- Skontrolovať: `pm2 conf earnings-table`

### 3. Systemd
- Ak je PM2 spustený cez systemd, môže posielať signály
- Skontrolovať: `systemctl status pm2-*`

### 4. Cron Job
- Cron job môže reštartovať procesy
- Skontrolovať: `crontab -l` a `/etc/cron*`

### 5. Iný proces
- Iný proces môže posielať SIGINT
- Skontrolovať: `ps aux | grep -E "pm2|earnings"`

## 📝 Postup

1. **Pullnúť nový kód** (príkaz #1)
2. **Sledovať logy** (príkaz #2) - nechať bežať 5-10 minút
3. **Keď sa SIGINT spustí**, skontrolovať:
   - Stack trace v logoch
   - Parent process ID
   - PM2 konfiguráciu
4. **Identifikovať zdroj** na základe stack trace a parent process ID

## ⚠️ Poznámka

SIGINT sa spúšťa **presne každých 5 minút** (299 sekúnd), čo naznačuje automatický mechanizmus. Najpravdepodobnejšie je to:
- PM2 watchdog/healthcheck
- Systemd timer
- Cron job

Nový kód s detailným logovaním by mal ukázať presný zdroj.

