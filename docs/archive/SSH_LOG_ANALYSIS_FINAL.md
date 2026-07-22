# 📊 Finálna analýza logov z SSH - Kritické zistenia

## 🚨 KRITICKÉ PROBLÉMY

### 1. **earnings-table: 3348 reštartov, uptime len 113s**
- **Problém**: Proces sa reštartuje každých ~2 minúty
- **Príčina**: V error logoch **NIE SÚ ŽIADNE CHYBY** - proces sa ukončuje bez chýb
- **Možné príčiny**:
  - PM2 detekuje proces ako "failed" kvôli nejakej inej príčine
  - Proces sa ukončuje "gracefully" (exit code 0), ale PM2 ho reštartuje
  - Možno timeout alebo memory limit (ale memory usage je OK: 14.26 MiB, 77.88%)

### 2. **earnings-cron: 199 reštartov, uptime len 113s**
- **Problém**: Proces sa tiež často reštartuje
- **Príčina**: Syntetické testy zlyhávajú → logujú do stderr → proces dostáva SIGINT → ukončuje sa
- **Pattern v logoch**:
  ```
  🚨 SYNTHETIC TESTS FAILED
  ↩️ SIGINT received
  ⚠️ exit: 0
  ```

### 3. **SYNTHETIC TESTS FAILED - Logovanie do stderr**
- **Problém**: Syntetické testy logujú "FAILED" do `stderr` (cez `console.error()`)
- **Lokácia**: `modules/cron/src/jobs/synthetic-tests.ts:61`
- **Kód**:
  ```typescript
  if (suite.overallStatus === 'FAIL') {
    console.error('🚨 SYNTHETIC TESTS FAILED - Immediate attention required!');
  }
  ```
- **Dôsledok**: PM2 môže detekovať error logy ako "failure" a reštartovať proces

## 📋 ZISTENIA Z LOGOV

### earnings-table:
- ✅ **Memory usage OK**: 14.26 MiB, 77.88% heap usage
- ✅ **HTTP latency OK**: P95 = 24ms, Mean = 7ms
- ✅ **Event loop OK**: Latency = 0.30ms
- ❌ **Reštarty**: 3348 (KRITICKÉ!)
- ❌ **Uptime**: Len 113s (proces sa reštartuje každých ~2 min)
- ❌ **Error logy**: Prázdne (žiadne chyby, ale proces sa reštartuje)

### earnings-cron:
- ✅ **Pipeline beží**: Každých 5 minút
- ✅ **Syntetické testy v stdout**: PASS (v stdout logoch)
- ❌ **Syntetické testy v stderr**: FAILED (v error logoch)
- ❌ **Reštarty**: 199
- ❌ **Uptime**: Len 113s
- ❌ **Pattern**: FAILED → SIGINT → exit

## 🔍 ROOT CAUSE ANALYSIS

### Problém 1: earnings-table reštarty
**Možné príčiny**:
1. PM2 `max_restarts` limit dosiahnutý → ale `unstable_restarts: 0`
2. Proces sa ukončuje "gracefully" (exit 0), ale PM2 ho reštartuje
3. Možno nejaký timeout alebo health check zlyháva
4. Proces možno čaká na niečo, čo sa nikdy nestane → event loop sa vyprazdňuje

**Diagnostika potrebná**:
```bash
# Zistiť, prečo sa proces ukončuje
pm2 logs earnings-table --lines 2000 --nostream | grep -iE "exit|shutdown|graceful|beforeExit" | tail -50

# Skontrolovať PM2 konfiguráciu
pm2 show earnings-table | grep -iE "restart|max_restarts|min_uptime"
```

### Problém 2: earnings-cron reštarty kvôli syntetickým testom
**Príčina**:
1. Syntetické testy bežia každú minútu (`* * * * *`)
2. Testy zlyhávajú → logujú do stderr (`console.error`)
3. PM2 detekuje error logy → možno reštartuje proces
4. Alebo: Proces dostáva SIGINT z nejakého dôvodu po zlyhaní testov

**Riešenie**:
1. **Presunúť logovanie z `console.error` na `console.log`** - syntetické testy by nemali byť "error", len "warning"
2. **Alebo**: Zistiť, prečo testy zlyhávajú a opraviť ich
3. **Alebo**: Vypnúť syntetické testy dočasne, ak spôsobujú reštarty

## 🛠️ ODORÚČANIA

### 1. Okamžité opatrenia

#### A. Zistiť prečo sa earnings-table reštartuje
```bash
# Sledovať v reálnom čase
pm2 logs earnings-table --err

# Zistiť, kedy sa reštartuje
pm2 logs earnings-table --lines 5000 --nostream | grep -B 5 -A 5 "restart\|exit\|SIGINT" | tail -100
```

#### B. Presunúť SYNTHETIC TESTS FAILED z stderr do stdout
**Súbor**: `modules/cron/src/jobs/synthetic-tests.ts:61`
**Zmeniť**:
```typescript
// PRED:
console.error('🚨 SYNTHETIC TESTS FAILED - Immediate attention required!');

// PO:
console.log('⚠️ SYNTHETIC TESTS FAILED - Immediate attention required!');
```

#### C. Zistiť, prečo syntetické testy zlyhávajú
```bash
# Zistiť detailnejšie info o zlyhaní
pm2 logs earnings-cron --lines 1000 --nostream --out | grep -A 20 "Synthetic Tests" | tail -50
```

### 2. Dlhodobé riešenia

1. **Presunúť všetky "warning" logy z stderr do stdout**
2. **Pridať lepšie error handling** - proces by sa nemal ukončovať kvôli zlyhaným testom
3. **Nastaviť PM2 restart policy** - možno je príliš agresívna
4. **Pridať health checks** - aby PM2 vedel, že proces je OK aj keď testy zlyhávajú

## 📝 PRÍKAZY NA SSH PRE ĎALŠIU DIAGNOSTIKU

### 1. Zistiť prečo sa earnings-table reštartuje
```bash
# Sledovať v reálnom čase
pm2 logs earnings-table --err

# Zistiť pattern reštartov
pm2 logs earnings-table --lines 5000 --nostream | grep -iE "restart|exit|SIGINT|SIGTERM|beforeExit|shutdown" | tail -100

# Skontrolovať PM2 konfiguráciu
pm2 show earnings-table
```

### 2. Zistiť detailnejšie info o syntetických testoch
```bash
# Zistiť, ktoré testy zlyhávajú
pm2 logs earnings-cron --lines 1000 --nostream --out | grep -A 30 "Synthetic Tests" | tail -100

# Zistiť, kedy sa testy spúšťajú
pm2 logs earnings-cron --lines 2000 --nostream --out | grep -i "synthetic\|🧪" | tail -50
```

### 3. Sledovať reštarty v reálnom čase
```bash
# Monitorovať oba procesy
pm2 monit

# Alebo sledovať logy
pm2 logs
```

## 🎯 PRIORITY

1. **KRITICKÉ**: Zistiť prečo sa earnings-table reštartuje (3348 reštartov!)
2. **VYSOKÉ**: Presunúť SYNTHETIC TESTS FAILED z stderr do stdout
3. **STREDNÉ**: Zistiť, prečo syntetické testy zlyhávajú
4. **NÍZKE**: Optimalizovať PM2 restart policy

## 📊 SÚHRN

- **earnings-table**: Reštartuje sa bez chýb v logoch (3348x)
- **earnings-cron**: Reštartuje sa kvôli syntetickým testom (199x)
- **Syntetické testy**: Logujú FAILED do stderr, čo môže spôsobovať reštarty
- **Riešenie**: Presunúť warning logy z stderr do stdout + zistiť root cause reštartov

