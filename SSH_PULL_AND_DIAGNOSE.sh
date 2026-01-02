#!/bin/bash
# 🔄 Pull latest changes and run diagnosis

set -e

echo "🔄 Pulling latest changes..."

cd /var/www/earnings-table

# Stash local changes if any
if [ -n "$(git status --porcelain)" ]; then
    echo "⚠️  Local changes detected, stashing..."
    git stash
fi

# Pull latest
git pull origin main

# Make scripts executable
chmod +x SSH_DIAGNOSE_DNS_AND_SERVER.sh 2>/dev/null || true
chmod +x SSH_FIX_NGINX_CLEAN.sh 2>/dev/null || true

# Run diagnosis
echo ""
echo "🔍 Running diagnosis..."
echo ""
./SSH_DIAGNOSE_DNS_AND_SERVER.sh
