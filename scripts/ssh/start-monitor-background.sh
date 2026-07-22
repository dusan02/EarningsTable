#!/bin/bash
# 🚀 Start Monitor in Background - Auto-fix Git Conflicts

set -e

echo "🚀 Starting Monitor in Background"
echo "=================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Step 1: Fix git conflict
echo -e "${BLUE}Step 1: Fixing git conflict...${NC}"

if git status --porcelain | grep -q "monitor-dns-and-auto-certbot.sh"; then
    echo "Found local changes in monitor-dns-and-auto-certbot.sh"
    echo "Discarding local changes (using remote version)..."
    git restore monitor-dns-and-auto-certbot.sh 2>/dev/null || git checkout -- monitor-dns-and-auto-certbot.sh
    echo -e "${GREEN}✅ Local changes discarded${NC}"
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

# Step 3: Make script executable
echo ""
echo -e "${BLUE}Step 3: Making script executable...${NC}"
chmod +x monitor-dns-and-auto-certbot.sh
echo -e "${GREEN}✅ Script is executable${NC}"

# Step 4: Start monitor in background
echo ""
echo -e "${BLUE}Step 4: Starting monitor in background...${NC}"

# Start in background with nohup
nohup ./monitor-dns-and-auto-certbot.sh >> /var/log/monitor-dns.log 2>&1 &
MONITOR_PID=$!

sleep 3

# Verify it's running
if pgrep -f "monitor-dns-and-auto-certbot" > /dev/null; then
    echo -e "${GREEN}✅ Monitor started in background (PID: $MONITOR_PID)${NC}"
    echo ""
    echo "Monitor is running. You can:"
    echo "  - Check status: pgrep -af monitor-dns-and-auto-certbot"
    echo "  - View logs: tail -f /var/log/monitor-dns.log"
    echo "  - Stop monitor: pkill -f monitor-dns-and-auto-certbot"
    echo ""
    echo "Current log output:"
    echo "-------------------"
    tail -n 10 /var/log/monitor-dns.log 2>/dev/null || echo "Log file is being created..."
else
    echo -e "${RED}❌ Failed to start monitor${NC}"
    echo ""
    echo "Check logs:"
    cat /var/log/monitor-dns.log 2>/dev/null | tail -20 || echo "No log file found"
    exit 1
fi

echo ""


