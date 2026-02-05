#!/bin/bash
# Skript na zistenie, ktorý Git repozitár sa používa na SSH serveri

echo "=== Hľadanie Git repozitárov ==="
echo ""

# Možné cesty k projektu
PATHS=(
    "/var/www/earnings-table"
    "/var/www/EarningsTable"
    "/srv/EarningsTable"
    "/home/*/earnings-table"
    "/opt/earnings-table"
)

FOUND=false

for path in "${PATHS[@]}"; do
    # Expand glob patterns
    for expanded_path in $path; do
        if [ -d "$expanded_path" ] && [ -d "$expanded_path/.git" ]; then
            echo "✅ Nájdený Git repozitár: $expanded_path"
            cd "$expanded_path"
            echo ""
            echo "📋 Git Remote:"
            git remote -v
            echo ""
            echo "📌 Aktuálny branch:"
            git branch --show-current
            echo ""
            echo "📝 Posledný commit:"
            git log -1 --oneline
            echo ""
            echo "🔄 Git status:"
            git status --short
            echo ""
            FOUND=true
        fi
    done
done

if [ "$FOUND" = false ]; then
    echo "❌ Nenašiel sa žiadny Git repozitár v štandardných cestách"
    echo ""
    echo "🔍 Hľadanie všetkých .git priečinkov..."
    find /var/www /home /opt /srv -name ".git" -type d 2>/dev/null | head -10
fi



