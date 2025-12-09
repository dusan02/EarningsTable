# 📊 Jeden príkaz na kontrolu všetkých logov

## 🚀 Jeden príkaz - všetko naraz

```bash
cd /srv/EarningsTable && echo "=== 1. Last 20 logs from CronExecutionLog ===" && sqlite3 modules/database/prisma/prod.db "SELECT id, jobType, status, datetime(startedAt, 'localtime') as startedAt, datetime(completedAt, 'localtime') as completedAt, duration, recordsProcessed, errorMessage FROM cron_execution_log ORDER BY startedAt DESC LIMIT 20;" && echo "" && echo "=== 2. Current CronStatus ===" && sqlite3 -header -column modules/database/prisma/prod.db "SELECT jobType, datetime(lastRunAt, 'localtime') as lastRunAt, status, recordsProcessed, errorMessage FROM cron_status ORDER BY lastRunAt DESC;" && echo "" && echo "=== 3. Logs count by type (last 24h) ===" && sqlite3 -header -column modules/database/prisma/prod.db "SELECT jobType, status, COUNT(*) as count FROM cron_execution_log WHERE startedAt > datetime('now', '-1 day') GROUP BY jobType, status ORDER BY jobType, status;" && echo "" && echo "=== 4. Recent logs count (last 1 hour) ===" && sqlite3 modules/database/prisma/prod.db "SELECT COUNT(*) as recent_logs FROM cron_execution_log WHERE startedAt > datetime('now', '-1 hour');" && echo "" && echo "=== 5. Latest log entry ===" && sqlite3 -header -column modules/database/prisma/prod.db "SELECT id, jobType, status, datetime(startedAt, 'localtime') as startedAt, datetime(completedAt, 'localtime') as completedAt, duration, recordsProcessed FROM cron_execution_log ORDER BY startedAt DESC LIMIT 1;"
```

## 📋 Čo príkaz zobrazí

1. **Posledných 20 logov** z `CronExecutionLog`
2. **Aktuálny stav** všetkých cron jobov
3. **Počet logov** podľa typu (posledných 24 hodín)
4. **Počet nedávnych logov** (posledná 1 hodina)
5. **Najnovší log** entry

## 🎯 Výsledok

Príkaz zobrazí všetky informácie naraz, takže uvidíš:
- ✅ Či sa logy zapisujú do databázy
- ✅ Kedy naposledy bežali cron joby
- ✅ Koľko logov sa zapísalo za poslednú hodinu/deň
- ✅ Najnovšie logy s detailmi

