#!/bin/bash
# 📥 Stiahnuť najnovšie zmeny z GitHubu a reštartovať služby

set -e

echo "📥 Stiahnutie najnovších zmien z GitHubu..."

cd /var/www/earnings-table

# Stiahnuť zmeny
echo "🔄 Pulling latest changes..."
git pull origin main

# Reštartovať PM2 služby
echo "🔄 Restarting PM2 services..."
pm2 restart earnings-table

# Zobraziť status
echo "📊 PM2 Status:"
pm2 status

echo "✅ Hotovo!"
