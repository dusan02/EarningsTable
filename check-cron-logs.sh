#!/bin/bash
# Script na kontrolu logov a stavu cronov

echo "=========================================="
echo "📊 PM2 Status"
echo "=========================================="
pm2 list

echo ""
echo "=========================================="
echo "📋 Earnings-cron Status"
echo "=========================================="
pm2 status earnings-cron

echo ""
echo "=========================================="
echo "📝 Posledných 50 riadkov z logov (out)"
echo "=========================================="
pm2 logs earnings-cron --lines 50 --nostream | tail -50

echo ""
echo "=========================================="
echo "❌ Posledných 30 riadkov z error logov"
echo "=========================================="
pm2 logs earnings-cron --err --lines 30 --nostream | tail -30

echo ""
echo "=========================================="
echo "🔍 Hľadanie kľúčových správ"
echo "=========================================="
echo "Hľadám 'Daily clear', 'pipeline', 'tick', 'scheduled'..."
pm2 logs earnings-cron --lines 200 --nostream | grep -i "daily clear\|pipeline\|tick\|scheduled\|✅\|❌" | tail -20

echo ""
echo "=========================================="
echo "⏰ Posledné cron ticky"
echo "=========================================="
pm2 logs earnings-cron --lines 100 --nostream | grep -i "tick\|CRON" | tail -10

echo ""
echo "=========================================="
echo "🔄 Posledné pipeline behy"
echo "=========================================="
pm2 logs earnings-cron --lines 100 --nostream | grep -i "pipeline\|starting\|completed" | tail -10

