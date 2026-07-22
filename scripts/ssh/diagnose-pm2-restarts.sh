#!/bin/bash
# 🔍 Kompletná diagnostika PM2 reštartov

echo "=========================================="
echo "📊 1. PM2 Status Detail"
echo "=========================================="
pm2 show earnings-table
pm2 show earnings-cron

echo ""
echo "=========================================="
echo "⚙️ 2. PM2 Konfigurácia (ecosystem.config.js)"
echo "=========================================="
cat ecosystem.config.js | grep -A 20 "earnings-table" || echo "Konfigurácia nenájdená"

echo ""
echo "=========================================="
echo "📝 3. Posledných 100 riadkov z stdout (earnings-table)"
echo "=========================================="
pm2 logs earnings-table --lines 100 --nostream --out | tail -100

echo ""
echo "=========================================="
echo "❌ 4. Posledných 100 riadkov z stderr (earnings-table)"
echo "=========================================="
pm2 logs earnings-table --lines 100 --nostream --err | tail -100

echo ""
echo "=========================================="
echo "🕐 5. Časové stopy - kedy sa proces reštartuje"
echo "=========================================="
echo "Hľadám 'online', 'restart', 'exit' v logoch..."
pm2 logs earnings-table --lines 2000 --nostream | grep -iE "online|restart|exit|starting|stopped" | tail -50

echo ""
echo "=========================================="
echo "💾 6. Memory Usage History"
echo "=========================================="
pm2 monit --no-interaction &
MONIT_PID=$!
sleep 5
kill $MONIT_PID 2>/dev/null
echo "Memory info z pm2 describe:"
pm2 describe earnings-table | grep -i memory

echo ""
echo "=========================================="
echo "🔍 7. System Logs - OOM Killer"
echo "=========================================="
echo "Kontrolujem systémové logy pre OOM killer..."
dmesg | grep -i "oom\|killed\|memory" | tail -20 || echo "Žiadne OOM záznamy"

echo ""
echo "=========================================="
echo "📊 8. PM2 Process Info"
echo "=========================================="
pm2 jlist | jq '.[] | select(.name=="earnings-table") | {name, pm2_env: {restart_time, unstable_restarts, status, pm_uptime, axm_actions, pmx_module}}' 2>/dev/null || echo "jq nie je nainštalovaný"

echo ""
echo "=========================================="
echo "🔄 9. PM2 Restart History"
echo "=========================================="
pm2 logs earnings-table --lines 10000 --nostream | grep -iE "restart|online|offline|stopped" | tail -100

echo ""
echo "=========================================="
echo "📈 10. Uptime vs Restarts"
echo "=========================================="
RESTARTS=$(pm2 jlist | jq '.[] | select(.name=="earnings-table") | .pm2_env.restart_time' 2>/dev/null || echo "N/A")
UPTIME=$(pm2 jlist | jq '.[] | select(.name=="earnings-table") | .pm2_env.pm_uptime' 2>/dev/null || echo "N/A")
echo "Restarts: $RESTARTS"
echo "Uptime: $UPTIME ms"
if [ "$RESTARTS" != "N/A" ] && [ "$UPTIME" != "N/A" ]; then
    AVG_UPTIME=$((UPTIME / (RESTARTS + 1)))
    echo "Priemerný uptime medzi reštartmi: $AVG_UPTIME ms ($(($AVG_UPTIME / 1000)) sekúnd)"
fi

echo ""
echo "=========================================="
echo "✅ Diagnostika dokončená"
echo "=========================================="

