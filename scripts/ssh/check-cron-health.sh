#!/bin/bash
# Kompletný health check pre cron systém
# Použitie: ./check-cron-health.sh

echo "=========================================="
echo "📊 1. PM2 Status - Všetky procesy"
echo "=========================================="
pm2 list

echo ""
echo "=========================================="
echo "📋 2. Earnings-cron Detailný Status"
echo "=========================================="
pm2 status earnings-cron

echo ""
echo "=========================================="
echo "📝 3. Posledných 50 riadkov z logov (stdout)"
echo "=========================================="
pm2 logs earnings-cron --lines 50 --nostream | tail -50

echo ""
echo "=========================================="
echo "❌ 4. Posledných 30 riadkov z error logov"
echo "=========================================="
pm2 logs earnings-cron --err --lines 30 --nostream | tail -30

echo ""
echo "=========================================="
echo "🔍 5. Kľúčové správy (plánovanie, reset, pipeline)"
echo "=========================================="
pm2 logs earnings-cron --lines 500 --nostream | grep -i "scheduled\|daily clear\|pipeline\|tick\|boot guard\|starting\|done\|valid" | tail -30

echo ""
echo "=========================================="
echo "⏰ 6. Posledné cron ticky"
echo "=========================================="
pm2 logs earnings-cron --lines 200 --nostream | grep -i "tick\|CRON" | tail -10

echo ""
echo "=========================================="
echo "🔄 7. Posledné pipeline behy"
echo "=========================================="
pm2 logs earnings-cron --lines 200 --nostream | grep -i "pipeline\|starting\|completed" | tail -10

echo ""
echo "=========================================="
echo "💾 8. Overenie ukladania dát"
echo "=========================================="
pm2 logs earnings-cron --lines 200 --nostream | grep -i "finhubdata\|saving\|upserting\|saved\|stored\|final report" | tail -15

echo ""
echo "=========================================="
echo "🛡️ 9. Boot Guard správy"
echo "=========================================="
pm2 logs earnings-cron --lines 200 --nostream | grep -i "boot guard" | tail -10

echo ""
echo "=========================================="
echo "📊 10. Overenie dát v databáze"
echo "=========================================="
cd /var/www/earnings-table/modules/cron
npx tsx -e "
import('./src/core/DatabaseManager.js').then(async ({ db }) => {
  const finhub = await db.getFinhubData();
  const polygon = await db.getPolygonData();
  const final = await db.getFinalReport();
  const withLogos = final.filter(r => r.logoUrl).length;
  console.log('📊 FinhubData:', finhub.length, 'záznamov');
  console.log('📊 PolygonData:', polygon.length, 'záznamov');
  console.log('📊 FinalReport:', final.length, 'záznamov');
  console.log('🖼️  FinalReport s logami:', withLogos, 'z', final.length);
  await db.disconnect();
}).catch(e => {
  console.error('❌ Error:', e.message);
  process.exit(1);
});
" 2>/dev/null || echo "⚠️  Nepodarilo sa pripojiť k databáze"

echo ""
echo "=========================================="
echo "✅ Health Check Complete"
echo "=========================================="







