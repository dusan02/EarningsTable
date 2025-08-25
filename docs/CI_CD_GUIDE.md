# 🚀 CI/CD Pipeline Guide

## 📋 **Prehľad CI/CD Pipeline**

Náš CI/CD pipeline automatizuje testovanie, kontrolu kvality kódu a nasadenie aplikácie.

### **🔄 CI (Continuous Integration):**
- **Automatické testy** pri každom commite
- **Kontrola kvality** kódu (PHPStan, CodeSniffer)
- **Kontrola dokumentácie**
- **Rýchle odhalenie chýb**

### **🚀 CD (Continuous Deployment):**
- **Automatické nasadenie** na produkčný server
- **Bezpečné rollbacky** pri problémoch
- **Post-deployment testy**
- **Monitoring a alerting**

## 🛠️ **Nastavenie GitHub Actions**

### **1. GitHub Secrets:**
Nastavte tieto secrets v GitHub repository:

```bash
SSH_PRIVATE_KEY          # SSH kľúč pre server
SERVER_USER             # SSH user (napr. root)
SERVER_HOST             # IP adresa servera
SITE_URL                # URL webu (napr. https://earningstable.com)
```

### **2. SSH Key Setup:**
```bash
# Generovanie SSH kľúča
ssh-keygen -t rsa -b 4096 -C "github-actions@earningstable.com"

# Pridanie public key na server
ssh-copy-id -i ~/.ssh/id_rsa.pub user@server

# Pridanie private key do GitHub Secrets
cat ~/.ssh/id_rsa
```

## 📁 **Štruktúra CI/CD súborov:**

```
.github/
├── workflows/
│   └── ci-cd.yml          # Hlavný CI/CD workflow
├── composer.json          # PHP dependencies
├── phpstan.neon          # PHPStan konfigurácia
├── phpcs.xml             # CodeSniffer konfigurácia
└── deploy/
    ├── rollback.sh       # Rollback script
    ├── auto-backup.sh    # Backup script
    └── monitor.sh        # Monitoring script
```

## 🧪 **Testovací proces:**

### **Automatické testy:**
1. **Database tests** - kontrola pripojenia
2. **API tests** - kontrola API endpointov
3. **Path tests** - kontrola ciest
4. **Integration tests** - komplexné testy

### **Kontrola kvality:**
1. **PHPStan** - statická analýza kódu
2. **CodeSniffer** - kontrola štýlu kódu
3. **Documentation check** - kontrola dokumentácie

## 🚀 **Deployment proces:**

### **1. Backup:**
```bash
# Automatický backup pred deploymentom
./deploy/auto-backup.sh
```

### **2. Deployment:**
```bash
# Vytvorenie deployment package
tar -czf deployment.tar.gz --exclude='.git' --exclude='Tests' .

# Upload na server
scp deployment.tar.gz user@server:/tmp/

# Rozbalenie na serveri
ssh user@server "cd /var/www/html && tar -xzf /tmp/deployment.tar.gz"
```

### **3. Post-deployment:**
```bash
# Testovanie po nasadení
./deploy/monitor.sh

# Kontrola dostupnosti
curl -f https://earningstable.com/test-db.php
```

## 🚨 **Rollback proces:**

### **Automatický rollback:**
```bash
# Spustenie rollback scriptu
./deploy/rollback.sh
```

### **Manuálny rollback:**
```bash
# Nájdenie backup súboru
ls -t /var/www/backups/*.tar.gz

# Rollback na konkrétny backup
tar -xzf /var/www/backups/backup_20250824_143000.tar.gz
```

## 📊 **Monitoring:**

### **Automatické kontroly:**
- **Website availability**
- **Database connection**
- **API endpoints**
- **Cron jobs**
- **Disk space**
- **Memory usage**

### **Alerting:**
- **Email notifications** pri kritických problémoch
- **Log monitoring** v `/var/log/earnings-table-monitor.log`
- **Real-time status** cez monitoring script

## 🔧 **Lokálne testovanie:**

### **Composer commands:**
```bash
# Inštalácia dependencies
composer install

# Spustenie testov
composer test

# Kontrola kvality kódu
composer cs
composer stan

# Vytvorenie deployment package
composer deploy
```

### **Make commands:**
```bash
# Spustenie všetkých testov
make test

# Test databázy
make test-db

# Test API
make test-api

# Vyčistenie cache
make clean

# Vytvorenie zálohy
make backup
```

## 🎯 **Best Practices:**

### **✅ Odporúčania:**
1. **Vždy testujte** pred commitom
2. **Používajte feature branches**
3. **Code review** pred merge
4. **Monitorujte** po deploymente
5. **Pravidelné backupy**

### **❌ Čo sa vyhnúť:**
1. **Commitovanie** priamo do main branch
2. **Ignorovanie** testov
3. **Deployment** bez backupu
4. **Ignorovanie** monitoring alertov

## 📞 **Troubleshooting:**

### **Časté problémy:**
1. **SSH connection failed** - skontrolujte SSH kľúče
2. **Database connection failed** - skontrolujte DB credentials
3. **API tests failed** - skontrolujte API kľúče
4. **Deployment failed** - skontrolujte server permissions

### **Logy:**
- **GitHub Actions:** V repository na záložke Actions
- **Server logs:** `/var/log/earnings-table-*.log`
- **Application logs:** `logs/` directory

## 🎉 **Záver:**

CI/CD pipeline zabezpečuje:
- **Automatické testovanie**
- **Kontrolu kvality**
- **Bezpečné nasadenie**
- **Monitoring a alerting**
- **Rýchle rollbacky**

**Pipeline je pripravený na produkciu!** 🚀
