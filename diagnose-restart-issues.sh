#!/bin/bash
# 🔍 Diagnostika problémov s reštartmi a logmi

echo "=========================================="
echo "📊 1. PM2 Status Detail"
echo "=========================================="
pm2 describe earnings-table
pm2 describe earnings-cron

echo ""
echo "=========================================="
echo "🔄 2. Analýza reštartov - Earnings-Table"
echo "=========================================="
echo "Hľadám príčiny reštartov..."
pm2 logs earnings-table --lines 500 --nostream --err | grep -iE "error|crash|out of memory|killed|signal|restart|exit" | tail -30

echo ""
echo "=========================================="
echo "⏰ 3. Časové stopy reštartov"
echo "=========================================="
pm2 logs earnings-table --lines 1000 --nostream | grep -iE "restart|exit|SIGINT|SIGTERM|beforeExit" | tail -50

echo ""
echo "=========================================="
echo "💾 4. Memory Usage"
echo "=========================================="
pm2 describe earnings-table | grep -i memory
pm2 describe earnings-cron | grep -i memory
free -h

echo ""
echo "=========================================="
echo "🚨 5. SYNTHETIC TESTS FAILED - Detail"
echo "=========================================="
echo "Hľadám kontext okolo SYNTHETIC TESTS FAILED..."
pm2 logs earnings-cron --lines 2000 --nostream --err | grep -B 10 -A 5 "SYNTHETIC TESTS FAILED" | head -100

echo ""
echo "=========================================="
echo "📊 6. Porovnanie stdout vs stderr"
echo "=========================================="
echo "Posledných 20 riadkov z stdout (syntetické testy):"
pm2 logs earnings-cron --lines 50 --nostream --out | grep -i "synthetic\|PASS\|FAIL" | tail -20

echo ""
echo "Posledných 20 riadkov z stderr (syntetické testy):"
pm2 logs earnings-cron --lines 50 --nostream --err | grep -i "synthetic\|PASS\|FAIL" | tail -20

echo ""
echo "=========================================="
echo "🔍 7. Hľadanie patternov v error logoch"
echo "=========================================="
echo "Počet SYNTHETIC TESTS FAILED:"
pm2 logs earnings-cron --lines 10000 --nostream --err | grep -c "SYNTHETIC TESTS FAILED"

echo ""
echo "Počet SIGINT:"
pm2 logs earnings-cron --lines 10000 --nostream --err | grep -c "SIGINT"

echo ""
echo "Počet exit:"
pm2 logs earnings-cron --lines 10000 --nostream --err | grep -c "exit:"

echo ""
echo "=========================================="
echo "📈 8. Posledné pipeline behy"
echo "=========================================="
pm2 logs earnings-cron --lines 500 --nostream --out | grep -iE "pipeline.*starting|pipeline.*completed" | tail -20

echo ""
echo "=========================================="
echo "✅ Diagnostika dokončená"
echo "=========================================="

