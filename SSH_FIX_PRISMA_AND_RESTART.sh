#!/bin/bash
# Oprava Prisma a reštart earnings-cron

cd /srv/EarningsTable && echo "═══════════════════════════════════════════════════════════" && echo "OPRAVA PRISMA A REŠTART EARNINGS-CRON" && echo "═══════════════════════════════════════════════════════════" && echo "" && echo "1. Kontrola Prisma schema:" && ls -la modules/database/prisma/schema.prisma && echo "" && echo "2. Prisma generate:" && cd modules/database && npx prisma generate 2>&1 | tail -30 && echo "" && echo "3. Kontrola Prisma client:" && ls -la node_modules/.prisma/client/index.d.ts 2>&1 | head -3 && echo "" && echo "4. Reštart earnings-cron:" && cd /srv/EarningsTable && systemctl restart earnings-cron && sleep 5 && echo "" && echo "5. Status service:" && systemctl status earnings-cron --no-pager -l | head -25 && echo "" && echo "6. Posledných 30 riadkov logov:" && journalctl -u earnings-cron -n 30 --no-pager | tail -20

