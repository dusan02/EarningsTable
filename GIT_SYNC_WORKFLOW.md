# 🔄 Git Synchronization Workflow

## 📋 Prehľad

Kompletný workflow pre synchronizáciu dát medzi:
- **SSH Server** (produkcia) ↔ **GitHub** ↔ **Lokálne PC**

Repository: `https://github.com/dusan02/EarningsTable`

---

## 🔄 Workflow Diagram

```
┌─────────────┐         ┌──────────┐         ┌─────────────┐
│ SSH Server  │ ──────► │  GitHub  │ ──────► │ Lokálne PC  │
│ (Produkcia) │         │          │         │  (Windows)  │
└─────────────┘         └──────────┘         └─────────────┘
      ▲                        │                      │
      │                        │                      │
      └────────────────────────┴──────────────────────┘
                    (Opravený kód)
```

---

## 📤 KROK 1: Upload dát z SSH servera → GitHub

### Na SSH serveri (Linux):

```bash
# 1. Pripojiť sa na server
ssh root@bardusa
# alebo
ssh your-username@your-server-ip

# 2. Prejsť do projektu
cd /var/www/earnings-table

# 3. Skontrolovať Git status
git status

# 4. Pridať zmeny (ak sú nejaké dáta na upload)
git add .

# 5. Commit zmeny
git commit -m "Update: Production data sync $(date +%Y-%m-%d)"

# 6. Push na GitHub
git push origin main
```

### ⚠️ Dôležité poznámky:

- **NEPUSHOVAŤ** databázové súbory (`.db`, `.sqlite`) - sú v `.gitignore`
- **NEPUSHOVAŤ** `.env` súbory - obsahujú citlivé údaje
- **NEPUSHOVAŤ** `node_modules/` - veľké a zbytočné

---

## 📥 KROK 2: Download z GitHubu → Lokálne PC

### Na Windows (PowerShell):

```powershell
# 1. Prejsť do projektu
cd D:\Projects\EarningsTable

# 2. Stiahnuť najnovšie zmeny z GitHubu
git pull origin main

# 3. Skontrolovať zmeny
git log --oneline -5
```

### Alebo ak ešte nemáte projekt:

```powershell
# 1. Klonovať repozitár
cd D:\Projects
git clone https://github.com/dusan02/EarningsTable.git

# 2. Prejsť do projektu
cd EarningsTable

# 3. Inštalovať závislosti
npm install
```

---

## 🔧 KROK 3: Opraviť kód na lokálnom PC

### Na Windows:

```powershell
# 1. Otvoriť projekt v editore (napr. VS Code)
code .

# 2. Urobiť zmeny v kóde
# ... (vaše úpravy) ...

# 3. Otestovať lokálne
npm start
# alebo
npm run build
```

---

## 📤 KROK 4: Upload opráv z lokálneho PC → GitHub

### Na Windows (PowerShell):

```powershell
# 1. Prejsť do projektu
cd D:\Projects\EarningsTable

# 2. Skontrolovať zmeny
git status

# 3. Pridať zmenené súbory
git add .

# 4. Commit zmeny
git commit -m "Fix: Popis oprávy"

# 5. Push na GitHub
git push origin main
```

---

## 📥 KROK 5: Download opráv z GitHubu → SSH server

### Na SSH serveri (Linux):

```bash
# 1. Pripojiť sa na server
ssh root@bardusa

# 2. Prejsť do projektu
cd /var/www/earnings-table

# 3. Stiahnuť najnovšie zmeny
git pull origin main

# 4. Reštartovať PM2 službu
pm2 restart earnings-table

# 5. Skontrolovať status
pm2 status
pm2 logs earnings-table --lines 20
```

---

## 🚀 Rýchle skripty

### Na SSH serveri:

```bash
# Použiť skript pre rýchly pull a restart
cd /var/www/earnings-table
./quick-pull-and-restart.sh
```

### Na lokálnom PC (PowerShell):

```powershell
# Použiť skript pre rýchly push
cd D:\Projects\EarningsTable
.\quick-push.ps1
```

---

## 🔍 Kontrola synchronizácie

### Skontrolovať, či sú všetky prostredia synchronizované:

```bash
# Na SSH serveri
cd /var/www/earnings-table
git log --oneline -1

# Na lokálnom PC
cd D:\Projects\EarningsTable
git log --oneline -1

# Porovnať - mali by byť rovnaké commit hashe
```

---

## ⚠️ Dôležité upozornenia

### 1. **Konflikty v Git**

Ak sa vyskytnú konflikty:

```bash
# Na SSH serveri alebo lokálnom PC
git status  # zobraziť konflikty
git pull origin main  # pokúsiť sa merge
# Ak sú konflikty, manuálne ich vyriešiť
git add .
git commit -m "Merge: Resolve conflicts"
git push origin main
```

### 2. **Zálohovanie pred pushom**

```bash
# Na SSH serveri - zálohovať databázu
cp modules/database/prisma/prod.db modules/database/prisma/prod.db.backup

# Na lokálnom PC - zálohovať zmeny
git stash  # uložiť necommitnuté zmeny
```

### 3. **Čo NIKDY nepushovať**

- `.env` súbory (API kľúče, heslá)
- `*.db`, `*.sqlite` (databázy)
- `node_modules/` (závislosti)
- Súkromné kľúče a certifikáty

---

## 📝 Príklady commit správ

```bash
# Oprava bugu
git commit -m "Fix: Oprava zobrazenia cien v tabulke"

# Nová funkcia
git commit -m "Feat: Pridanie dark mode toggle"

# Update dát
git commit -m "Update: Synchronizácia produkčných dát"

# Refaktoring
git commit -m "Refactor: Optimalizácia API endpointov"

# Dokumentácia
git commit -m "Docs: Aktualizácia README"
```

---

## 🆘 Riešenie problémov

### Problém: "Permission denied" pri push

```bash
# Skontrolovať Git konfiguráciu
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# Skontrolovať SSH kľúče (ak používate SSH)
ssh -T git@github.com
```

### Problém: "Repository not found"

```bash
# Skontrolovať remote URL
git remote -v

# Ak je zlé, opraviť:
git remote set-url origin https://github.com/dusan02/EarningsTable.git
```

### Problém: "Merge conflict"

```bash
# Zobraziť konflikty
git status

# Automaticky vyriešiť (ak je to možné)
git pull origin main --rebase

# Alebo manuálne otvoriť súbory a vyriešiť konflikty
# Potom:
git add .
git commit -m "Merge: Resolve conflicts"
```

---

## 📚 Ďalšie zdroje

- [GitHub Repository](https://github.com/dusan02/EarningsTable)
- [SSH_DEPLOY_INSTRUCTIONS.md](SSH_DEPLOY_INSTRUCTIONS.md) - SSH príkazy
- [PRODUCTION_QUICK_REFERENCE.md](PRODUCTION_QUICK_REFERENCE.md) - Produkčné príkazy

