#!/bin/bash
# 🔬 Analýza správania logov v produkcii
# Tento skript zisťuje ako, kde a čo sa loguje

cd /srv/EarningsTable || cd /var/www/earnings-table || exit 1

echo "=========================================="
echo "📊 1. PM2 Konfigurácia a Log Paths"
echo "=========================================="
echo "PM2 logy sú uložené v: ~/.pm2/logs/"
echo ""
echo "Dostupné log súbory:"
ls -lh ~/.pm2/logs/ 2>/dev/null | grep earnings || echo "Žiadne log súbory"

echo ""
echo "=========================================="
echo "📁 2. Veľkosť log súborov"
echo "=========================================="
du -h ~/.pm2/logs/earnings-* 2>/dev/null | sort -h || echo "Žiadne log súbory"

echo ""
echo "=========================================="
echo "🕐 3. Posledné aktivity (timestampy)"
echo "=========================================="
echo "Cron - posledných 5 riadkov:"
pm2 logs earnings-cron --lines 5 --nostream | tail -5

echo ""
echo "Web Server - posledných 5 riadkov:"
pm2 logs earnings-table --lines 5 --nostream | tail -5

echo ""
echo "=========================================="
echo "📝 4. Typy log správ v Cron"
echo "=========================================="
echo "Emoji a formátovanie:"
pm2 logs earnings-cron --lines 200 --nostream | grep -oE "[📊📥💾🔄✅❌⏱️🚀🧹🖼️📈]" | sort | uniq -c

echo ""
echo "Kľúčové slová:"
pm2 logs earnings-cron --lines 200 --nostream | grep -oE "(Starting|completed|failed|error|pipeline|tick|Daily clear)" -i | sort | uniq -c

echo ""
echo "=========================================="
echo "🔄 5. Frekvencia pipeline behov"
echo "=========================================="
echo "Počet pipeline behov v posledných 500 riadkoch:"
pm2 logs earnings-cron --lines 500 --nostream | grep -i "pipeline" | wc -l

echo ""
echo "Posledných 10 pipeline behov:"
pm2 logs earnings-cron --lines 500 --nostream | grep -iE "pipeline.*starting|pipeline.*completed" | tail -10

echo ""
echo "=========================================="
echo "⏰ 6. Cron tick frekvencia"
echo "=========================================="
echo "Počet tickov v posledných 200 riadkoch:"
pm2 logs earnings-cron --lines 200 --nostream | grep -iE "tick|CRON|⏱️" | wc -l

echo ""
echo "Posledných 10 tickov:"
pm2 logs earnings-cron --lines 200 --nostream | grep -iE "tick|CRON|⏱️" | tail -10

echo ""
echo "=========================================="
echo "❌ 7. Chyby a ich frekvencia"
echo "=========================================="
echo "Počet chýb v posledných 1000 riadkoch:"
pm2 logs earnings-cron --lines 1000 --nostream | grep -iE "error|failed|❌|exception" | wc -l

echo ""
echo "Posledných 10 chýb:"
pm2 logs earnings-cron --lines 1000 --nostream | grep -iE "error|failed|❌|exception" | tail -10

echo ""
echo "Chyby v Web Server:"
pm2 logs earnings-table --lines 500 --nostream | grep -iE "error|failed|❌|exception|500" | tail -10

echo ""
echo "=========================================="
echo "📊 8. Typy operácií v logoch"
echo "=========================================="
echo "Finnhub operácie:"
pm2 logs earnings-cron --lines 500 --nostream | grep -i "finnhub" | wc -l

echo "Polygon operácie:"
pm2 logs earnings-cron --lines 500 --nostream | grep -i "polygon" | wc -l

echo "Database operácie (upsert/save):"
pm2 logs earnings-cron --lines 500 --nostream | grep -iE "upsert|saving|database" | wc -l

echo "Logo operácie:"
pm2 logs earnings-cron --lines 500 --nostream | grep -i "logo" | wc -l

echo ""
echo "=========================================="
echo "🧹 9. Daily Clear operácie"
echo "=========================================="
echo "Počet daily clear operácií v posledných 2000 riadkoch:"
pm2 logs earnings-cron --lines 2000 --nostream | grep -i "daily clear" | wc -l

echo ""
echo "Posledných 5 daily clear operácií:"
pm2 logs earnings-cron --lines 2000 --nostream | grep -i "daily clear" | tail -5

echo ""
echo "=========================================="
echo "📈 10. Rast logov (posledných 24h)"
echo "=========================================="
echo "Veľkosť log súborov:"
du -h ~/.pm2/logs/earnings-* 2>/dev/null

echo ""
echo "Počet riadkov v posledných 1000 logoch:"
pm2 logs earnings-cron --lines 1000 --nostream | wc -l
pm2 logs earnings-table --lines 1000 --nostream | wc -l

echo ""
echo "=========================================="
echo "✅ Analýza dokončená"
echo "=========================================="
echo ""
echo "💡 Tip: Pre detailnejšiu analýzu použite:"
echo "   ./check-production-logs.sh"
echo ""
echo "💡 Tip: Pre sledovanie v reálnom čase:"
echo "   pm2 logs earnings-cron"

