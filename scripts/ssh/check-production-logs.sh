#!/bin/bash
# 🔍 Kompletný skript na kontrolu logov v produkcii
# Použitie: ./check-production-logs.sh [option]

cd /srv/EarningsTable || cd /var/www/earnings-table || exit 1

echo "=========================================="
echo "📊 PM2 Status - Všetky procesy"
echo "=========================================="
pm2 list

echo ""
echo "=========================================="
echo "📋 Earnings-Table (Web Server) Status"
echo "=========================================="
pm2 status earnings-table

echo ""
echo "=========================================="
echo "📋 Earnings-Cron (Cron Jobs) Status"
echo "=========================================="
pm2 status earnings-cron

echo ""
echo "=========================================="
echo "📝 Earnings-Table - Posledných 100 riadkov (stdout)"
echo "=========================================="
pm2 logs earnings-table --lines 100 --nostream --out | tail -100

echo ""
echo "=========================================="
echo "❌ Earnings-Table - Posledných 50 riadkov (stderr)"
echo "=========================================="
pm2 logs earnings-table --lines 50 --nostream --err | tail -50

echo ""
echo "=========================================="
echo "📝 Earnings-Cron - Posledných 100 riadkov (stdout)"
echo "=========================================="
pm2 logs earnings-cron --lines 100 --nostream --out | tail -100

echo ""
echo "=========================================="
echo "❌ Earnings-Cron - Posledných 50 riadkov (stderr)"
echo "=========================================="
pm2 logs earnings-cron --lines 50 --nostream --err | tail -50

echo ""
echo "=========================================="
echo "🔍 Hľadanie kľúčových správ v Cron logoch"
echo "=========================================="
echo "Hľadám: 'Daily clear', 'pipeline', 'tick', 'scheduled', '✅', '❌'..."
pm2 logs earnings-cron --lines 500 --nostream | grep -iE "daily clear|pipeline|tick|scheduled|✅|❌|error|failed" | tail -30

echo ""
echo "=========================================="
echo "⏰ Posledné cron ticky (každých 5 min)"
echo "=========================================="
pm2 logs earnings-cron --lines 200 --nostream | grep -iE "tick|CRON|⏱️" | tail -15

echo ""
echo "=========================================="
echo "🔄 Posledné pipeline behy"
echo "=========================================="
pm2 logs earnings-cron --lines 200 --nostream | grep -iE "pipeline|starting|completed|success|failed" | tail -15

echo ""
echo "=========================================="
echo "🧹 Daily Clear operácie"
echo "=========================================="
pm2 logs earnings-cron --lines 500 --nostream | grep -iE "daily clear|clearing|cleared" | tail -20

echo ""
echo "=========================================="
echo "📊 Finnhub fetch operácie"
echo "=========================================="
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "finnhub|fetching|earnings|📥|📊" | tail -20

echo ""
echo "=========================================="
echo "📈 Polygon fetch operácie"
echo "=========================================="
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "polygon|market cap|📈" | tail -20

echo ""
echo "=========================================="
echo "💾 Database operácie"
echo "=========================================="
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "upsert|saving|database|💾|✓" | tail -20

echo ""
echo "=========================================="
echo "🖼️ Logo operácie"
echo "=========================================="
pm2 logs earnings-cron --lines 300 --nostream | grep -iE "logo|🖼️" | tail -20

echo ""
echo "=========================================="
echo "❌ Všetky chyby v Cron (posledných 24h)"
echo "=========================================="
pm2 logs earnings-cron --lines 1000 --nostream | grep -iE "error|failed|❌|exception" | tail -30

echo ""
echo "=========================================="
echo "❌ Všetky chyby v Web Server (posledných 24h)"
echo "=========================================="
pm2 logs earnings-table --lines 500 --nostream | grep -iE "error|failed|❌|exception|500" | tail -30

echo ""
echo "=========================================="
echo "📁 PM2 Log File Locations"
echo "=========================================="
echo "PM2 logy sú uložené v: ~/.pm2/logs/"
echo ""
ls -lh ~/.pm2/logs/ | grep -E "earnings-table|earnings-cron"

echo ""
echo "=========================================="
echo "📊 Veľkosť log súborov"
echo "=========================================="
du -h ~/.pm2/logs/earnings-* 2>/dev/null || echo "Log súbory nenájdené"

echo ""
echo "=========================================="
echo "🕐 Posledné aktivity (timestampy)"
echo "=========================================="
pm2 logs earnings-cron --lines 50 --nostream | tail -10

echo ""
echo "=========================================="
echo "✅ Kontrola dokončená"
echo "=========================================="

