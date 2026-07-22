# 🔍 Zistenie, kto posiela SIGINT procesu

## 🚨 Problém

Proces `earnings-table` dostáva SIGINT a ukončuje sa:
```
🛑 Shutting down server...
```

## 🔍 Príkazy na SSH na zistenie príčiny

### 1. Sledovať SIGINT v reálnom čase
```bash
# Sledovať logy v reálnom čase a hľadať SIGINT
pm2 logs earnings-table --err | grep -i "SIGINT\|Shutting down\|beforeExit\|exit"
```

### 2. Zistiť, kedy sa SIGINT spúšťa
```bash
# Posledných 1000 riadkov s timestampmi
pm2 logs earnings-table --lines 1000 --nostream | grep -B 10 -A 10 "SIGINT\|Shutting down" | tail -50
```

### 3. Skontrolovať PM2 konfiguráciu
```bash
# Zobraziť kompletnú PM2 konfiguráciu
pm2 show earnings-table

# Skontrolovať, či nie je nejaký automatický reštart
pm2 describe earnings-table | grep -iE "restart|max_restarts|min_uptime|kill_timeout"
```

### 4. Zistiť, či PM2 posiela signály
```bash
# Sledovať PM2 procesy
ps aux | grep -E "pm2|node.*simple-server"

# Skontrolovať PM2 daemon logy
pm2 logs PM2 --lines 100 --nostream | grep -i "earnings-table\|SIGINT\|kill\|restart"
```

### 5. Skontrolovať systémové logy
```bash
# Skontrolovať systemd alebo iné služby, ktoré môžu posielať signály
journalctl -u pm2* -n 100 --no-pager 2>/dev/null || echo "No systemd service"

# Skontrolovať cron jobs, ktoré môžu posielať signály
crontab -l | grep -i "earnings\|pm2\|kill"
```

### 6. Sledovať proces v reálnom čase
```bash
# Monitorovať proces
pm2 monit

# Alebo sledovať pomocou strace (ak je nainštalovaný)
# strace -p $(pm2 jlist | jq '.[] | select(.name=="earnings-table") | .pid') 2>&1 | grep -i "signal\|kill"
```

### 7. Skontrolovať, či nie je health check
```bash
# Skontrolovať, či nie je nejaký externý health check
pm2 show earnings-table | grep -iE "health|check|monitor"

# Skontrolovať nginx alebo iný reverse proxy
nginx -t 2>/dev/null && cat /etc/nginx/sites-enabled/* | grep -i "earnings\|5555\|health" || echo "No nginx config found"
```

## 📊 Po aplikovaní nového kódu

Nový kód má detailnejšie logovanie. Po reštarte by sme mali vidieť:

```bash
pm2 restart earnings-table
sleep 10
pm2 logs earnings-table --lines 200 --nostream | grep -iE "SIGINT|beforeExit|exit|Keep-alive|heartbeat" | tail -30
```

**Očakávané logy:**
- `✅ Keep-alive mechanism initialized` - potvrdenie, že keep-alive beží
- `💓 Keep-alive heartbeat` - každých 5 minút
- `⚠️ Process beforeExit event` - ak sa proces pokúša ukončiť
- `🛑 SIGINT received at [timestamp]` - kedy a prečo sa SIGINT spúšťa
- `🛑 Stack trace` - kto volá SIGINT

## 🎯 Možné príčiny

1. **PM2 automatický reštart** - PM2 detekuje proces ako "failed"
2. **Health check zlyháva** - nejaký externý monitor posiela SIGINT
3. **Memory limit** - PM2 reštartuje kvôli memory (ale memory je OK)
4. **Timeout** - nejaký timeout spôsobuje ukončenie
5. **Iný proces** - niekto iný posiela SIGINT

## 🔧 Riešenie po zistení príčiny

### Ak PM2 posiela SIGINT:
- Skontrolovať `ecosystem.config.js` - možno pridať `min_uptime` a `kill_timeout`
- Skontrolovať, či nie je `max_restarts` dosiahnutý

### Ak externý proces posiela SIGINT:
- Zistiť, ktorý proces to je
- Buď ho vypnúť, alebo upraviť konfiguráciu

### Ak je to timeout:
- Zvýšiť timeout hodnoty v PM2 konfigurácii

