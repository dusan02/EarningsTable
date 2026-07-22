#!/bin/bash
# 🔧 Riešenie divergent branches na SSH serveri
# Spustiť: bash SSH_FIX_DIVERGENT_BRANCHES.sh

echo "🔧 Riešenie divergent branches..."

cd /var/www/earnings-table

# 1. Nastaviť merge stratégiu
echo "📋 Nastavenie merge stratégie..."
git config pull.rebase false

# 2. Stiahnuť a zlúčiť zmeny
echo "📥 Stiahnutie a zlučovanie zmien..."
git pull origin main --no-rebase

# 3. Ak sú konflikty, Git ti ukáže ktoré súbory
# V tom prípade ich musíš manuálne vyriešiť a potom:
# git add .
# git commit -m "Merge: Resolve conflicts"

# 4. Nastaviť skripty ako spustiteľné
echo "🔧 Nastavenie skriptov ako spustiteľných..."
chmod +x quick-pull-and-restart.sh upload-data-to-git.sh 2>/dev/null || echo "Skripty ešte nie sú stiahnuté"

echo "✅ Hotovo! Teraz môžeš použiť:"
echo "   ./quick-pull-and-restart.sh"
echo "   ./upload-data-to-git.sh 'Popis'"

