#!/bin/bash
# Kontrola progressu po opravách

echo "═══════════════════════════════════════════════════════════"
echo "KONTROLA PROGRESSU PO OPRAVÁCH"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "1. Status earnings-cron service:"
systemctl status earnings-cron --no-pager -l | head -15
echo ""
echo "2. Posledných 20 riadkov logov:"
journalctl -u earnings-cron -n 20 --no-pager | tail -15
echo ""
echo "3. Kontrola dátumov v final_report (prvých 5 záznamov):"
sqlite3 -header -column modules/database/prisma/prod.db "SELECT symbol, date(reportDate) as reportDate, datetime(updatedAt, 'localtime') as updatedAt FROM final_report ORDER BY updatedAt DESC LIMIT 5;"
echo ""
echo "4. Kontrola logov v cron_execution_log:"
sqlite3 -header -column modules/database/prisma/prod.db "SELECT jobType, status, datetime(startedAt, 'localtime') as startedAt, recordsProcessed, datetime(completedAt, 'localtime') as completedAt FROM cron_execution_log ORDER BY startedAt DESC LIMIT 5;"
echo ""
echo "5. Kontrola CronStatus:"
sqlite3 -header -column modules/database/prisma/prod.db "SELECT jobType, status, datetime(lastRunAt, 'localtime') as lastRunAt, recordsProcessed FROM cron_status ORDER BY lastRunAt DESC;"
echo ""
echo "6. Počet záznamov v tabuľkách:"
sqlite3 modules/database/prisma/prod.db "SELECT 'finnhub_data' as table_name, COUNT(*) as count FROM finnhub_data UNION ALL SELECT 'polygon_data', COUNT(*) FROM polygon_data UNION ALL SELECT 'final_report', COUNT(*) FROM final_report UNION ALL SELECT 'cron_execution_log', COUNT(*) FROM cron_execution_log;"

