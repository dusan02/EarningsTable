# 📜 Analýza histórie a riešenie problémov

## 🔍 Čo sa stalo v histórii

### 1. Zmena "Market Cap" → "MKT CAP" (frontend)
- **Kde**: `simple-dashboard.html` riadok 609, 1374
- **Zmena**: Názov stĺpca z "Market Cap" na "MKT CAP" (kratší názov)
- **Status**: ✅ Hotovo

### 2. Debug logovanie problém
- **Problém**: Debug logy sa nezobrazovali v PM2 logoch
- **Riešenie**: Zmena z `console.error` na `process.stderr.write` (obchádza PM2 buffering)
- **Lokácia**: `simple-server.js` riadky 512-545
- **Status**: ✅ Implementované (vidíme debug logy v error logoch)

### 3. Prisma databázové problémy
- **Problém**: Po upload na produkciu boli problémy s Prisma
- **Zistenia z `DEBUG_STATUS.md`**:
  - Dve rôzne databázy (`/srv/EarningsTable` vs `/var/www/earnings-table`)
  - Prisma client nie je správne vygenerovaný
  - MarketCap hodnoty boli `null` v produkčnej databáze

### 4. Proces sa začal reštartovať
- **Kedy**: Po Prisma problémoch
- **Symptóm**: `🛑 Shutting down server...` v logoch
- **Príčina**: Proces dostáva SIGINT a ukončuje sa

## 🚨 Aktuálny stav

### Z SSH výstupu vidím:
1. ✅ **Debug logy sa zobrazujú** - vidíme ich v error logoch:
   ```
   🔍 DEBUG: Got data from DB, count: 31
   📊 Total records: 31, with marketCap: 27
   📊 First 5 symbols after sorting
   ```

2. ❌ **Proces sa reštartuje** - vidíme `🛑 Shutting down server...`
   - Reštarty: 3351
   - Proces dostáva SIGINT

3. ❌ **Git pull zlyhal** - logo súbory blokujú pull
   - `ATON.webp` - lokálne zmeny
   - `JANL.webp` - untracked súbor

4. ❌ **Nový kód nie je na serveri** - keep-alive fix nie je aplikovaný

## 🔧 Riešenie

### Krok 1: Vyriešiť git pull problém

```bash
cd /srv/EarningsTable

# Možnosť A: Stash všetky lokálne zmeny
git stash

# Možnosť B: Odstrániť problematické súbory (ak nie sú dôležité)
rm modules/web/public/logos/ATON.webp modules/web/public/logos/JANL.webp 2>/dev/null || true

# Možnosť C: Commit lokálne zmeny
git add modules/web/public/logos/ATON.webp modules/web/public/logos/JANL.webp
git commit -m "Update logo files"
```

### Krok 2: Pull nový kód s keep-alive fixom

```bash
git pull origin main
```

### Krok 3: Reštartovať s novým kódom

```bash
pm2 restart earnings-table
sleep 5
pm2 logs earnings-table --lines 100 --nostream | tail -50
```

### Krok 4: Skontrolovať, či keep-alive funguje

```bash
# Hľadať keep-alive v logoch
pm2 logs earnings-table --lines 200 --nostream | grep -iE "keep-alive|beforeExit|exit" | tail -20

# Sledovať reštarty
watch -n 30 'pm2 show earnings-table | grep restarts'
```

## 📊 Čo sa zmenilo v kóde

### 1. Debug logovanie (`simple-server.js`)
- **PRED**: `console.error()` - nezobrazovalo sa v PM2 logoch
- **PO**: `process.stderr.write()` - zobrazuje sa v error logoch ✅

### 2. Keep-alive mechanizmus (`simple-server.js`)
- **PRED**: Žiadny keep-alive - proces sa ukončoval
- **PO**: `setInterval` každých 60s + `beforeExit`/`exit` logovanie ✅

### 3. Syntetické testy (`synthetic-tests.ts`)
- **PRED**: `console.error()` - PM2 to detekovalo ako chybu
- **PO**: `console.log()` - len warning, nie error ✅

## 🎯 Očakávané výsledky po oprave

1. ✅ Git pull úspešný
2. ✅ Nový kód s keep-alive na serveri
3. ✅ Menej reštartov (alebo žiadne)
4. ✅ Debug logy sa zobrazujú (už funguje)
5. ✅ Proces zostane nažive (keep-alive)

## 📝 Príkazy na SSH (kompletný postup)

```bash
cd /srv/EarningsTable

# 1. Vyriešiť git problém
git stash
# alebo
rm modules/web/public/logos/ATON.webp modules/web/public/logos/JANL.webp 2>/dev/null || true

# 2. Pull nový kód
git pull origin main

# 3. Reštartovať
pm2 restart earnings-table

# 4. Počkať a skontrolovať
sleep 5
pm2 logs earnings-table --lines 100 --nostream | tail -50

# 5. Skontrolovať keep-alive
pm2 logs earnings-table --lines 200 --nostream | grep -iE "keep-alive|beforeExit|exit" | tail -20

# 6. Sledovať reštarty (po 5-10 min)
pm2 show earnings-table | grep restarts
```

## 🔍 Zistenia z histórie

1. **Debug logy fungujú** - vidíme ich v error logoch (to je správne)
2. **Problém je v reštartoch** - proces dostáva SIGINT
3. **Nový kód nie je na serveri** - git pull zlyhal
4. **Keep-alive fix by mal pomôcť** - ale musí byť na serveri

## ⚠️ Dôležité

- Debug logy v stderr sú **správne** - to je úmyselné (obchádza PM2 buffering)
- Problém nie je v logovaní, ale v **reštartoch procesu**
- Keep-alive fix by mal vyriešiť reštarty, ale musí byť najprv na serveri

