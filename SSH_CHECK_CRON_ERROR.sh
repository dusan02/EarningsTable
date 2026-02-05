#!/bin/bash
# Kontrola chyby v earnings-cron service

echo "═══════════════════════════════════════════════════════════"
echo "DIAGNOSTIKA CHYBY V EARNINGS-CRON SERVICE"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "1. Status service:"
systemctl status earnings-cron --no-pager -l | head -30
echo ""
echo "2. Posledných 50 riadkov logov:"
journalctl -u earnings-cron -n 50 --no-pager
echo ""
echo "3. Kontrola Prisma client:"
cd /srv/EarningsTable/modules/cron && ls -la node_modules/.prisma/client 2>&1 | head -5
echo ""
echo "4. Kontrola Prisma schema:"
cd /srv/EarningsTable/modules/database && ls -la prisma/schema.prisma 2>&1
echo ""
echo "5. Testovanie Prisma generate:"
cd /srv/EarningsTable/modules/database && npx prisma generate 2>&1 | tail -20

