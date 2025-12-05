# 🔧 Rýchla referencia - Oprava dát na produkcii

## 🚀 Rýchle príkazy

### Diagnostika problému
```bash
cd /var/www/earnings-table
./fix-production-data.sh diagnose
```

### Resetovanie databázy (vymaže všetky dáta)
```bash
cd /var/www/earnings-table
./fix-production-data.sh reset-db
```

### Resetovanie cronu (reštart)
```bash
cd /var/www/earnings-table
./fix-production-data.sh reset-cron
```

### Manuálne spustenie pipeline
```bash
cd /var/www/earnings-table
./fix-production-data.sh force-run
```

### Kompletný reset (všetko naraz)
```bash
cd /var/www/earnings-table
./fix-production-data.sh all
```

## 📋 Typický workflow pri probléme s dátami

### 1. Diagnostika
```bash
./fix-production-data.sh diagnose
```
**Kontroluje:**
- PM2 status
- Cron logy
- Dáta v databáze
- Environment premenné

### 2. Ak sú dáta prázdne alebo staré

**Možnosť A: Len reštartovať cron**
```bash
./fix-production-data.sh reset-cron
```

**Možnosť B: Vymazať databázu a nechať cron znovu naplniť**
```bash
./fix-production-data.sh reset-db
# Počkajte 5-10 minút, kým cron naplní dáta
```

**Možnosť C: Kompletný reset (najrýchlejšie)**
```bash
./fix-production-data.sh all
```

### 3. Overenie
```bash
./fix-production-data.sh diagnose
```

## 🔍 Manuálne kontroly

### PM2 Status
```bash
pm2 status
pm2 describe earnings-cron
```

### Logy
```bash
# Posledných 50 riadkov
pm2 logs earnings-cron --lines 50 --nostream

# Realtime logy (stlač Ctrl+C pre ukončenie)
pm2 logs earnings-cron

# Len chyby
pm2 logs earnings-cron --err --lines 30 --nostream
```

### Kontrola dát v databáze
```bash
cd /var/www/earnings-table/modules/cron
npx tsx -e "
import('./src/core/DatabaseManager.js').then(async ({ db }) => {
  const final = await db.getFinalReport();
  console.log('FinalReport záznamov:', final.length);
  if (final.length > 0) {
    console.log('Prvý záznam:', JSON.stringify(final[0], null, 2));
  }
  await db.disconnect();
});
"
```

## ⚠️ Časté problémy

### 1. Cron nebeží
```bash
pm2 restart earnings-cron
pm2 logs earnings-cron --lines 20
```

### 2. Dáta sa neaktualizujú
```bash
# Skontrolujte, či cron beží
pm2 status earnings-cron

# Skontrolujte logy pre chyby
pm2 logs earnings-cron --err --lines 50 --nostream

# Ak nie sú chyby, vymažte databázu a nechajte cron znovu naplniť
./fix-production-data.sh reset-db
```

### 3. API vracia prázdne dáta
```bash
# Skontrolujte FinalReport tabuľku
./fix-production-data.sh diagnose

# Ak je prázdna, resetujte
./fix-production-data.sh all
```

### 4. Cron sa zasekol
```bash
# Reštart
pm2 restart earnings-cron

# Ak to nepomôže, kompletný reset
./fix-production-data.sh all
```

## 📝 Poznámky

- **Cron beží každých 5 minút** a naplňuje dáta
- **Reset databázy** sa deje automaticky každý deň o **07:00 NY time**
- **Po resetovaní databázy** počkajte 5-10 minút, kým cron naplní dáta
- **ALLOW_CLEAR=true** musí byť v .env pre resetovanie databázy

## 🔗 Súvisiace skripty

- `check-cron-status.sh` - Rýchla kontrola
- `check-cron-health.sh` - Kompletný health check
- `check-cron-running.sh` - Kontrola behu cronu

