# 📋 Príkazy pre SSH server (produkcia)

## ⚠️ DÔLEŽITÉ

Tieto príkazy musia byť spustené **na SSH serveri (Linux)**, NIE na Windows PowerShell!

## 🔗 Pripojenie na SSH server

```bash
ssh root@bardusa
# alebo
ssh your-username@your-server-ip
```

## 🔄 Kompletný Git Workflow

**Pozri:** [GIT_SYNC_WORKFLOW.md](GIT_SYNC_WORKFLOW.md) - Kompletný návod na synchronizáciu

### Rýchle príkazy (po stiahnutí skriptov):

```bash
# 📥 Stiahnuť zmeny z GitHubu a reštartovať
cd /var/www/earnings-table
./quick-pull-and-restart.sh

# 📤 Upload dát na GitHub
cd /var/www/earnings-table
./upload-data-to-git.sh "Popis zmien"
```

### Manuálne príkazy (ak skripty ešte nie sú):

```bash
# 📤 Upload dát na GitHub (manuálne)
cd /var/www/earnings-table
git add .
git commit -m "Update: Production data sync $(date +%Y-%m-%d)"
git push origin main

# 📥 Stiahnuť zmeny a reštartovať (manuálne)
cd /var/www/earnings-table
git pull origin main
pm2 restart earnings-table
pm2 status
```

### Prvé stiahnutie skriptov:

```bash
# Na SSH serveri - stiahnuť najnovšie zmeny (vrátane skriptov)
cd /var/www/earnings-table
git pull origin main
chmod +x quick-pull-and-restart.sh upload-data-to-git.sh
```

### ⚠️ Riešenie divergent branches:

Ak sa zobrazí `fatal: Need to specify how to reconcile divergent branches`:

```bash
# Na SSH serveri
cd /var/www/earnings-table

# Nastaviť merge stratégiu
git config pull.rebase false

# Stiahnuť a zlúčiť zmeny
git pull origin main --no-rebase

# Ak sú konflikty, vyriešiť ich a potom:
git add .
git commit -m "Merge: Resolve conflicts"

# Nastaviť skripty ako spustiteľné
chmod +x quick-pull-and-restart.sh upload-data-to-git.sh
```

**Viac informácií:** Pozri [FIX_DIVERGENT_BRANCHES.md](FIX_DIVERGENT_BRANCHES.md)

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

## 🔄 Git Synchronizácia

### Workflow: SSH → Git → Lokálne PC → Git → SSH

1. **SSH → Git**: `./upload-data-to-git.sh "Popis"`
2. **Git → Lokálne PC**: `git pull origin main` (na Windows)
3. **Opraviť kód** na lokálnom PC
4. **Lokálne PC → Git**: `.\quick-push.ps1 "Popis oprávy"` (na Windows)
5. **Git → SSH**: `./quick-pull-and-restart.sh` (na SSH serveri)

**Viac informácií:** Pozri [GIT_SYNC_WORKFLOW.md](GIT_SYNC_WORKFLOW.md)
