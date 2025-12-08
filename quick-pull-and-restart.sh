#!/bin/bash
# 🔄 Rýchly pull a restart na SSH serveri
# Použitie: ./quick-pull-and-restart.sh

set -e

echo "🔄 Rýchly pull a restart..."

# Farba pre výstup
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Konfigurácia
PROJECT_DIR="/var/www/earnings-table"
SERVICE_NAME="earnings-table"

# 1. Prejsť do projektu
cd "$PROJECT_DIR" || {
    echo -e "${RED}❌ Chyba: Priečinok $PROJECT_DIR neexistuje${NC}"
    exit 1
}

# 2. Skontrolovať Git status
echo -e "${YELLOW}📋 Kontrola Git statusu...${NC}"
git status

# 3. Stiahnuť zmeny z GitHubu
echo -e "${YELLOW}📥 Stiahnutie zmien z GitHubu...${NC}"
if git pull origin main; then
    echo -e "${GREEN}✅ Zmeny stiahnuté${NC}"
else
    echo -e "${RED}❌ Chyba pri stiahnutí zmien${NC}"
    exit 1
fi

# 4. Reštartovať PM2 službu
echo -e "${YELLOW}🔄 Reštartovanie PM2 služby...${NC}"
if pm2 restart "$SERVICE_NAME"; then
    echo -e "${GREEN}✅ Služba reštartovaná${NC}"
else
    echo -e "${RED}❌ Chyba pri reštarte služby${NC}"
    exit 1
fi

# 5. Zobraziť status
echo -e "${YELLOW}📊 Status služby:${NC}"
pm2 status

# 6. Zobraziť posledné logy
echo -e "${YELLOW}📝 Posledné logy:${NC}"
pm2 logs "$SERVICE_NAME" --lines 10 --nostream

echo -e "${GREEN}✅ Hotovo!${NC}"

