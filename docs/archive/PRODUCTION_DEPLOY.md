# 🚀 Príkazy na deploy na produkciu

## Rýchly deploy (git pull + restart)

```bash
# 1. Prihlásenie na server
ssh user@your-server

# 2. Prechod do projektu
cd /var/www/earnings-table

# 3. Stiahnutie zmien z GitHuba
git pull origin main

# 4. Restart PM2 služieb
pm2 restart all

# 5. Kontrola stavu
pm2 status
pm2 logs earnings-table --lines 20
```

## Kompletný deploy (ak treba reinstalovať závislosti)

```bash
# 1. Prihlásenie a prechod do projektu
cd /var/www/earnings-table

# 2. Stiahnutie zmien
git pull origin main

# 3. Reinstalácia závislostí (ak je potrebné)
npm install --production
cd modules/database && npm install --production && cd ../..
cd modules/cron && npm install --production && cd ../..
cd modules/shared && npm install --production && cd ../..

# 4. Regenerácia Prisma clienta (ak je potrebné)
cd modules/database
npx prisma generate --schema=prisma/schema.prisma
cd ../..

# 5. Restart služieb
pm2 restart all

# 6. Kontrola
pm2 status
pm2 logs earnings-table --lines 30
```

## Overenie správneho behu

```bash
# Kontrola PM2 procesov
pm2 status

# Logy (posledných 50 riadkov)
pm2 logs earnings-table --lines 50

# Test API endpointu
curl http://localhost:5555/api/health

# Test final report API
curl http://localhost:5555/api/final-report | head -20

# Skontrolovať otvorené porty
netstat -tuln | grep 5555
```

## Ak niečo nefunguje

```bash
# Kompletný restart (stop + start)
pm2 stop all
pm2 delete all
pm2 start ecosystem.config.js

# Alebo len web server
pm2 restart earnings-table

# Alebo len cron
pm2 restart earnings-cron

# Kontrola chýb
pm2 logs --err
```

## Poznámky

- **Rýchly deploy** stačí pokiaľ sa nezmenili závislosti (package.json)
- **Kompletný deploy** je potrebný ak sa zmenili npm balíčky alebo Prisma schema
- Po `git pull` sa zmeny prejavia okamžite po `pm2 restart`
- Pre frontend zmeny (`index.html`) netreba nič iné okrem restartu

