#!/bin/bash

# 🚨 ROLLBACK SCRIPT
# Bezpečné vrátenie zmien pri problémoch s deploymentom

set -e

# Konfigurácia
BACKUP_DIR="/var/www/backups"
CURRENT_DIR="/var/www/html"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "🚨 Starting rollback process..."

# 1. Kontrola existencie backup súborov
if [ ! -d "$BACKUP_DIR" ]; then
    echo "❌ Backup directory not found: $BACKUP_DIR"
    exit 1
fi

# 2. Nájdenie najnovšieho backup súboru
LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/*.tar.gz 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "❌ No backup files found in $BACKUP_DIR"
    exit 1
fi

echo "📦 Found backup: $LATEST_BACKUP"

# 3. Vytvorenie backup aktuálneho stavu
echo "💾 Creating backup of current state..."
tar -czf "$BACKUP_DIR/rollback_backup_$TIMESTAMP.tar.gz" -C "$CURRENT_DIR" .

# 4. Rollback na predchádzajúci stav
echo "🔄 Rolling back to previous version..."
cd "$CURRENT_DIR"
tar -xzf "$LATEST_BACKUP"

# 5. Nastavenie správnych oprávnení
echo "🔐 Setting permissions..."
chmod -R 755 .
chown -R www-data:www-data .

# 6. Test rollback
echo "🧪 Testing rollback..."
if curl -f http://localhost/test-db.php > /dev/null 2>&1; then
    echo "✅ Rollback successful!"
    echo "🌐 Site is accessible"
else
    echo "❌ Rollback failed - site not accessible"
    exit 1
fi

# 7. Notifikácia
echo "📢 Rollback completed successfully!"
echo "📅 Time: $(date)"
echo "📦 Backup used: $LATEST_BACKUP"
echo "💾 Current backup: rollback_backup_$TIMESTAMP.tar.gz"

# 8. Logovanie
echo "[$(date)] Rollback completed successfully using $LATEST_BACKUP" >> /var/log/earnings-table-rollback.log
