# 🔍 SSH Príkazy na kontrolu logov v produkcii

## 🚀 Rýchle príkazy (kopíruj a vlož do SSH)

### 1. Základný status
```bash
pm2 list
pm2 status earnings-cron
pm2 status earnings-table
```

### 2. Posledných 50 riadkov z logov
```bash
# Cron logy (stdout)
pm2 logs earnings-cron --lines 50 --nostream --out

# Cron chyby (stderr)
pm2 logs earnings-cron --lines 50 --nostream --err

# Web server logy
pm2 logs earnings-table --lines 50 --nostream
```

### 3. Sledovanie v reálnom čase
```bash
# Všetky logy
pm2 logs

# Len cron
pm2 logs earnings-cron

# Len chyby
pm2 logs --err
```

### 4. Hľadanie konkrétnych správ
```bash
# Hľadanie "pipeline"
pm2 logs earnings-cron --lines 500 --nostream | grep -i "pipeline"

# Hľadanie chýb
pm2 logs earnings-cron --lines 500 --nostream | grep -i "error\|failed\|❌"

# Hľadanie "Daily clear"
pm2 logs earnings-cron --lines 500 --nostream | grep -i "daily clear"

# Hľadanie cron tickov
pm2 logs earnings-cron --lines 200 --nostream | grep -i "tick\|CRON"
```

### 5. Lokácia log súborov
```bash
# Zobrazenie log súborov
ls -lh ~/.pm2/logs/

# Veľkosť log súborov
du -h ~/.pm2/logs/earnings-*

# Čítanie priamo zo súboru
tail -100 ~/.pm2/logs/earnings-cron-out.log
tail -100 ~/.pm2/logs/earnings-cron-error.log
```

## 📋 Kompletná analýza (spustenie skriptov)

### 1. Rýchla kontrola
```bash
cd /srv/EarningsTable
chmod +x quick-check-logs.sh
./quick-check-logs.sh
```

### 2. Kompletná kontrola logov
```bash
cd /srv/EarningsTable
chmod +x check-production-logs.sh
./check-production-logs.sh
```

### 3. Analýza správania logov
```bash
cd /srv/EarningsTable
chmod +x analyze-logging-behavior.sh
./analyze-logging-behavior.sh
```

## 🔍 Špecifické prípady použitia

### Zistiť posledných 10 pipeline behov
```bash
pm2 logs earnings-cron --lines 500 --nostream | grep -iE "pipeline.*starting|pipeline.*completed" | tail -10
```

### Zistiť počet chýb za posledných 24h
```bash
pm2 logs earnings-cron --lines 5000 --nostream | grep -i "error\|failed\|❌" | wc -l
```

### Zistiť posledných 10 daily clear operácií
```bash
pm2 logs earnings-cron --lines 2000 --nostream | grep -i "daily clear" | tail -10
```

### Zistiť Finnhub fetch operácie
```bash
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "finnhub|fetching|earnings"
```

### Zistiť Polygon fetch operácie
```bash
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "polygon|market cap"
```

### Zistiť Database operácie
```bash
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "upsert|saving|database"
```

### Zistiť Logo operácie
```bash
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "logo|🖼️"
```

## 📊 Štatistiky

### Počet riadkov v logoch
```bash
pm2 logs earnings-cron --lines 1000 --nostream | wc -l
pm2 logs earnings-table --lines 1000 --nostream | wc -l
```

### Typy log správ (emoji)
```bash
pm2 logs earnings-cron --lines 200 --nostream | grep -oE "[📊📥💾🔄✅❌⏱️🚀🧹🖼️📈]" | sort | uniq -c
```

### Kľúčové slová
```bash
pm2 logs earnings-cron --lines 200 --nostream | grep -oE "(Starting|completed|failed|error|pipeline|tick|Daily clear)" -i | sort | uniq -c
```

## 🛠️ PM2 príkazy pre správu logov

```bash
# Vymazanie logov (POZOR!)
pm2 flush

# Vymazanie logov len pre konkrétny proces
pm2 flush earnings-cron

# Reload logov
pm2 reloadLogs

# Restart procesu
pm2 restart earnings-cron
pm2 restart earnings-table
```

## 💡 Tipy

1. **Pre väčšie množstvo dát**: Zvýšte `--lines` (napr. `--lines 5000`)
2. **Pre uloženie do súboru**: `pm2 logs earnings-cron --lines 1000 --nostream > /tmp/cron-logs.txt`
3. **Pre kombináciu stdout a stderr**: Odstráňte `--out` alebo `--err` flagy
4. **Pre sledovanie v reálnom čase**: Použite `pm2 logs` bez `--nostream`

## 📁 Štruktúra logov

PM2 ukladá logy do:
- `~/.pm2/logs/earnings-cron-out.log` - stdout z cron
- `~/.pm2/logs/earnings-cron-error.log` - stderr z cron
- `~/.pm2/logs/earnings-table-out.log` - stdout z web servera
- `~/.pm2/logs/earnings-table-error.log` - stderr z web servera

## 🔧 Riešenie problémov

### Ak sa nezobrazujú nové logy
```bash
pm2 reloadLogs
pm2 restart earnings-cron
```

### Ak sú logy príliš veľké
```bash
# Zobrazenie veľkosti
du -h ~/.pm2/logs/earnings-*

# Vymazanie (ak je to potrebné)
pm2 flush earnings-cron
```

### Ak chcete vidieť len chyby
```bash
pm2 logs earnings-cron --err --lines 100
```

