# 🚀 Rýchle stiahnutie zmien na SSH serveri

## ✅ Projekt nájdený na: `/var/www/earnings-table`

## 📥 Príkazy na stiahnutie zmien:

```bash
# 1. Prejsť do projektu
cd /var/www/earnings-table

# 2. Skontrolovať git status
git status

# 3. Stiahnuť zmeny z GitHubu
git pull origin main

# 4. Inštalovať nové závislosti (compression, dotenv)
npm install --legacy-peer-deps

# 5. Reštartovať PM2 služby
pm2 restart earnings-table
pm2 restart earnings-cron

# 6. Skontrolovať status
pm2 status
pm2 logs earnings-table --lines 20
```

## ⚠️ Ak git pull zlyhá kvôli divergent branches:

```bash
cd /var/www/earnings-table
git config pull.rebase false
git pull origin main --no-rebase
```

## 🔍 Overenie, že zmeny sú stiahnuté:

```bash
# Skontrolovať, či sú nové súbory
ls -la package.json
grep -i "compression\|dotenv" package.json

# Skontrolovať, či simple-server.js má opravy
grep -i "dll.node" simple-server.js
```

## ✅ Po úspešnom stiahnutí:

- Nové závislosti (compression, dotenv) budú nainštalované
- Prisma engine detection bude fungovať pre Windows aj Linux
- Server by mal bežať bez chýb
