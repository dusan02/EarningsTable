#!/bin/bash

# 💾 AUTO BACKUP SCRIPT
# Automatické zálohovanie pred deploymentom

set -e

# Konfigurácia
BACKUP_DIR="/var/www/backups"
CURRENT_DIR="/var/www/html"
MAX_BACKUPS=10
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "💾 Starting automatic backup..."

# 1. Vytvorenie backup adresára ak neexistuje
mkdir -p "$BACKUP_DIR"

# 2. Vytvorenie backup súboru
BACKUP_FILE="$BACKUP_DIR/backup_$TIMESTAMP.tar.gz"
echo "📦 Creating backup: $BACKUP_FILE"

cd "$CURRENT_DIR"
tar -czf "$BACKUP_FILE" \
    --exclude='logs/*.log' \
    --exclude='storage/*.cache' \
    --exclude='temp_*' \
    .

# 3. Kontrola veľkosti backup súboru
BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
echo "📊 Backup size: $BACKUP_SIZE"

# 4. Overenie integrity backup súboru
echo "🔍 Verifying backup integrity..."
if tar -tzf "$BACKUP_FILE" > /dev/null 2>&1; then
    echo "✅ Backup integrity verified"
else
    echo "❌ Backup integrity check failed"
    rm -f "$BACKUP_FILE"
    exit 1
fi

# 5. Vymazanie starých backup súborov (zachová MAX_BACKUPS)
echo "🧹 Cleaning old backups..."
cd "$BACKUP_DIR"
ls -t *.tar.gz | tail -n +$((MAX_BACKUPS + 1)) | xargs -r rm -f

# 6. Zobrazenie dostupných backup súborov
echo "📋 Available backups:"
ls -lh *.tar.gz | head -$MAX_BACKUPS

# 7. Logovanie
echo "[$(date)] Backup created: $BACKUP_FILE (Size: $BACKUP_SIZE)" >> /var/log/earnings-table-backup.log

echo "✅ Automatic backup completed successfully!"
echo "📦 Backup file: $BACKUP_FILE"
echo "📊 Size: $BACKUP_SIZE"
echo "📅 Time: $(date)"
