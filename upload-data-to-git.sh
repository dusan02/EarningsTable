#!/bin/bash
# 📤 Upload dát z SSH servera na GitHub
# Použitie: ./upload-data-to-git.sh "Popis zmien"

set -e

COMMIT_MESSAGE="${1:-Update: Production data sync $(date +%Y-%m-%d\ %H:%M:%S)}"

echo "📤 Upload dát na GitHub..."

# Farba pre výstup
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Konfigurácia
PROJECT_DIR="/var/www/earnings-table"

# 1. Prejsť do projektu
cd "$PROJECT_DIR" || {
    echo -e "${RED}❌ Chyba: Priečinok $PROJECT_DIR neexistuje${NC}"
    exit 1
}

# 2. Skontrolovať Git status
echo -e "${YELLOW}📋 Kontrola Git statusu...${NC}"
git status

# 3. Pridať zmeny (bez databáz a env súborov)
echo -e "${YELLOW}📦 Pridávanie zmien...${NC}"
git add .

# 4. Skontrolovať, čo sa pridá
echo -e "${YELLOW}📝 Zmeny na commitnutie:${NC}"
git status --short

# 5. Commit
echo -e "${YELLOW}💾 Commit zmien...${NC}"
if git commit -m "$COMMIT_MESSAGE"; then
    echo -e "${GREEN}✅ Zmeny commitnuté${NC}"
else
    echo -e "${YELLOW}⚠️  Žiadne zmeny na commitnutie${NC}"
    exit 0
fi

# 6. Push na GitHub
echo -e "${YELLOW}📤 Push na GitHub...${NC}"
if git push origin main; then
    echo -e "${GREEN}✅ Zmeny pushnuté na GitHub${NC}"
else
    echo -e "${RED}❌ Chyba pri pushnutí${NC}"
    exit 1
fi

# 7. Zobraziť posledný commit
echo -e "${YELLOW}📝 Posledný commit:${NC}"
git log --oneline -1

echo -e "${GREEN}✅ Hotovo!${NC}"

