#!/bin/bash
# 🔧 Regenerate Prisma Client for Production
# This ensures the Prisma client matches the current database schema

set -e

echo "🔧 Regenerating Prisma client for production..."

PROJECT_DIR="/var/www/earnings-table"
export DATABASE_URL="file:$PROJECT_DIR/modules/database/prisma/prod.db"

cd "$PROJECT_DIR/modules/database"

echo "📊 Current DATABASE_URL: $DATABASE_URL"
echo "📋 Schema location: prisma/schema.prisma"
echo ""

# Remove old generated client
echo "🧹 Cleaning old Prisma client..."
rm -rf ../shared/node_modules/.prisma
rm -rf ../shared/node_modules/@prisma/client
echo "✅ Old client removed"

# Regenerate Prisma client
echo "⚙️  Generating Prisma client..."
npx prisma generate --schema=prisma/schema.prisma

echo ""
echo "✅ Prisma client regenerated!"
echo ""
echo "📊 Generated client location:"
ls -la ../shared/node_modules/.prisma/client/ | head -5
echo ""
echo "Next: Restart PM2 service"
echo "  pm2 restart earnings-table"

