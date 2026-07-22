#!/bin/bash
# 🔧 Fix Prisma Client - Create symlink for .prisma/client
# Prisma client looks for .prisma/client in the same directory, but it's in parent

set -e

PROJECT_DIR="/var/www/earnings-table"

echo "🔧 Fixing Prisma client runtime symlink..."

cd "$PROJECT_DIR/modules/shared/node_modules/@prisma/client"

# Check if .prisma directory exists
if [ -d ".prisma" ]; then
    echo "✅ .prisma directory exists, removing..."
    rm -rf .prisma
fi

# Check if parent .prisma/client exists
if [ -d "../.prisma/client" ]; then
    echo "✅ Found parent .prisma/client, creating symlink..."
    ln -s ../.prisma/client .prisma
    echo "✅ Symlink created: .prisma -> ../.prisma/client"
else
    echo "❌ Parent .prisma/client not found!"
    exit 1
fi

echo ""
echo "✅ Prisma client runtime symlink created!"
echo ""
echo "Next: Restart PM2 service"
echo "  pm2 restart earnings-table"

