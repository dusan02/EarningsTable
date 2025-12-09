#!/bin/bash
# 🔧 Automatické riešenie git pull problému a reštartov

cd /srv/EarningsTable || exit 1

echo "=========================================="
echo "📦 1. Vyriešenie git problému"
echo "=========================================="
echo "Skúsim stash všetkých lokálnych zmien..."

# Stash všetkých zmien (vrátane untracked súborov)
git stash push -u -m "Stash all changes before pull" 2>/dev/null || echo "Žiadne zmeny na stash"

# Ak stash zlyhal, skús odstrániť problematické súbory
if [ $? -ne 0 ] || [ -f "modules/web/public/logos/ATON.webp" ] || [ -f "modules/web/public/logos/JANL.webp" ]; then
    echo "Odstraňujem problematické logo súbory..."
    rm -f modules/web/public/logos/ATON.webp modules/web/public/logos/JANL.webp 2>/dev/null || true
fi

echo ""
echo "=========================================="
echo "⬇️ 2. Pull nový kód z GitHub"
echo "=========================================="
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Git pull stále zlyhal!"
    echo "Skúsim reset a pull..."
    git reset --hard HEAD
    git pull origin main
fi

echo ""
echo "=========================================="
echo "🔄 3. Reštartovať earnings-table"
echo "=========================================="
pm2 restart earnings-table

echo ""
echo "=========================================="
echo "⏳ 4. Čakám 5 sekúnd..."
echo "=========================================="
sleep 5

echo ""
echo "=========================================="
echo "📊 5. Kontrola logov"
echo "=========================================="
pm2 logs earnings-table --lines 50 --nostream | tail -50

echo ""
echo "=========================================="
echo "🔍 6. Hľadanie keep-alive a exit eventov"
echo "=========================================="
pm2 logs earnings-table --lines 200 --nostream | grep -iE "keep-alive|beforeExit|exit|Shutting down" | tail -20

echo ""
echo "=========================================="
echo "📈 7. Aktuálny status"
echo "=========================================="
pm2 show earnings-table | grep -E "restarts|uptime|status"

echo ""
echo "=========================================="
echo "✅ Hotovo!"
echo "=========================================="
echo ""
echo "💡 Tip: Sleduj reštarty počas nasledujúcich 10 minút:"
echo "   watch -n 30 'pm2 show earnings-table | grep restarts'"

