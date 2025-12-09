# 🔧 Riešenie: PM2 posiela SIGINT každých 5 minút

## 🔍 Zistenia

Z logov je jasné:
- **Parent process ID: 8863** = PM2 daemon
- **Stack trace: `process.emit`** = signál prichádza zvonka (nie z kódu)
- **Presne každých 5 minút (299 sekúnd)** = automatický mechanizmus
- **Proces beží stabilne** = nie je to kvôli chybe v kóde

**Záver:** PM2 daemon posiela SIGINT procesu každých 5 minút, pravdepodobne kvôli watchdog/healthcheck mechanizmu.

---

## ✅ Riešenie 1: Upravená PM2 konfigurácia

Upravil som `ecosystem.config.js`:
- ✅ Pridané `min_uptime: "10s"` - proces musí bežať aspoň 10s, aby bol považovaný za stabilný
- ✅ Pridané `kill_timeout: 8000` - čas na graceful shutdown
- ✅ Pridané `listen_timeout: 10000` - čas na spustenie procesu
- ✅ Zmenené `max_restarts: Infinity` - umožniť neobmedzené reštarty (PM2 to zvládne)
- ✅ Pridané `exp_backoff_restart_delay: 100` - exponenciálny backoff pre reštarty

---

## 📋 Príkazy na SSH

### 1. Pullnúť novú konfiguráciu a reštartovať
```bash
cd /srv/EarningsTable
git pull origin main
pm2 delete earnings-table
pm2 start ecosystem.config.js --only earnings-table
sleep 5
pm2 list
```

### 2. Sledovať, či sa SIGINT stále spúšťa
```bash
# Sledovať 10 minút
timeout 600 pm2 logs earnings-table --err --lines 0 2>&1 | grep -A 10 "SIGINT received" || echo "No SIGINT in last 10 minutes - SUCCESS!"
```

### 3. Skontrolovať PM2 status
```bash
pm2 describe earnings-table | grep -E "restart|uptime|status|unstable"
```

---

## 🔍 Riešenie 2: Zistiť, prečo PM2 posiela SIGINT

Ak sa SIGINT stále spúšťa po úprave konfigurácie, skontrolovať:

### A. PM2 watchdog/healthcheck
```bash
# Skontrolovať PM2 interné logy
pm2 logs PM2 --lines 200 --nostream | grep -iE "watchdog|healthcheck|earnings-table|kill|signal" | tail -30

# Skontrolovať PM2 konfiguráciu
pm2 conf earnings-table
```

### B. PM2 verzia a známe bugy
```bash
pm2 --version
# Skontrolovať, či nie je známy bug v PM2 pre watchdog každých 5 minút
```

### C. Systemd (ak je PM2 spustený cez systemd)
```bash
systemctl status pm2-* 2>/dev/null || echo "No systemd PM2 service"
journalctl -u pm2* -n 100 --no-pager 2>/dev/null | grep -iE "earnings|signal|kill" || echo "No relevant logs"
```

### D. Cron jobs
```bash
crontab -l
grep -r "pm2\|earnings" /etc/cron* 2>/dev/null || true
```

---

## 🎯 Riešenie 3: Alternatíva - Vypnúť PM2 watchdog (ak existuje)

Ak PM2 má watchdog, ktorý posiela SIGINT, možno ho vypnúť:

```bash
# Skontrolovať PM2 watchdog nastavenia
pm2 conf earnings-table | grep -i watchdog

# Možno pridať do ecosystem.config.js:
# watch: false (už je tam)
# ignore_watch: ["*"]
```

---

## 📊 Očakávané výsledky

### Po úprave konfigurácie:
- ✅ Proces by mal bežať stabilne bez SIGINT
- ✅ Restart count by sa nemal zvyšovať každých 5 minút
- ✅ Uptime by mal rásť kontinuálne

### Ak sa SIGINT stále spúšťa:
- Skontrolovať PM2 watchdog/healthcheck (príkaz 2A)
- Skontrolovať PM2 verziu a známe bugy
- Možno je to bug v PM2 a treba aktualizovať

---

## 🔧 Riešenie 4: Workaround - Ignorovať SIGINT (NEDOPORUČUJE SA)

Ak nič iné nepomôže, možno ignorovať SIGINT (ale to nie je ideálne):

```javascript
// V simple-server.js
process.on("SIGINT", () => {
  console.error("🛑 SIGINT received but ignoring (PM2 watchdog issue)");
  // Neukončovať proces
});
```

**⚠️ POZOR:** Toto nie je ideálne riešenie, lebo zabráni graceful shutdown.

---

## 📝 Postup

1. **Pullnúť novú konfiguráciu** (príkaz #1)
2. **Sledovať 10 minút** (príkaz #2) - ak sa SIGINT nespustí, problém je vyriešený
3. **Ak sa SIGINT stále spúšťa**, skontrolovať PM2 watchdog (príkaz #2A)
4. **Ak je to PM2 bug**, aktualizovať PM2 alebo použiť workaround

---

## 🎯 Najpravdepodobnejšie riešenie

**PM2 má interný watchdog**, ktorý kontroluje procesy každých 5 minút. Nová konfigurácia s `min_uptime` a `kill_timeout` by to malo vyriešiť.

Ak nie, je to pravdepodobne **bug v PM2** a treba:
- Aktualizovať PM2 na najnovšiu verziu
- Alebo použiť workaround s ignorovaním SIGINT (nie ideálne)

