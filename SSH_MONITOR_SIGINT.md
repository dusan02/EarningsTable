# SSH Príkazy na Monitorovanie SIGINT

## Situácia
Proces `earnings-table` sa stále reštartuje (3353 reštartov). V logoch vidíme `🛑 Shutting down server...`, čo znamená, že SIGINT handler sa spúšťa. Nový kód s detailným logovaním je už na serveri (git pull bol úspešný).

## Cieľ
Zistiť, **kto/kedy/prečo** posiela SIGINT signál procesu.

---

## Rýchle príkazy

### 1. Skontrolovať, či je nový kód na serveri
```bash
cd /srv/EarningsTable
git log --oneline -1
# Mala by byť vidieť posledná commit s "Add detailed logging"
```

### 2. Reštartovať a sledovať logy
```bash
pm2 restart earnings-table
sleep 5
pm2 logs earnings-table --lines 100 --nostream | tail -50
```

### 3. Sledovať v reálnom čase (najlepšie)
```bash
# Sledovať všetky dôležité eventy
pm2 logs earnings-table --lines 0 2>&1 | grep -iE "SIGINT|beforeExit|exit|Keep-alive|heartbeat|Stack trace"
```

### 4. Skontrolovať stderr pre detailné logy
```bash
pm2 logs earnings-table --err --lines 200 --nostream | grep -iE "SIGINT|beforeExit|exit|Stack trace|uptime|Memory" | tail -30
```

---

## Čo hľadať v logoch

Po reštarte by sme mali vidieť:

1. **Pri štarte:**
   - `✅ Keep-alive mechanism initialized`
   - `🚀 API Server running on port 5555`

2. **Ak sa SIGINT spustí:**
   - `🛑 SIGINT received at [timestamp]`
   - `🛑 Stack trace: [stack trace]` ← **Toto je kľúčové!** Ukáže, kto volá SIGINT
   - `🛑 Process uptime: [seconds]` ← Ako dlho bežal pred ukončením
   - `🛑 Memory usage: [object]` ← Memory usage v momente SIGINT
   - `🛑 Shutting down server...`

3. **Ak sa proces pokúša ukončiť inak:**
   - `⚠️ Process beforeExit event: [code]`
   - `⚠️ Stack trace: [stack trace]`
   - `⚠️ Active handles: [count]`
   - `⚠️ Active requests: [count]`

4. **Keep-alive heartbeat (každých 5 minút):**
   - `💓 Keep-alive heartbeat: [timestamp], uptime: [seconds]s`

---

## Diagnostika

### Zistiť, kto posiela SIGINT

Stack trace v logoch ukáže, odkiaľ prichádza SIGINT. Možné zdroje:

1. **PM2** - ak PM2 detekuje problém a posiela SIGINT
2. **Systemd** - ak je PM2 spustený cez systemd
3. **Cron job** - ak nejaký cron job reštartuje procesy
4. **Iný proces** - ak nejaký iný proces posiela signál

### Príkazy na diagnostiku

```bash
# 1. Skontrolovať PM2 konfiguráciu
pm2 describe earnings-table

# 2. Skontrolovať, či nejaký cron job reštartuje procesy
crontab -l
grep -r "pm2\|earnings" /etc/cron* 2>/dev/null || true

# 3. Skontrolovať systemd (ak je PM2 spustený cez systemd)
systemctl status pm2-* 2>/dev/null || echo "No systemd PM2 service"

# 4. Skontrolovať, či nejaký iný proces posiela signály
ps aux | grep -E "pm2|earnings|node.*simple-server"

# 5. Skontrolovať PM2 logy pre chyby
pm2 logs --err --lines 100 | grep -iE "error|kill|signal|restart" | tail -20
```

---

## Očakávané výsledky

### Ak SIGINT prichádza z PM2:
Stack trace ukáže PM2 interné volania.

### Ak SIGINT prichádza z iného procesu:
Stack trace ukáže externý proces alebo system call.

### Ak proces končí inak (nie SIGINT):
Uvidíme `beforeExit` alebo `exit` eventy bez SIGINT.

---

## Ďalšie kroky

Po získaní stack trace z logov:
1. Identifikovať zdroj SIGINT
2. Zistiť, prečo tento zdroj posiela SIGINT
3. Opraviť konfiguráciu alebo kód podľa potreby

---

## Poznámky

- **Keep-alive heartbeat** sa zobrazuje každých 5 minút (ak beží proces aspoň 5 minút)
- Ak proces končí skôr ako za 5 minút, neuvidíme heartbeat
- **Stack trace** je najdôležitejší - ukáže presný zdroj problému

