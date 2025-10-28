#!/bin/bash

# 🚀 Quick Deploy: Prisma Fix to Production
# Uploads and runs the Prisma fix script on production server

set -e

echo "🚀 Deploying Prisma fix to production..."

# Configuration - UPDATE THESE VALUES
SERVER_USER="root"
SERVER_HOST="bardusa"
PROJECT_DIR="/var/www/earnings-table"

echo "📋 Deploy Configuration:"
echo "  Server: $SERVER_USER@$SERVER_HOST"
echo "  Project: $PROJECT_DIR"
echo ""

# 1. Upload the fix script
echo "📤 Uploading fix script..."
scp fix-prisma-production.sh "$SERVER_USER@$SERVER_HOST:/tmp/"

# 2. Make it executable and run it
echo "🔧 Running Prisma fix on production..."
ssh "$SERVER_USER@$SERVER_HOST" << EOF
chmod +x /tmp/fix-prisma-production.sh
cd $PROJECT_DIR
/tmp/fix-prisma-production.sh
EOF

echo ""
echo "✅ Prisma fix deployed to production!"
echo ""
echo "🔍 Check status with:"
echo "  ssh $SERVER_USER@$SERVER_HOST 'pm2 status'"
echo "  ssh $SERVER_USER@$SERVER_HOST 'pm2 logs earnings-cron --lines 20'"
