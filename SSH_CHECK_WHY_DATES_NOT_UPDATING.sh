#!/bin/bash
# Diagnostika prečo sa dátumy neaktualizujú

echo "═══════════════════════════════════════════════════════════"
echo "DIAGNOSTIKA: PREČO SA DÁTUMY NEAKTUALIZUJÚ"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "1. Kontrola finnhub_data - reportDate (mali by byť správne):"
echo "───────────────────────────────────────────────────────────"
sqlite3 -header -column modules/database/prisma/prod.db "SELECT symbol, date(reportDate) as reportDate, datetime(updatedAt, 'localtime') as updatedAt FROM finnhub_data ORDER BY updatedAt DESC LIMIT 5;"
echo ""
echo "2. Kontrola raw hodnoty reportDate v final_report:"
echo "───────────────────────────────────────────────────────────"
sqlite3 -header -column modules/database/prisma/prod.db "SELECT symbol, reportDate, typeof(reportDate) as type, datetime(reportDate, 'localtime') as reportDate_parsed FROM final_report LIMIT 3;"
echo ""
echo "3. Kontrola či pipeline bežal po vymazaní (posledných 20 min):"
echo "───────────────────────────────────────────────────────────"
journalctl -u earnings-cron --since "20 minutes ago" --no-pager | grep -E "Pipeline|generateFinalReport|reportDate|Generating FinalReport" | tail -15
echo ""
echo "4. Kontrola warning logov o dátumoch:"
echo "───────────────────────────────────────────────────────────"
journalctl -u earnings-cron --since "20 minutes ago" --no-pager | grep -i "warning.*reportDate\|reportDate.*2000" | tail -10
echo ""
echo "5. Test - manuálne nastavenie reportDate na aktuálny dátum:"
echo "───────────────────────────────────────────────────────────"
sqlite3 modules/database/prisma/prod.db "UPDATE final_report SET reportDate = date('now'), updatedAt = datetime('now') WHERE symbol = 'ALZN';" && sqlite3 -header -column modules/database/prisma/prod.db "SELECT symbol, date(reportDate) as reportDate, datetime(updatedAt, 'localtime') as updatedAt FROM final_report WHERE symbol = 'ALZN';"
echo ""
echo "═══════════════════════════════════════════════════════════"

