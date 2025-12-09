# Ďalšie kroky - Monitorovanie SIGINT

## ✅ Aktuálny stav

- ✅ Nový kód je nasadený (`bee8e01`)
- ✅ Proces beží (`earnings-table` je online)
- ✅ Keep-alive mechanism je inicializovaný
- ✅ Server beží na porte 5555
- ✅ Žiadne `ReferenceError` (opravené)

## ⚠️ Problém

Proces má **3478 reštartov**, takže sa stále reštartuje. Teraz máme detailné logovanie, takže keď sa SIGINT spustí, uvidíme **stack trace**, ktorý ukáže, kto/kedy/prečo posiela SIGINT.

---

## 🔍 Monitorovanie v reálnom čase

### Príkaz 1: Sledovať všetky dôležité eventy
```bash
pm2 logs earnings-table --lines 0 2>&1 | grep -iE "SIGINT|beforeExit|exit|Keep-alive|heartbeat|Stack trace|uptime|Memory|Shutting down"
```

**Nechaj to bežať 5-10 minút** a sleduj, kedy sa SIGINT spustí.

### Príkaz 2: Sledovať všetky logy (bez filtrovania)
```bash
pm2 logs earnings-table --lines 0
```

### Príkaz 3: Skontrolovať stderr pre detailné logy
```bash
pm2 logs earnings-table --err --lines 200 --nostream | grep -iE "SIGINT|beforeExit|exit|Stack trace|uptime|Memory" | tail -30
```

---

## 📊 Čo hľadať

### ✅ Keep-alive heartbeat (každých 5 minút)
Ak proces beží aspoň 5 minút, uvidíš:
```
💓 Keep-alive heartbeat: [timestamp], uptime: [seconds]s
```

### 🛑 SIGINT event
Keď sa SIGINT spustí, uvidíš:
```
🛑 SIGINT received at [timestamp]
🛑 Stack trace: [stack trace] ← KĽÚČOVÉ!
🛑 Process uptime: [seconds]
🛑 Memory usage: [object]
🛑 Shutting down server...
```

### ⚠️ Iné exit eventy
Ak sa proces pokúša ukončiť inak:
```
⚠️ Process beforeExit event: [code]
⚠️ Stack trace: [stack trace]
⚠️ Active handles: [count]
⚠️ Active requests: [count]
```

---

## 🎯 Cieľ

**Zistiť z stack trace, kto posiela SIGINT:**
- PM2 interné volania?
- Systemd?
- Cron job?
- Iný proces?

---

## 📝 Príkaz na SSH

Spusti tento príkaz a nechaj ho bežať **5-10 minút**:

```bash
cd /srv/EarningsTable && pm2 logs earnings-table --lines 0 2>&1 | grep -iE "SIGINT|beforeExit|exit|Keep-alive|heartbeat|Stack trace|uptime|Memory|Shutting down"
```

Keď sa SIGINT spustí, uvidíš stack trace. **Pošli mi výstup**, aby som mohol identifikovať zdroj problému.

---

## 🔄 Alternatíva: Skontrolovať po 5 minútach

Ak nechceš sledovať v reálnom čase, spusti:

```bash
# Počkať 5 minút a potom skontrolovať logy
sleep 300 && pm2 logs earnings-table --err --lines 500 --nostream | grep -iE "SIGINT|beforeExit|exit|Stack trace|uptime|Memory|Shutting down" | tail -50
```

---

## 📌 Poznámky

- **Keep-alive heartbeat** sa zobrazuje každých 5 minút (ak proces beží aspoň 5 minút)
- Ak proces končí skôr ako za 5 minút, neuvidíme heartbeat
- **Stack trace** je najdôležitejší - ukáže presný zdroj problému
- Proces má teraz 3478 reštartov, takže sa reštartuje pomerne často

