#!/bin/bash
# 🔧 Install @prisma/client package in modules/shared
# This is required for simple-server.js to work

set -e

echo "📦 Installing @prisma/client in modules/shared..."

PROJECT_DIR="/var/www/earnings-table"

cd "$PROJECT_DIR/modules/shared"

# Install @prisma/client package
echo "📦 Installing @prisma/client..."
npm install @prisma/client@6.17.1

# Regenerate Prisma client to ensure it's linked correctly
echo "⚙️  Regenerating Prisma client..."
cd ../database
export DATABASE_URL="file:$PROJECT_DIR/modules/database/prisma/prod.db"
npx prisma generate --schema=prisma/schema.prisma

cd ../..

echo ""
echo "✅ @prisma/client installed and Prisma client regenerated!"
echo ""
echo "Next: Restart PM2 service"
echo "  pm2 restart earnings-table"

