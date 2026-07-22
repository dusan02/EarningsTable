#!/bin/bash
# 🔧 Complete Prisma Client Fix
# Remove ALL root Prisma clients and ensure only shared client exists

set -e

echo "🔧 Complete Prisma client fix..."

PROJECT_DIR="/var/www/earnings-table"

cd "$PROJECT_DIR"

# Remove ALL root Prisma clients
echo "🧹 Removing ALL root Prisma clients..."
rm -rf node_modules/.prisma 2>/dev/null || true
rm -rf node_modules/@prisma 2>/dev/null || true
rm -rf node_modules/@prisma/client 2>/dev/null || true
echo "✅ Root Prisma clients removed"

# Ensure @prisma/client is installed in modules/shared
echo "📦 Ensuring @prisma/client in modules/shared..."
cd modules/shared
if [ ! -d "node_modules/@prisma/client" ]; then
    echo "Installing @prisma/client..."
    npm install @prisma/client@6.17.1
else
    echo "✅ @prisma/client already installed"
fi

# Regenerate Prisma client
echo "⚙️  Regenerating Prisma client..."
cd ../database
export DATABASE_URL="file:$PROJECT_DIR/modules/database/prisma/prod.db"
npx prisma generate --schema=prisma/schema.prisma

cd ../..

echo ""
echo "✅ Complete Prisma client fix done!"
echo ""
echo "📊 Verification:"
echo "  Root .prisma: $(ls -d node_modules/.prisma 2>/dev/null || echo 'NOT FOUND')"
echo "  Shared @prisma/client: $(ls -d modules/shared/node_modules/@prisma/client 2>/dev/null || echo 'NOT FOUND')"
echo ""
echo "Next: Restart PM2 service"
echo "  pm2 restart earnings-table"

