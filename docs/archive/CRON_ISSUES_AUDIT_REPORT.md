# 🔍 Audit Report: Cron Jobs, Scheduling & Logging Issues

## 📋 Prehľad

Analýza cron jobs, rozvrhu a ukladania logov pre **23h denný nonstop beh**.

---

## ❌ KRITICKÉ PROBLÉMY

### 1. **Cron rozvrh NIE JE 23h nonstop**

**Problém:**
```typescript
// modules/cron/src/main.ts:346
const UNIFIED_CRON = '*/5 * * * *';  // Každých 5 min, 24/7
```

**Ale:**
- Daily clear je `0 3 * * 1-5` (len Po-Pi, nie víkendy!)
- Preskakuje sa 03:00 (minute === 0)
- **Víkendy sa crony NESPUŠŤAJÚ** - len Po-Pi!

**Riešenie:**
```typescript
// Zmeniť na 7 dní v týždni:
const DAILY_CLEAR_CRON = '0 3 * * *';  // Každý deň, nie len 1-5
```

---

### 2. **Daily Cycle Manager má konfliktné rozvrhy**

**Problém:**
```typescript
// modules/cron/src/daily-cycle-manager.ts:56
cron.schedule('10,15,20,25,30,35,40,45,50,55 3-23 * * *', ...)
cron.schedule('*/5 0-2 * * *', ...)
```

**Problémy:**
- **Gap medzi 23:55 a 00:00** - 5 minútová medzera!
- **Gap medzi 02:55 a 03:00** - 5 minútová medzera!
- Daily Cycle Manager sa **NEPOUŽÍVA** (main.ts používa unified cron)

**Riešenie:**
- Odstrániť Daily Cycle Manager alebo ho integrovať
- Unified cron by mal byť skutočne 24/7

---

### 3. **Chýbajúce logy v CronStatus tabuľke**

**Problém:**
```typescript
// modules/cron/src/main.ts:235
try { 
  await db.updateCronStatus('pipeline', 'error', 0, (e as any)?.message || String(e)); 
} catch {}  // ❌ Tichý catch - log sa stratí!
```

**Problémy:**
- Tichý catch - ak updateCronStatus zlyhá, nič sa nezaloguje
- Performance monitor sa ukladá len pri úspechu
- Chýba historický log - len posledný stav

**Riešenie:**
```typescript
try { 
  await db.updateCronStatus('pipeline', 'error', 0, error.message); 
} catch (logError) {
  console.error('❌ Failed to save cron status:', logError);
  // Fallback: uložiť do súboru alebo iný mechanizmus
}
```

---

### 4. **Memory leak v PerformanceMonitor**

**Problém:**
```typescript
// modules/cron/src/performance-monitor.ts:35
private snapshots: PerformanceSnapshot[] = [];
private readonly maxSnapshots = 100;
```

**Problémy:**
- Snapshots sa ukladajú do pamäte, nikdy sa nečistia z DB
- Po 23h behu môže byť 100+ snapshots v pamäti
- `saveToDatabase()` ukladá len posledný snapshot, nie históriu

**Riešenie:**
- Pridať historickú tabuľku pre performance logy
- Alebo čistiť staré snapshots pravidelne

---

### 5. **Quiet Window môže blokovať beh**

**Problém:**
```typescript
// modules/cron/src/main.ts:193
function isInQuietWindow(): boolean {
  const inWindow = Date.now() < __quietWindowUntil;
  if (inWindow) {
    console.log(`🕊️ Quiet window active — skipping tick`);
  }
  return inWindow;
}
```

**Problémy:**
- Ak sa proces reštartuje počas quiet window, môže preskočiť beh
- Quiet window sa nastavuje len po daily clear, ale ak clear zlyhá, window zostane

**Riešenie:**
- Resetovať quiet window pri reštarte
- Logovať, keď sa preskakuje kvôli quiet window

---

### 6. **Boot Guard má časové okno len 03:00-03:10**

**Problém:**
```typescript
// modules/cron/src/main.ts:300
const inWindow_03_00_to_03_05 = (nyHour === 3 && (nyMinute < 5 || ...));
const inWindow_03_05_to_03_10 = (nyHour === 3 && nyMinute >= 5 && nyMinute < 10);
```

**Problémy:**
- Ak sa proces reštartuje po 03:10, boot guard nefunguje
- Ak sa reštartuje medzi 03:00-03:05, čaká na 03:05 (môže byť príliš neskoro)

**Riešenie:**
- Rozšíriť boot guard okno na 03:00-03:30
- Alebo spustiť pipeline hneď, ak je po 03:05

---

### 7. **Chýba historický log behov**

**Problém:**
- `CronStatus` tabuľka má len `lastRunAt` - len posledný beh
- Nie je historický záznam všetkých behov
- Nemožno sledovať, koľkokrát zlyhalo za deň

**Riešenie:**
```sql
-- Pridať novú tabuľku:
model CronExecutionLog {
  id            Int       @id @default(autoincrement())
  jobType       String
  status        String
  startedAt     DateTime
  completedAt   DateTime?
  duration      Int?      // ms
  recordsProcessed Int?
  errorMessage  String?
  createdAt     DateTime  @default(now())
  
  @@index([jobType, startedAt])
  @@map("cron_execution_log")
}
```

---

### 8. **Pipeline timeout je 15 minút, ale cron je každých 5 minút**

**Problém:**
```typescript
// modules/cron/src/main.ts:184
const PIPELINE_TIMEOUT_MS = 15 * 60 * 1000; // 15 minutes
// Ale cron beží každých 5 minút!
```

**Problémy:**
- Ak pipeline trvá 10 minút, ďalší cron sa spustí po 5 minútach
- Môže dôjsť k prekrývaniu behov
- `__pipelineRunning` flag by mal zabrániť, ale ak timeout zlyhá...

**Riešenie:**
- Znížiť timeout na 4 minúty (menej ako 5 min cron interval)
- Alebo zvýšiť cron interval na 10 minút
- Alebo lepšie: dynamický timeout podľa histórie

---

### 9. **Daily clear len Po-Pi, ale crony bežia 24/7**

**Problém:**
```typescript
// modules/cron/src/main.ts:368
const DAILY_CLEAR_CRON = '0 3 * * 1-5';  // Len Po-Pi
// Ale unified cron: '*/5 * * * *' beží 24/7
```

**Problémy:**
- Víkendy sa dáta nečistia, ale crony bežia
- Dáta sa hromadia cez víkend
- V pondelok o 03:00 sa vymažú všetky víkendové dáta

**Riešenie:**
- Zmeniť na `'0 3 * * *'` (každý deň)
- Alebo preskočiť crony cez víkend, ak nechceš dáta

---

### 10. **Chýba monitoring a alerting**

**Problémy:**
- Žiadne upozornenia pri zlyhaní
- Žiadne metriky o úspešnosti behov
- Nemožno zistiť, koľko behov zlyhalo za deň

**Riešenie:**
- Pridať health check endpoint
- Pridať alerting pri viacerých zlyhaniach za sebou
- Pridať dashboard s metrikami

---

## ⚠️ STREDNÉ PROBLÉMY

### 11. **Error handling v updateCronStatus**

**Problém:**
```typescript
// modules/cron/src/jobs/FinnhubCronJob.ts:65
await db.updateCronStatus('finnhub', 'error', undefined, (error as any)?.message || 'Unknown error');
```

**Problémy:**
- `undefined` pre recordsProcessed môže spôsobiť problém
- `(error as any)` - zlá typová kontrola

**Riešenie:**
```typescript
await db.updateCronStatus('finnhub', 'error', 0, error?.message || 'Unknown error');
```

---

### 12. **PerformanceMonitor ukladá len posledný snapshot**

**Problém:**
```typescript
// modules/cron/src/performance-monitor.ts:217
async saveToDatabase(): Promise<void> {
  const latest = this.snapshots[this.snapshots.length - 1];
  // Ukladá len posledný, nie históriu!
}
```

**Riešenie:**
- Ukladať všetky snapshots alebo aspoň posledných 10
- Alebo vytvoriť historickú tabuľku

---

### 13. **Timezone handling môže byť problematický**

**Problém:**
```typescript
// modules/cron/src/main.ts:14
const TZ = process.env.CRON_TZ || 'America/New_York';
```

**Problémy:**
- Ak sa zmení časové pásmo (DST), môže dôjsť k problémom
- `toLocaleString` môže byť nekonzistentný

**Riešenie:**
- Použiť `luxon` alebo `date-fns-tz` pre lepšiu timezone podporu

---

## ✅ ODporúčania

### Prioritné opravy:

1. **Zmeniť daily clear na každý deň** (nie len Po-Pi)
2. **Pridať historický log tabuľku** pre CronExecutionLog
3. **Opraviť quiet window reset** pri reštarte
4. **Znížiť pipeline timeout** na 4 minúty
5. **Rozšíriť boot guard okno** na 03:00-03:30

### Dlouhodobé vylepšenia:

1. **Pridať monitoring dashboard**
2. **Pridať alerting systém**
3. **Optimalizovať performance monitoring**
4. **Pridať health check endpoint**

---

## 📊 Štatistiky

- **Cron jobs:** 2 (unified pipeline + daily clear)
- **Rozvrh:** Každých 5 min (okrem 03:00)
- **Problémy:** 13 identifikovaných
- **Kritické:** 10
- **Stredné:** 3

---

## 🔧 Návrh opráv

Pozri: `CRON_FIXES_IMPLEMENTATION.md` (vytvorím po schválení)

