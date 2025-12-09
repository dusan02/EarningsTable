# 🚀 Rýchly návod na aplikáciu CronExecutionLog migrácie

## Problém
Prisma zistila drift v databáze (databáza nie je v sync s migračnými súbormi).

## Riešenie

### Možnosť 1: Označiť migráciu ako aplikovanú (ak už tabuľka existuje)

```powershell
cd modules/database
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
npx prisma migrate resolve --applied 20250127000000_add_cron_execution_log
npx prisma generate
```

### Možnosť 2: Aplikovať SQL manuálne

1. Otvoriť databázu v Prisma Studio:
```powershell
cd modules/database
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
npx prisma studio
```

2. V Prisma Studio → Database → Run SQL:
```sql
CREATE TABLE IF NOT EXISTS "cron_execution_log" (
    "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    "jobType" TEXT NOT NULL,
    "status" TEXT NOT NULL,
    "startedAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "completedAt" DATETIME,
    "duration" INTEGER,
    "recordsProcessed" INTEGER,
    "errorMessage" TEXT,
    "createdAt" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "cron_execution_log_jobType_startedAt_idx" ON "cron_execution_log"("jobType", "startedAt");
CREATE INDEX IF NOT EXISTS "cron_execution_log_startedAt_idx" ON "cron_execution_log"("startedAt");
CREATE INDEX IF NOT EXISTS "cron_execution_log_status_idx" ON "cron_execution_log"("status");
```

3. Označiť migráciu ako aplikovanú:
```powershell
npx prisma migrate resolve --applied 20250127000000_add_cron_execution_log
npx prisma generate
```

### Možnosť 3: Reset databázy (STRATÍŠ DÁTA!)

```powershell
cd modules/database
$env:DATABASE_URL = "file:D:\Projects\EarningsTable\modules\database\prisma\dev.db"
npx prisma migrate reset
# Potom všetky migrácie sa aplikujú automaticky
```

---

## Odporúčanie

**Pre development:** Možnosť 2 (manuálne SQL) - bezpečné, nestratíš dáta
**Pre produkciu:** Použiť `prisma migrate deploy` na SSH serveri

---

## Overenie

Po aplikácii skontrolovať:
```powershell
npx prisma studio
# Skontrolovať, či tabuľka cron_execution_log existuje
```

