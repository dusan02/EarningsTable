# 📋 Príkazy pre SSH server (produkcia)

## ⚠️ DÔLEŽITÉ

Tieto príkazy musia byť spustené **na SSH serveri (Linux)**, NIE na Windows PowerShell!

## 🔗 Pripojenie na SSH server

```bash
ssh root@bardusa
# alebo
ssh your-username@your-server-ip
```

## 📥 Stiahnutie zmien z GitHubu a restart

```bash
# 1. Prejsť do projektu
cd /var/www/earnings-table

# 2. Stiahnuť najnovšie zmeny z GitHubu
git pull origin main

# 3. Reštartovať PM2 službu
pm2 restart earnings-table

# 4. Skontrolovať status
pm2 status
pm2 logs earnings-table --lines 20
```

## ✅ Overenie

```bash
# Test API
curl http://localhost:5555/api/health

# Alebo cez doménu
curl https://www.earningstable.com/api/health
```

## 🔧 Oprava problémov s dátami

### Ak v tabulke nie sú dáta

```bash
cd /var/www/earnings-table

# 1. Diagnostika problému
./fix-production-data.sh diagnose

# 2. Podľa výsledku:
#    - Ak cron nebeží: ./fix-production-data.sh reset-cron
#    - Ak sú dáta prázdne: ./fix-production-data.sh reset-db
#    - Kompletný reset: ./fix-production-data.sh all
```

**Viac informácií:** Pozri `PRODUCTION_FIX_QUICK_REFERENCE.md`

## 📝 Poznámky

- **Na Windows** už máte všetko pushnuté na GitHub ✅
- **Na SSH serveri** len potrebujete pullnúť a reštartovať
- Cesta `/var/www/earnings-table` existuje len na Linux serveri
- Na Windows používajte `D:\Projects\EarningsTable`
- **Nový skript:** `fix-production-data.sh` - diagnostika a oprava dát
