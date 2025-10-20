# Cron Schedule Update

## Nový časový rozvrh cron jobs

### Nastavenie podľa požiadaviek:

1. **07:00** - Mazanie všetkých dát z databázy
2. **07:05** - Prvá iterácia behu cron jobs
3. **Každých 5 minút** - Ďalšie iterácie až do **06:50** nasledujúceho dňa
4. **Víkendy** - Cron jobs nebežia

### Technické detaily:

#### Cron Expressions:

- **Mazanie dát**: `0 7 * * 1-5` (07:00 každý pracovný deň)
- **Cron jobs**: `*/5 * * * 1-5` (každých 5 minút, len pracovné dni)

#### Logika:

- **07:00-07:04**: Preskočenie behu cron jobs (čas na mazanie dát)
- **07:05-06:55**: Bežanie cron jobs každých 5 minút
- **Víkendy**: Automatické preskočenie (sobota, nedeľa)
- **Sviatky**: Preskočenie NYSE sviatkov

#### Implementácia:

- Použitý `clear-all-data.js` skript pre mazanie dát
- Redis mutex pre zabránenie prekrývajúcich sa behov
- Podmienky na kontrolu času a dňa v týždni
- Timezone: `America/New_York`

### Súbory upravené:

- `modules/cron/src/cron-scheduler.ts` - hlavný scheduler
- Pridaná funkcia `clearAllData()` pre mazanie dát
- Upravená logika naplánovania s časovými podmienkami

### Testovanie:

- ✅ TypeScript kompilácia bez chýb
- ✅ Test behu s `--once` flagom
- ✅ Správne naplánovanie cron expressions

### Spustenie:

```bash
cd modules/cron
npm start
```

### Environment variables:

- `TZ=America/New_York` (default)
- `USE_REDIS_LOCK=false` (default)
- `SKIP_RESET_CHECK=false` (default)
