# 🔄 Rýchle nasadenie opráv na produkciu

## Čo treba urobiť:

### 1. **Commitnúť zmeny lokálne:**

```bash
git add simple-server.js api-routes.ts site.webmanifest
git commit -m "Fix: Serialize BigInt and Date values for JSON, add site.webmanifest"
git push origin main
```

### 2. **Pripojiť sa na produkčný server cez SSH:**

```bash
ssh your-username@your-server-ip
```

### 3. **Na serveri:**

```bash
cd /var/www/earnings-table

# Stiahnuť zmeny z Git
git pull origin main

# Alebo ak nemáte Git, skopírovať súbory manuálne:
# - simple-server.js
# - api-routes.ts
# - site.webmanifest
```

### 4. **Reštartovať PM2 službu:**

```bash
pm2 restart earnings-table
# alebo
pm2 restart simple-server.js

# Skontrolovať status
pm2 status
pm2 logs earnings-table --lines 50
```

### 5. **Overiť že funguje:**

```bash
# Test health endpoint
curl https://www.earningstable.com/api/health

# Test final-report endpoint
curl https://www.earningstable.com/api/final-report | head -20

# Test manifest
curl https://www.earningstable.com/site.webmanifest
```

## Alternatívne: Použiť deploy skript

1. Upravte `deploy-fixes-production.sh` (SERVER_USER, SERVER_HOST, PROJECT_DIR)
2. Spustite:

```bash
chmod +x deploy-fixes-production.sh
./deploy-fixes-production.sh
```

## ⚠️ Dôležité:

- **Zálohujte** `simple-server.js` pred nasadením (ak treba)
- **Skontrolujte** logy po reštarte: `pm2 logs earnings-table`
- **Overte** že DATABASE_URL je správne nastavený na serveri
