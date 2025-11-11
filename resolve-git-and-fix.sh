#!/bin/bash
# 🔧 Resolve Git Conflict and Run Quick Fix

set -e

echo "🔧 Resolving Git Conflict and Running Quick Fix..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Step 1: Check git status
echo -e "${BLUE}Step 1: Checking git status...${NC}"
if git status --porcelain | grep -q "final-cleanup-nginx.sh\|setup-ssl-and-cleanup.sh"; then
    echo "Found local changes in nginx scripts"
    echo ""
    echo "Options:"
    echo "  A) Keep local changes (commit them)"
    echo "  B) Discard local changes (use remote versions) - RECOMMENDED"
    echo ""
    read -p "Choose (A/B) [default: B]: " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Aa]$ ]]; then
        echo "Committing local changes..."
        git add final-cleanup-nginx.sh setup-ssl-and-cleanup.sh
        git commit -m "local nginx scripts tweak"
    else
        echo "Discarding local changes..."
        git restore final-cleanup-nginx.sh setup-ssl-and-cleanup.sh 2>/dev/null || \
        git checkout -- final-cleanup-nginx.sh setup-ssl-and-cleanup.sh
    fi
else
    echo -e "${GREEN}✅ No local changes detected${NC}"
fi

# Step 2: Pull latest changes
echo ""
echo -e "${BLUE}Step 2: Pulling latest changes...${NC}"
if git pull origin feat/skeleton-loading-etag; then
    echo -e "${GREEN}✅ Pull successful${NC}"
else
    echo -e "${RED}❌ Pull failed${NC}"
    echo "Please resolve conflicts manually and run again"
    exit 1
fi

# Step 3: Make quick-fix script executable and run it
echo ""
echo -e "${BLUE}Step 3: Running quick fix...${NC}"
if [ -f "quick-fix-backup-and-dns-check.sh" ]; then
    chmod +x quick-fix-backup-and-dns-check.sh
    ./quick-fix-backup-and-dns-check.sh
else
    echo -e "${RED}❌ quick-fix-backup-and-dns-check.sh not found${NC}"
    echo "The file should have been pulled from git"
    exit 1
fi

echo ""
echo -e "${GREEN}🎉 Done!${NC}"
echo ""
echo "Next steps:"
echo "  1. Check DNS records (if missing, add them)"
echo "  2. Run: certbot --nginx -d earningsstable.com -d www.earningsstable.com"
echo "  3. Test: curl -I https://earningsstable.com/robots.txt"
echo ""

