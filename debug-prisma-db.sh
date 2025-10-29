#!/bin/bash
# 🔍 Debug Prisma Database Issue
# Check database tables and Prisma client configuration

set -e

PROJECT_DIR="/var/www/earnings-table"
DB_PATH="$PROJECT_DIR/modules/database/prisma/prod.db"

echo "🔍 Debugging Prisma database issue..."
echo ""

echo "📊 Database file: $DB_PATH"
echo ""

# Check if database exists
if [ ! -f "$DB_PATH" ]; then
    echo "❌ Database file does not exist!"
    exit 1
fi

echo "✅ Database file exists"
echo ""

# Show all tables
echo "📋 Tables in database:"
sqlite3 "$DB_PATH" "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;"
echo ""

# Check final_report table specifically
echo "🔍 Checking final_report table:"
if sqlite3 "$DB_PATH" "SELECT name FROM sqlite_master WHERE type='table' AND name='final_report';" | grep -q "final_report"; then
    echo "✅ Table 'final_report' EXISTS"
    echo ""
    echo "📊 Table structure:"
    sqlite3 "$DB_PATH" ".schema final_report"
    echo ""
    echo "📈 Row count:"
    sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM final_report;"
else
    echo "❌ Table 'final_report' DOES NOT EXIST"
    echo ""
    echo "Creating table..."
    sqlite3 "$DB_PATH" <<'EOF'
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
    reportDate TEXT,
    snapshotDate TEXT,
    createdAt TEXT NOT NULL DEFAULT (datetime('now')),
    updatedAt TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS final_report_symbol_idx ON final_report(symbol);
CREATE INDEX IF NOT EXISTS final_report_createdAt_idx ON final_report(createdAt);
EOF
    echo "✅ Table created"
fi

echo ""
echo "🔧 Checking Prisma client locations:"
echo ""
echo "Root node_modules Prisma client:"
ls -la "$PROJECT_DIR/node_modules/.prisma/client/" 2>/dev/null | head -3 || echo "  Not found"
echo ""
echo "Shared node_modules Prisma client:"
ls -la "$PROJECT_DIR/modules/shared/node_modules/.prisma/client/" 2>/dev/null | head -3 || echo "  Not found"
echo ""
echo "✅ Debug complete"

