# SSH Príkazy - Deploy Detailného Logovania

## ⚠️ DÔLEŽITÉ
Tieto príkazy sú určené pre **SSH server (Linux)**, nie pre lokálny Windows PowerShell!

---

## Krok 1: Prihlásiť sa na SSH server
```bash
ssh root@bardusa
# alebo akýkoľvek iný spôsob prihlásenia na tvoj server
```

## Krok 2: Spustiť príkazy na SSH serveri

### Rýchla verzia (skopíruj a vlož):
```bash
cd /srv/EarningsTable && \
git stash push -u -m "Stash before pull" 2>/dev/null || true && \
git pull origin main && \
git log --oneline -1 && \
pm2 restart earnings-table && \
sleep 5 && \
pm2 logs earnings-table --lines 100 --nostream | tail -50
```

### Alebo krok po kroku:
```bash
# 1. Prejsť do adresára
cd /srv/EarningsTable

# 2. Stash lokálne zmeny (ak sú)
git stash push -u -m "Stash before pull" 2>/dev/null || true

# 3. Pullnúť nový kód
git pull origin main

# 4. Overiť, že nový commit je tam
git log --oneline -1
# Mala by byť vidieť: "Add detailed logging for SIGINT and process exit events..."

# 5. Reštartovať proces
pm2 restart earnings-table

# 6. Počkať 5 sekúnd
sleep 5

# 7. Skontrolovať logy
pm2 logs earnings-table --lines 100 --nostream | tail -50
```

---

## Krok 3: Sledovať detailné logy v reálnom čase

### Sledovať všetky dôležité eventy:
```bash
pm2 logs earnings-table --lines 0 2>&1 | grep -iE "SIGINT|beforeExit|exit|Keep-alive|heartbeat|Stack trace|uptime|Memory"
```

### Sledovať všetky logy (bez filtrovania):
```bash
pm2 logs earnings-table --lines 0
```

### Skontrolovať stderr pre detailné logy:
```bash
pm2 logs earnings-table --err --lines 200 --nostream | grep -iE "SIGINT|beforeExit|exit|Stack trace|uptime|Memory" | tail -30
```

---

## Čo hľadať v logoch

Po úspešnom deploy by sme mali vidieť:

### ✅ Pri štarte:
- `✅ Keep-alive mechanism initialized`
- `🚀 API Server running on port 5555`

### 🛑 Ak sa SIGINT spustí:
- `🛑 SIGINT received at [timestamp]`
- `🛑 Stack trace: [stack trace]` ← **KĽÚČOVÉ!** Ukáže, kto volá SIGINT
- `🛑 Process uptime: [seconds]` ← Ako dlho bežal pred ukončením
- `🛑 Memory usage: [object]` ← Memory usage v momente SIGINT
- `🛑 Shutting down server...`

### ⚠️ Ak sa proces pokúša ukončiť inak:
- `⚠️ Process beforeExit event: [code]`
- `⚠️ Stack trace: [stack trace]`
- `⚠️ Active handles: [count]`
- `⚠️ Active requests: [count]`

### 💓 Keep-alive heartbeat (každých 5 minút):
- `💓 Keep-alive heartbeat: [timestamp], uptime: [seconds]s`

---

## Diagnostika

### Ak nevidíš žiadne nové logy:
1. Over, že nový commit je na serveri: `git log --oneline -1`
2. Over, že proces bol reštartovaný: `pm2 list`
3. Skús reštartovať znova: `pm2 restart earnings-table`

### Ak proces stále končí bez SIGINT logov:
- Možno sa proces ukončuje inak (nie cez SIGINT)
- Skontroluj `beforeExit` a `exit` eventy v logoch

---

## Poznámky

- **Stack trace** je najdôležitejší - ukáže presný zdroj problému
- **Keep-alive heartbeat** sa zobrazuje každých 5 minút (ak proces beží aspoň 5 minút)
- Ak proces končí skôr ako za 5 minút, neuvidíme heartbeat

