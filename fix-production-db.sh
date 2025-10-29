#!/bin/bash
# 🔧 Fix Production Database - Run Prisma Migrations
# This script fixes the missing database tables issue

set -e

echo "🔧 Fixing production database..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

PROJECT_DIR="/var/www/earnings-table"
DB_PATH="$PROJECT_DIR/modules/database/prisma/prod.db"
SCHEMA_PATH="$PROJECT_DIR/modules/database/prisma/schema.prisma"

echo -e "${YELLOW}📋 Database Configuration:${NC}"
echo "  Project Dir: $PROJECT_DIR"
echo "  Database: $DB_PATH"
echo "  Schema: $SCHEMA_PATH"
echo ""

# Check if database file exists
if [ ! -f "$DB_PATH" ]; then
    echo -e "${YELLOW}⚠️  Database file not found, creating...${NC}"
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"
    echo -e "${GREEN}✅ Database file created${NC}"
fi

# Check if schema exists
if [ ! -f "$SCHEMA_PATH" ]; then
    echo -e "${RED}❌ Schema file not found at: $SCHEMA_PATH${NC}"
    echo -e "${YELLOW}Trying root prisma.schema...${NC}"
    SCHEMA_PATH="$PROJECT_DIR/prisma.schema"
fi

# Set DATABASE_URL
export DATABASE_URL="file:$DB_PATH"
echo -e "${YELLOW}📊 DATABASE_URL: $DATABASE_URL${NC}"

# Navigate to database directory
cd "$PROJECT_DIR/modules/database"

# Option 1: Use migrate deploy (if migrations exist)
if [ -d "prisma/migrations" ] && [ "$(ls -A prisma/migrations)" ]; then
    echo -e "${YELLOW}🔄 Running Prisma migrations...${NC}"
    npx prisma migrate deploy --schema=prisma/schema.prisma || {
        echo -e "${YELLOW}⚠️  Migrate deploy failed, trying db push...${NC}"
        npx prisma db push --schema=prisma/schema.prisma --accept-data-loss
    }
else
    # Option 2: Use db push (if no migrations)
    echo -e "${YELLOW}🔄 Pushing database schema (no migrations found)...${NC}"
    npx prisma db push --schema=prisma/schema.prisma --accept-data-loss
fi

# Regenerate Prisma client
echo -e "${YELLOW}🔨 Regenerating Prisma client...${NC}"
npx prisma generate --schema=prisma/schema.prisma

# Verify tables exist
echo -e "${YELLOW}✅ Verifying database tables...${NC}"
if sqlite3 "$DB_PATH" "SELECT name FROM sqlite_master WHERE type='table' AND name='final_report';" | grep -q "final_report"; then
    echo -e "${GREEN}✅ Table 'final_report' exists${NC}"
else
    echo -e "${RED}❌ Table 'final_report' NOT found${NC}"
    echo -e "${YELLOW}Trying to create it manually...${NC}"
    sqlite3 "$DB_PATH" <<EOF
CREATE TABLE IF NOT EXISTS final_report (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol TEXT UNIQUE NOT NULL,
    name TEXT,
    size TEXT,
    marketCap INTEGER,
    marketCapDiff INTEGER,
    price REAL,
    change REAL,
    epsActual REAL,
    epsEst REAL,
    epsSurp REAL,
    revActual INTEGER,
    revEst INTEGER,
    revSurp REAL,
    logoUrl TEXT,
    logoSource TEXT,
    logoFetchedAt TEXT,
    createdAt TEXT NOT NULL DEFAULT (datetime('now')),
    updatedAt TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS final_report_symbol_idx ON final_report(symbol);
CREATE INDEX IF NOT EXISTS final_report_createdAt_idx ON final_report(createdAt);
EOF
fi

# Show all tables
echo -e "${YELLOW}📊 Database tables:${NC}"
sqlite3 "$DB_PATH" "SELECT name FROM sqlite_master WHERE type='table';"

echo ""
echo -e "${GREEN}✅ Database fix complete!${NC}"
echo ""
echo "Next steps:"
echo "  1. Restart PM2: pm2 restart earnings-table"
echo "  2. Check logs: pm2 logs earnings-table --lines 50"
echo "  3. Test API: curl http://localhost:5555/api/health"

