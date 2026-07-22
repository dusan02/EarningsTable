# 🕐 Cron Schedule Update - 3h → 0.5h Window

## 📋 **Zhrnutie zmien**

Zmenil som časové okno medzi daily clearom a prvým cronom z **3 hodín** na **0.5 hodiny** (30 minút → 5 minút).

## 🔄 **Pred zmenou**

```
03:00 NY - Daily clear
03:30 NY - Prvý cron (30-minútové okno)
04:00 NY - Pravidelné crony každých 5 min
```

## ✅ **Po zmene**

```
03:00 NY - Daily clear
03:05 NY - Prvý cron (5-minútové okno)
03:10 NY - Pravidelné crony každých 5 min
```

## 📊 **Konkrétne zmeny**

### **1. Early Slot Cron**

- **Pred**: `30,35,40,45,50,55 3 * * 1-5` (03:30-03:55)
- **Po**: `5,10,15,20,25,30,35,40,45,50,55 3 * * 1-5` (03:05-03:55)

### **2. Boot Guard**

- **Pred**: 03:00-03:30 → plánuje na 03:30
- **Po**: 03:00-03:05 → plánuje na 03:05

### **3. Late Boot Guard**

- **Pred**: 03:30-03:35 → spustí ihneď
- **Po**: 03:05-03:10 → spustí ihneď

## 🎯 **Výhody**

### **1. Rýchlejšie obnovenie dát**

- Dáta sa začnú načítavať už o 03:05 namiesto 03:30
- 25 minút skoršie dostupnosť dát

### **2. Menšie riziko duplicitných dát**

- Kratšie okno medzi clearom a prvým cronom
- Menej času na akumuláciu starých dát

### **3. Lepšia spoľahlivosť**

- Boot guard sa spustí skôr po reštarte
- Rýchlejšie zotavenie po výpadku

## 🛡️ **Bezpečnostné opatrenia**

### **1. Environment Validation**

- Pridaná validácia env variables pri štarte
- Lepšie error handling pri chýbajúcich konfiguráciách

### **2. Boot Guard Recovery**

- Automatické zotavenie po reštarte
- Inteligentné plánovanie podľa aktuálneho času

### **3. Error Handling**

- Centralizované error handling
- Graceful degradation pri failures

## 📈 **Monitoring**

### **1. Status Command**

```bash
npm run cron status
```

Zobrazuje:

- Aktuálny stav cron jobov
- Časové rozvrhy
- Environment validation status

### **2. List Command**

```bash
npm run cron list
```

Zobrazuje:

- Všetky dostupné cron joby
- Časové rozvrhy
- Boot guard systém

## 🚀 **Nasadenie**

### **1. Lokálne testovanie**

```bash
cd modules/cron
npm run start:once
```

### **2. Produkčné nasadenie**

```bash
pm2 delete earnings-cron
pm2 start ecosystem.config.js --only earnings-cron --env production
pm2 save
```

### **3. Okamžité spustenie**

```bash
pm2 start ecosystem.config.js --only earnings-cron --env production -- --force --once
```

## 🔍 **Overenie**

### **1. Skontrolovať logy**

```bash
pm2 logs earnings-cron --lines 50
```

### **2. Skontrolovať status**

```bash
npm run cron status
```

### **3. Testovať API**

```bash
curl -sS https://www.earningstable.com/api/final-report | head -c 200
```

## ⚠️ **Poznámky**

1. **Timezone**: Všetky časy sú v America/New_York
2. **Pracovné dni**: Crony bežia len Po-Pi (1-5)
3. **Boot guard**: Automaticky sa spustí po reštarte v okne 03:00-03:10
4. **Environment**: Povinné env variables sa validujú pri štarte

## 📞 **Podpora**

Ak sa vyskytnú problémy:

1. Skontrolujte env variables
2. Skontrolujte PM2 logy
3. Spustite `npm run cron status`
4. Testujte s `--once` flagom
