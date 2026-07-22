# ⚡ Rýchly Git Sync - Prehľad

## 🔄 Workflow v 5 krokoch

```
1. SSH → Git      📤 Upload dát z produkcie
2. Git → PC       📥 Stiahnuť na lokálne PC
3. Opraviť        🔧 Upraviť kód
4. PC → Git       📤 Upload opráv
5. Git → SSH      📥 Stiahnuť oprávy na produkciu
```

---

## 📤 SSH → Git (Upload dát)

**Na SSH serveri:**

```bash
cd /var/www/earnings-table
./upload-data-to-git.sh "Update: Production data"
```

**Alebo manuálne:**

```bash
cd /var/www/earnings-table
git add .
git commit -m "Update: Production data"
git push origin main
```

---

## 📥 Git → PC (Download)

**Na Windows (PowerShell):**

```powershell
cd D:\Projects\EarningsTable
git pull origin main
```

---

## 🔧 Opraviť kód

**Na Windows:**

```powershell
# Otvoriť v editore
code .

# Urobiť zmeny...
# Otestovať lokálne
npm start
```

---

## 📤 PC → Git (Upload opráv)

**Na Windows (PowerShell):**

```powershell
cd D:\Projects\EarningsTable
.\quick-push.ps1 "Fix: Popis oprávy"
```

**Alebo manuálne:**

```powershell
git add .
git commit -m "Fix: Popis oprávy"
git push origin main
```

---

## 📥 Git → SSH (Download opráv)

**Na SSH serveri:**

```bash
cd /var/www/earnings-table
./quick-pull-and-restart.sh
```

**Alebo manuálne:**

```bash
cd /var/www/earnings-table
git pull origin main
pm2 restart earnings-table
```

---

## 📚 Kompletná dokumentácia

- **[GIT_SYNC_WORKFLOW.md](GIT_SYNC_WORKFLOW.md)** - Detailný návod
- **[SSH_DEPLOY_INSTRUCTIONS.md](SSH_DEPLOY_INSTRUCTIONS.md)** - SSH príkazy

---

## ⚠️ Dôležité

- **NEPUSHOVAŤ** `.env` súbory
- **NEPUSHOVAŤ** `*.db` databázy
- **NEPUSHOVAŤ** `node_modules/`
- Vždy **zálohovať** pred veľkými zmenami

