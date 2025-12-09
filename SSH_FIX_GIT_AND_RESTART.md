# 🔧 Riešenie git pull problému a reštartov

## 🚨 Problém identifikovaný

V SSH výstupe vidím:
1. **Git pull zlyhal** kvôli lokálnym zmenám:
   ```
   error: Your local changes to the following files would be overwritten by merge:
           modules/web/public/logos/ATON.webp
   error: The following untracked working tree files would be overwritten by merge:
           modules/web/public/logos/JANL.webp
   ```

2. **Proces dostáva SIGINT** a ukončuje sa:
   ```
   🛑 Shutting down server...
   ```

3. **Proces sa reštartuje** s **starým kódom** (bez keep-alive fixu)

## ✅ Riešenie

### Krok 1: Vyriešiť git pull problém

```bash
cd /srv/EarningsTable

# Stash lokálne zmeny v logo súboroch
git stash push modules/web/public/logos/ATON.webp modules/web/public/logos/JANL.webp -m "Stash logo changes before pull"

# Alebo ak chceš zachovať zmeny:
# git add modules/web/public/logos/ATON.webp modules/web/public/logos/JANL.webp
# git commit -m "Update logo files"

# Teraz pull
git pull origin main
```

### Krok 2: Reštartovať s novým kódom

```bash
# Reštartovať earnings-table s novým kódom
pm2 restart earnings-table

# Počkať 5 sekúnd
sleep 5

# Skontrolovať logy
pm2 logs earnings-table --lines 50 --nostream
```

### Krok 3: Sledovať, či sa reštarty znížili

```bash
# Počkaj 5-10 minút a skontroluj
pm2 show earnings-table | grep restarts

# Sledovať v reálnom čase
pm2 logs earnings-table --err
```

## 🔍 Zistiť, kto posiela SIGINT

Ak sa proces stále ukončuje, potrebujeme zistiť, kto posiela SIGINT:

```bash
# Sledovať procesy, ktoré môžu posielať signály
ps aux | grep -E "pm2|node|earnings"

# Skontrolovať PM2 konfiguráciu
cat ecosystem.config.js | grep -A 10 "earnings-table"

# Skontrolovať, či nie je nejaký health check alebo monitor
pm2 list
pm2 describe earnings-table
```

## 📝 Kompletný postup na SSH

```bash
cd /srv/EarningsTable

# 1. Stash logo zmeny
git stash push modules/web/public/logos/ATON.webp modules/web/public/logos/JANL.webp -m "Stash before pull"

# 2. Pull nový kód
git pull origin main

# 3. Reštartovať
pm2 restart earnings-table

# 4. Počkať a skontrolovať
sleep 5
pm2 logs earnings-table --lines 100 --nostream | tail -50

# 5. Skontrolovať, či nový kód beží (mal by byť keep-alive)
pm2 logs earnings-table --lines 200 --nostream | grep -iE "keep-alive|beforeExit|exit" | tail -20
```

## 🎯 Očakávané výsledky

Po aplikovaní fixu by malo byť:
- ✅ Git pull úspešný
- ✅ Nový kód s keep-alive mechanizmom
- ✅ Menej reštartov (alebo žiadne)
- ✅ V logoch by sa nemali objaviť `🛑 Shutting down server...` správy (okrem manuálneho reštartu)

## ⚠️ Ak sa problém opakuje

Ak sa proces stále ukončuje po aplikovaní fixu:

1. **Skontrolovať PM2 konfiguráciu** - možno je tam nejaký automatický reštart
2. **Skontrolovať systémové logy** - možno niekto iný posiela signály
3. **Pridať viac logovania** - aby sme videli, kedy a prečo sa proces ukončuje

