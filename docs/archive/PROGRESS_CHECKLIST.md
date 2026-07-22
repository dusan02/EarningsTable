# 📋 Checklist - Oprava logov firiem na produkcii

## ✅ ČO FUNGUJE

### 1. Systemd migrácia
- [x] `earnings-table` migrovaný na systemd ✅
- [x] `earnings-cron` migrovaný na systemd ✅
- [x] Oba servisy bežia (`active (running)`) ✅
- [x] Žiadne časté reštarty ✅

### 2. Pipeline a cron joby
- [x] Unified pipeline beží každých 5 minút ✅
- [x] Cron ticky sa spúšťajú správne ✅
- [x] Finnhub job sa spúšťa a dokončuje ✅
- [x] Polygon job sa spúšťa a dokončuje ✅
- [x] Pipeline logy sa zobrazujú v systemd ✅

### 3. API a databáza
- [x] API endpoint `/api/final-report` funguje ✅
- [x] API vracia 31 companies ✅
- [x] Databáza má 31 záznamov v každej tabuľke ✅
- [x] Frontend sa správne servuje ✅

### 4. Opravené dátumy
- [x] `reportDate` v `finnhub_data` opravený (31 záznamov) ✅
- [x] `updatedAt` v `final_report` opravený (30 záznamov) ✅

## ❌ ČO EŠTE NEFUNGUJE

### 1. Dátumy v final_report
- [ ] `reportDate` v `final_report` je stále `2000-01-01` ❌
- [ ] `updatedAt` v `final_report` sa vracia na `2000-01-01` po spustení pipeline ❌
- [ ] `generateFinalReport()` prepisuje dátumy späť na staré hodnoty ❌

### 2. Logy do databázy
- [ ] `cron_execution_log` má 0 záznamov ❌
- [ ] Logy sa nezapisujú do histórie ❌
- [ ] `CronStatus.lastRunAt` má placeholder dátum `2000-01-01` ❌

## 🔍 ZISTENÉ PROBLÉMY

### Problém 1: Dátumy sa prepisujú
- **Symptóm**: `reportDate` a `updatedAt` v `final_report` sa vracajú na `2000-01-01`
- **Príčina**: `generateFinalReport()` používa `getRunTimestamps()`, ktorý vracia dátumy, ale možno sa nezapisujú správne
- **Riešenie**: Skontrolovať `generateFinalReport()` a `normalizeFinalReportDates()`

### Problém 2: Logy sa nezapisujú
- **Symptóm**: `cron_execution_log` je prázdny
- **Príčina**: `updateCronStatus()` vyžaduje `startedAt` parameter, ale možno sa neposiela správne
- **Riešenie**: Skontrolovať, či sa `startedAt` posiela pri každom volaní

## 📊 AKTUÁLNY STAV

### Databáza
- `finnhub_data`: 31 záznamov, `reportDate` = `2025-12-09` ✅
- `polygon_data`: 31 záznamov ✅
- `final_report`: 31 záznamov, `reportDate` = `2000-01-01` ❌, `updatedAt` = `2000-01-01` ❌
- `cron_execution_log`: 0 záznamov ❌

### Pipeline
- Spúšťa sa každých 5 minút ✅
- Finnhub job beží ✅
- Polygon job beží ✅
- `generateFinalReport()` beží, ale prepisuje dátumy ❌

## 🎯 ĎALŠIE KROKY

1. **Opraviť `generateFinalReport()`** - zabezpečiť, aby sa dátumy nezapisovali ako `2000-01-01`
   - `getRunTimestamps()` vracia správne dátumy ✅
   - Problém môže byť v `normalizeFinalReportDates()` alebo v Prisma `upsert`
   - **Riešenie**: Pridať explicitné nastavenie `updatedAt` pri `upsert`

2. **Skontrolovať Prisma `@updatedAt`** - pri `upsert` sa `updatedAt` nemusí aktualizovať automaticky
   - **Riešenie**: Pridať `updatedAt: new Date()` do `updateData` v `generateFinalReport()`

3. **Skontrolovať `updateCronStatus()`** - zabezpečiť, aby sa logy zapisovali do `cron_execution_log`
   - `startedAt` sa posiela ✅
   - Problém môže byť v tom, že sa logy nezapisujú pri každom spustení
   - **Riešenie**: Skontrolovať, či sa `updateCronStatus()` volá s `startedAt` pri každom dokončení

4. **Testovať po oprave** - overiť, že sa dátumy aktualizujú pri každom spustení pipeline

## 📈 PROGRES

- **Systemd migrácia**: 100% ✅
- **Pipeline fungovanie**: 90% ✅ (beží, ale prepisuje dátumy)
- **Dátumy v databáze**: 50% ⚠️ (finnhub_data OK, final_report NOK)
- **Logy do databázy**: 0% ❌

**Celkový progres: ~60%** ⚠️

## 🔧 PRIORITNÉ OPRAVY

### 1. ✅ OPRAVENÉ: `updatedAt` v `generateFinalReport()`
- Pridané explicitné nastavenie `updatedAt: new Date()` do `updateData`
- **Status**: Opravené v kóde, čaká na deploy

### 2. ⚠️ ČAKÁ: Skontrolovať, prečo sa `reportDate` prepisuje
- `reportDateISO` z `getRunTimestamps()` by mal byť správny
- Možno problém v `normalizeFinalReportDates()` alebo Prisma `upsert`
- **Potrebné**: Testovať po deploy opravy `updatedAt`

### 3. ⚠️ ČAKÁ: Opraviť logy do `cron_execution_log`
- `updateCronStatus()` sa volá s `startedAt` ✅
- Problém môže byť v tom, že sa logy nezapisujú pri každom dokončení
- **Potrebné**: Skontrolovať logy po ďalšom spustení pipeline

## 📝 ZMENY V KÓDE

### Opravené:
1. `modules/cron/src/core/DatabaseManager.ts` - pridané `updatedAt: new Date()` do `updateData` v `generateFinalReport()`

### Čaká na testovanie:
- Po deploy by sa `updatedAt` mal aktualizovať pri každom spustení pipeline
- `reportDate` by sa mal aktualizovať z `getRunTimestamps()`

