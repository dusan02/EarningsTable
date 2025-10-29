#!/bin/bash
# 🔧 COMPLETE Prisma Fix - Remove ALL root Prisma and force using shared
# This fixes the issue where old Prisma client from root is still being used

set -e

echo "🔧 COMPLETE Prisma fix - removing ALL root Prisma clients..."

PROJECT_DIR="/var/www/earnings-table"

cd "$PROJECT_DIR"

# 1. Remove ALL root Prisma clients completely
echo "🧹 Removing ALL root Prisma clients..."
rm -rf node_modules/.prisma 2>/dev/null || true
rm -rf node_modules/@prisma 2>/dev/null || true
rm -rf node_modules/@prisma/client 2>/dev/null || true
echo "✅ Root Prisma clients removed"

# 2. Verify root Prisma is gone
echo "🔍 Verifying root Prisma is gone..."
if [ -d "node_modules/.prisma" ] || [ -d "node_modules/@prisma/client" ]; then
    echo "❌ Root Prisma still exists!"
    ls -la node_modules/.prisma 2>/dev/null || true
    ls -la node_modules/@prisma/client 2>/dev/null || true
else
    echo "✅ Root Prisma confirmed removed"
fi

# 3. Ensure shared Prisma client exists and is correct
echo "🔍 Checking shared Prisma client..."
if [ ! -d "modules/shared/node_modules/@prisma/client" ]; then
    echo "📦 Installing @prisma/client in modules/shared..."
    cd modules/shared
    npm install @prisma/client@6.17.1
    cd ../..
fi

# 4. Create symlink if needed
echo "🔗 Creating symlink for Prisma runtime..."
cd modules/shared/node_modules/@prisma/client
if [ ! -L ".prisma" ] && [ ! -d ".prisma" ]; then
    if [ -d "../.prisma/client" ]; then
        ln -s ../.prisma/client .prisma
        echo "✅ Symlink created"
    else
        echo "❌ Parent .prisma/client not found!"
    fi
else
    echo "✅ Symlink already exists or .prisma directory exists"
fi
cd "$PROJECT_DIR"

# 5. Regenerate Prisma client to ensure it's correct
echo "⚙️  Regenerating Prisma client..."
cd modules/database
export DATABASE_URL="file:$PROJECT_DIR/modules/database/prisma/prod.db"
npx prisma generate --schema=prisma/schema.prisma
cd ../..

echo ""
echo "✅ COMPLETE Prisma fix done!"
echo ""
echo "📊 Verification:"
echo "  Root .prisma: $(ls -d node_modules/.prisma 2>/dev/null || echo 'NOT FOUND ✅')"
echo "  Root @prisma/client: $(ls -d node_modules/@prisma/client 2>/dev/null || echo 'NOT FOUND ✅')"
echo "  Shared @prisma/client: $(ls -d modules/shared/node_modules/@prisma/client 2>/dev/null || echo 'NOT FOUND ❌')"
echo "  Shared .prisma/client: $(ls -d modules/shared/node_modules/.prisma/client 2>/dev/null || echo 'NOT FOUND ❌')"
echo ""
echo "Next: Restart PM2 service"
echo "  pm2 restart earnings-table"

