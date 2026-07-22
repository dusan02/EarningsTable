#!/bin/bash
# 🔧 Fix Git Conflict for check-status.sh

set -e

echo "🔧 Fixing Git Conflict..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check if there are local changes
if git status --porcelain | grep -q "check-status.sh"; then
    echo -e "${BLUE}Found local changes in check-status.sh${NC}"
    echo ""
    echo "Options:"
    echo "  A) Keep local changes (commit them)"
    echo "  B) Discard local changes (use remote version) - RECOMMENDED"
    echo ""
    read -p "Choose (A/B) [default: B]: " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Aa]$ ]]; then
        echo "Committing local changes..."
        git add check-status.sh
        git commit -m "local check-status.sh changes"
    else
        echo "Discarding local changes..."
        git restore check-status.sh 2>/dev/null || git checkout -- check-status.sh
    fi
else
    echo -e "${GREEN}✅ No local changes detected${NC}"
fi

# Pull latest changes
echo ""
echo -e "${BLUE}Pulling latest changes...${NC}"
if git pull origin feat/skeleton-loading-etag; then
    echo -e "${GREEN}✅ Pull successful${NC}"
else
    echo -e "${RED}❌ Pull failed${NC}"
    echo "Please resolve conflicts manually"
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 Git conflict resolved!${NC}"
echo ""

