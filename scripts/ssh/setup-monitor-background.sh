#!/bin/bash
# 🔧 Setup Monitor in Background and Fix Git Conflicts

set -e

echo "🔧 Setup Monitor in Background"
echo "=============================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Step 1: Fix git conflict
echo -e "${BLUE}Step 1: Fixing git conflict...${NC}"

if git status --porcelain | grep -q "check-status.sh"; then
    echo "Found local changes in check-status.sh"
    echo ""
    echo "Options:"
    echo "  A) Keep local changes (stash them)"
    echo "  B) Discard local changes (use remote version) - RECOMMENDED"
    echo ""
    read -p "Choose (A/B) [default: B]: " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Aa]$ ]]; then
        echo "Stashing local changes..."
        git stash push -m "local check-status tweak" -- check-status.sh
        echo -e "${GREEN}✅ Local changes stashed${NC}"
    else
        echo "Discarding local changes..."
        git restore check-status.sh 2>/dev/null || git checkout -- check-status.sh
        echo -e "${GREEN}✅ Local changes discarded${NC}"
    fi
else
    echo -e "${GREEN}✅ No local changes detected${NC}"
fi

# Pull latest changes
echo ""
echo "Pulling latest changes..."
if git pull origin feat/skeleton-loading-etag; then
    echo -e "${GREEN}✅ Pull successful${NC}"
else
    echo -e "${RED}❌ Pull failed${NC}"
    exit 1
fi

# Step 2: Check if monitor is already running
echo ""
echo -e "${BLUE}Step 2: Checking if monitor is already running...${NC}"

MONITOR_PID=$(pgrep -f "monitor-dns-and-auto-certbot" || echo "")

if [ -n "$MONITOR_PID" ]; then
    echo -e "${YELLOW}⚠️  Monitor script is already running (PID: $MONITOR_PID)${NC}"
    echo ""
    read -p "Kill existing monitor and start new one? (y/N): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        kill $MONITOR_PID 2>/dev/null || true
        sleep 2
        echo -e "${GREEN}✅ Old monitor stopped${NC}"
    else
        echo "Keeping existing monitor running"
        echo ""
        echo "Monitor is running. Check logs:"
        echo "  tail -f /var/log/monitor-dns.log"
        exit 0
    fi
fi

# Step 3: Start monitor in background
echo ""
echo -e "${BLUE}Step 3: Starting monitor in background...${NC}"

# Make sure script is executable
chmod +x monitor-dns-and-auto-certbot.sh

# Start in background with nohup
nohup ./monitor-dns-and-auto-certbot.sh >> /var/log/monitor-dns.log 2>&1 &
MONITOR_PID=$!

sleep 2

# Verify it's running
if pgrep -f "monitor-dns-and-auto-certbot" > /dev/null; then
    echo -e "${GREEN}✅ Monitor started in background (PID: $MONITOR_PID)${NC}"
    echo ""
    echo "Monitor is running. You can:"
    echo "  - Check status: pgrep -af monitor-dns-and-auto-certbot"
    echo "  - View logs: tail -f /var/log/monitor-dns.log"
    echo "  - Stop monitor: pkill -f monitor-dns-and-auto-certbot"
else
    echo -e "${RED}❌ Failed to start monitor${NC}"
    echo "Check logs: cat /var/log/monitor-dns.log"
    exit 1
fi

# Step 4: Show current DNS status
echo ""
echo -e "${BLUE}Step 4: Current DNS Status${NC}"
echo "----------------------"

EXPECTED_IP="89.185.250.213"
DNS_MAIN=$(dig +short earningsstable.com 2>/dev/null || echo "")
DNS_WWW=$(dig +short www.earningsstable.com 2>/dev/null || echo "")

echo "earningsstable.com:"
if [ -n "$DNS_MAIN" ]; then
    if [ "$DNS_MAIN" = "$EXPECTED_IP" ]; then
        echo -e "  ${GREEN}✅ $DNS_MAIN (ready!)${NC}"
    else
        echo -e "  ${YELLOW}⚠️  $DNS_MAIN (expected $EXPECTED_IP)${NC}"
    fi
else
    echo -e "  ${RED}❌ No DNS record (waiting for propagation)${NC}"
fi

echo ""
echo "www.earningsstable.com:"
if [ -n "$DNS_WWW" ]; then
    if [ "$DNS_WWW" = "$EXPECTED_IP" ]; then
        echo -e "  ${GREEN}✅ $DNS_WWW (ready!)${NC}"
    else
        echo -e "  ${YELLOW}⚠️  $DNS_WWW (expected $EXPECTED_IP)${NC}"
    fi
else
    echo -e "  ${RED}❌ No DNS record (waiting for propagation)${NC}"
fi

echo ""
echo -e "${GREEN}🎉 Setup complete!${NC}"
echo ""
echo "Summary:"
echo "  ✅ Git conflict resolved"
echo "  ✅ Monitor running in background"
echo "  📊 DNS status checked"
echo ""
echo "Next steps:"
if [ -z "$DNS_MAIN" ] || [ "$DNS_MAIN" != "$EXPECTED_IP" ]; then
    echo "  1. Add DNS A records in your DNS provider:"
    echo "     - A @ → $EXPECTED_IP"
    echo "     - A www → $EXPECTED_IP"
    echo "  2. Wait 5-30 minutes for propagation"
    echo "  3. Monitor will auto-detect and run certbot"
else
    echo "  1. DNS is ready! Monitor should run certbot soon"
    echo "  2. Check logs: tail -f /var/log/monitor-dns.log"
fi
echo ""
echo "Useful commands:"
echo "  - Check monitor: pgrep -af monitor-dns-and-auto-certbot"
echo "  - View logs: tail -f /var/log/monitor-dns.log"
echo "  - Check DNS: dig +short earningsstable.com"
echo "  - Stop monitor: pkill -f monitor-dns-and-auto-certbot"
echo ""

