# 📥 Inštrukcie pre stiahnutie zmien na SSH serveri

## 🔍 Krok 1: Nájsť správnu cestu k projektu

Na SSH serveri skúste tieto príkazy:

```bash
# Skúste nájsť projekt
find /var/www -name "package.json" -type f 2>/dev/null
find /home -name "package.json" -type f 2>/dev/null
find /opt -name "package.json" -type f 2>/dev/null

# Alebo skontrolovať, či existuje štandardná cesta
ls -la /var/www/earnings-table
ls -la /var/www/EarningsTable
```

## 📋 Krok 2: Ak projekt už existuje

```bash
# Prejsť do projektu
cd /var/www/earnings-table
# alebo
cd /var/www/EarningsTable

# Skontrolovať git status
git status

# Stiahnuť zmeny
git pull origin main

# Inštalovať závislosti (ak treba)
npm install --legacy-peer-deps
```

## 🆕 Krok 3: Ak projekt ešte neexistuje (prvé nasadenie)

```bash
# Vytvoriť priečinok
sudo mkdir -p /var/www/earnings-table
sudo chown -R $USER:$USER /var/www/earnings-table

# Klonovať z GitHubu
cd /var/www
git clone https://github.com/dusan02/EarningsTable.git earnings-table
cd earnings-table

# Inštalovať závislosti
npm install --legacy-peer-deps
```

## 🔄 Krok 4: Po stiahnutí zmien

```bash
# Inštalovať nové závislosti
npm install --legacy-peer-deps

# Reštartovať PM2 služby (ak bežia)
pm2 restart earnings-table
pm2 restart earnings-cron
pm2 status
```

## ⚠️ Riešenie problémov

### Ak git pull zlyhá kvôli divergent branches:

```bash
cd /var/www/earnings-table
git config pull.rebase false
git pull origin main --no-rebase
```

### Ak nie je git repository:

```bash
cd /var/www/earnings-table
git init
git remote add origin https://github.com/dusan02/EarningsTable.git
git pull origin main
```

## 📝 Rýchle príkazy (ak už viete cestu)

```bash
cd /var/www/earnings-table
git pull origin main
npm install --legacy-peer-deps
pm2 restart earnings-table
pm2 status
```
