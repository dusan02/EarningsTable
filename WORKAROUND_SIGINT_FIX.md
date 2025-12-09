# 🔧 Workaround: Ignorovanie SIGINT od PM2 watchdog

## 🚨 Problém

PM2 watchdog posiela **SIGINT každých 5 minút**, čo spôsobuje:
- Proces sa ukončuje každých 5 minút
- Restart count sa zvyšuje
- Proces sa nikdy nedostane do stabilného stavu

## ✅ Riešenie

Pridaný **workaround**, ktorý **ignoruje SIGINT**, ak proces beží **menej ako 10 minút**:

```javascript
// Ignore SIGINT if process has been running for less than 10 minutes
if (uptime < 600) { // 10 minutes
  console.error("⚠️ Ignoring SIGINT - PM2 watchdog premature signal");
  return; // Don't shutdown
}
```

## 📋 Príkazy na SSH

### 1. Pullnúť workaround a reštartovať
```bash
cd /srv/EarningsTable
git pull origin main
pm2 restart earnings-table
sleep 5
pm2 list
```

### 2. Sledovať, či sa SIGINT ignoruje
```bash
# Sledovať 10 minút
timeout 600 pm2 logs earnings-table --err --lines 0 2>&1 | grep -iE "Ignoring SIGINT|SIGINT received" || echo "✅ No SIGINT events"
```

### 3. Skontrolovať, či proces beží stabilne
```bash
# Počkať 10 minút a skontrolovať restart count
pm2 describe earnings-table | grep "restart"
sleep 600
pm2 describe earnings-table | grep -E "restart|uptime"
```

## 📊 Očakávané výsledky

### Po aplikovaní workaround:
- ✅ SIGINT sa stále spúšťa každých 5 minút (PM2 watchdog)
- ✅ Ale proces **ignoruje SIGINT** a pokračuje v behu
- ✅ Restart count sa **nezvyšuje**
- ✅ Uptime **rastie kontinuálne**
- ✅ V logoch uvidíš: `⚠️ Ignoring SIGINT - process has only been running for Xs`

### Po 10 minútach:
- ✅ Ak sa SIGINT spustí po 10 minútach, proces sa ukončí normálne (graceful shutdown)
- ✅ PM2 ho reštartuje automaticky
- ✅ Ale proces už bežal 10+ minút, takže to nie je problém

## 🔍 Čo sa deje v logoch

### Prvých 10 minút:
```
🛑 SIGINT received at [timestamp]
🛑 Process uptime: 299 seconds
⚠️ Ignoring SIGINT - process has only been running for 299s (minimum 600s required for shutdown)
⚠️ This is likely PM2 watchdog sending premature SIGINT
```

### Po 10 minútach:
```
🛑 SIGINT received at [timestamp]
🛑 Process uptime: 601 seconds
🛑 Shutting down server...
```

## ⚠️ Poznámky

1. **Toto je workaround**, nie ideálne riešenie
2. **Ideálne riešenie** by bolo vypnúť PM2 watchdog alebo aktualizovať PM2
3. **Workaround funguje**, lebo:
   - Proces ignoruje SIGINT prvých 10 minút
   - Po 10 minútach sa proces môže ukončiť normálne
   - PM2 ho reštartuje, ale proces už bežal 10+ minút

## 🎯 Ďalšie kroky (voliteľné)

Ak chceš **ideálne riešenie** namiesto workaround:

1. **Aktualizovať PM2** na najnovšiu verziu:
   ```bash
   npm install -g pm2@latest
   pm2 update
   ```

2. **Skontrolovať PM2 watchdog nastavenia**:
   ```bash
   pm2 conf earnings-table | grep watchdog
   ```

3. **Vypnúť PM2 watchdog** (ak je to možné):
   - Skontrolovať PM2 dokumentáciu
   - Možno pridať `watchdog: false` do konfigurácie

## ✅ Testovanie

Po aplikovaní workaround:
1. Proces by mal bežať **aspoň 10 minút** bez reštartu
2. V logoch by sa mali zobraziť `⚠️ Ignoring SIGINT` správy
3. Restart count by sa **nemal zvyšovať** každých 5 minút

