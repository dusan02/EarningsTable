# 🔍 Rýchle príkazy na kontrolu logov v produkcii

## 📋 Základné príkazy

### 1. Status všetkých PM2 procesov
```bash
pm2 list
```

### 2. Status konkrétnych procesov
```bash
pm2 status earnings-table
pm2 status earnings-cron
```

### 3. Posledných N riadkov z logov (stdout)
```bash
# Web server - posledných 50 riadkov
pm2 logs earnings-table --lines 50 --nostream --out

# Cron jobs - posledných 50 riadkov
pm2 logs earnings-cron --lines 50 --nostream --out
```

### 4. Posledných N riadkov z error logov (stderr)
```bash
# Web server - chyby
pm2 logs earnings-table --lines 50 --nostream --err

# Cron jobs - chyby
pm2 logs earnings-cron --lines 50 --nostream --err
```

### 5. Sledovanie logov v reálnom čase
```bash
# Všetky logy
pm2 logs

# Len web server
pm2 logs earnings-table

# Len cron jobs
pm2 logs earnings-cron

# Len chyby
pm2 logs --err
```

## 🔍 Špecifické vyhľadávania

### Hľadanie kľúčových slov
```bash
# V cron logoch - hľadanie "pipeline"
pm2 logs earnings-cron --lines 500 --nostream | grep -i "pipeline"

# Hľadanie chýb
pm2 logs earnings-cron --lines 500 --nostream | grep -i "error\|failed\|❌"

# Hľadanie úspešných operácií
pm2 logs earnings-cron --lines 500 --nostream | grep -i "✅\|success\|completed"

# Hľadanie "Daily clear"
pm2 logs earnings-cron --lines 500 --nostream | grep -i "daily clear"

# Hľadanie cron tickov
pm2 logs earnings-cron --lines 200 --nostream | grep -i "tick\|CRON\|⏱️"
```

### Finnhub operácie
```bash
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "finnhub|fetching|earnings|📥|📊"
```

### Polygon operácie
```bash
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "polygon|market cap|📈"
```

### Database operácie
```bash
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "upsert|saving|database|💾|✓"
```

### Logo operácie
```bash
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "logo|🖼️"
```

## 📁 Lokácia log súborov

### Zobrazenie log súborov
```bash
# Zobrazenie všetkých log súborov
ls -lh ~/.pm2/logs/

# Zobrazenie veľkosti log súborov
du -h ~/.pm2/logs/earnings-*

# Čítanie log súboru priamo
tail -100 ~/.pm2/logs/earnings-cron-out.log
tail -100 ~/.pm2/logs/earnings-cron-error.log
tail -100 ~/.pm2/logs/earnings-table-out.log
tail -100 ~/.pm2/logs/earnings-table-error.log
```

## 🕐 Časové filtre

### Posledných 10 minút (ak logy obsahujú timestampy)
```bash
pm2 logs earnings-cron --lines 1000 --nostream | tail -100
```

### Dnes (ak máte timestampy v logoch)
```bash
TODAY=$(date +%Y-%m-%d)
pm2 logs earnings-cron --lines 5000 --nostream | grep "$TODAY"
```

## 📊 Komplexné analýzy

### Počet chýb za posledných 24h
```bash
pm2 logs earnings-cron --lines 5000 --nostream | grep -i "error\|failed\|❌" | wc -l
```

### Posledných 20 pipeline behov
```bash
pm2 logs earnings-cron --lines 1000 --nostream | grep -iE "pipeline.*starting|pipeline.*completed" | tail -20
```

### Posledných 10 daily clear operácií
```bash
pm2 logs earnings-cron --lines 2000 --nostream | grep -i "daily clear" | tail -10
```

## 🚀 Použitie kompletného skriptu

```bash
cd /srv/EarningsTable
chmod +x check-production-logs.sh
./check-production-logs.sh
```

## 💡 Tipy

1. **Pre sledovanie v reálnom čase**: Použite `pm2 logs` bez `--nostream`
2. **Pre väčšie množstvo dát**: Zvýšte `--lines` parameter (napr. `--lines 5000`)
3. **Pre kombináciu stdout a stderr**: Odstráňte `--out` alebo `--err` flagy
4. **Pre uloženie do súboru**: `pm2 logs earnings-cron --lines 1000 --nostream > /tmp/cron-logs.txt`

## 🔧 PM2 príkazy pre správu logov

```bash
# Vymazanie logov (POZOR: stratíte históriu!)
pm2 flush

# Vymazanie logov len pre konkrétny proces
pm2 flush earnings-cron

# Reload logov (ak sa nezobrazujú nové)
pm2 reloadLogs
```

