#!/bin/bash
# 🔧 Fix Prisma Client - Remove old root client and force using shared
# This ensures we use the correct Prisma client that matches the database schema

set -e

echo "🔧 Fixing Prisma client configuration..."

PROJECT_DIR="/var/www/earnings-table"

cd "$PROJECT_DIR"

# Remove old root Prisma client
echo "🧹 Removing old root Prisma client..."
rm -rf node_modules/.prisma 2>/dev/null || true
rm -rf node_modules/@prisma/client 2>/dev/null || true
echo "✅ Old root client removed"

# Ensure shared Prisma client exists
echo "🔍 Checking shared Prisma client..."
if [ ! -d "modules/shared/node_modules/.prisma/client" ]; then
    echo "⚙️  Regenerating Prisma client in shared..."
    cd modules/database
    export DATABASE_URL="file:$PROJECT_DIR/modules/database/prisma/prod.db"
    npx prisma generate --schema=prisma/schema.prisma
    cd ../..
else
    echo "✅ Shared Prisma client exists"
fi

echo ""
echo "✅ Prisma client fix complete!"
echo ""
echo "Next: Restart PM2 service"
echo "  pm2 restart earnings-table"

