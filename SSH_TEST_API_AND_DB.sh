#!/bin/bash
# Test API endpoint and database on SSH

cd /srv/EarningsTable && echo "=== 1. Test API endpoint ===" && curl -s http://localhost:5555/api/final-report | jq -r '.success, .count, .data[0:3] | {symbol, name, marketCap}' 2>/dev/null || curl -s http://localhost:5555/api/final-report | head -50 && echo "" && echo "=== 2. Check database directly ===" && sqlite3 -header -column modules/database/prisma/prod.db "SELECT COUNT(*) as total FROM final_report;" && echo "" && echo "=== 3. Sample data from database ===" && sqlite3 -header -column modules/database/prisma/prod.db "SELECT symbol, name, marketCap, price, change FROM final_report ORDER BY marketCap DESC LIMIT 5;" && echo "" && echo "=== 4. Check if server is running ===" && systemctl is-active earnings-table && echo "" && echo "=== 5. Check server logs (last 10 lines) ===" && journalctl -u earnings-table -n 10 --no-pager | tail -5

