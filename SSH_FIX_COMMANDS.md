# 🔧 Príkazy na vyriešenie divergent branches na SSH serveri

## ⚠️ Problém

Keď spustíš `git pull origin main` a zobrazí sa:
```
fatal: Need to specify how to reconcile divergent branches.
```

## ✅ Riešenie - KOPÍROVAŤ A SPUSTIŤ NA SSH SERVERI

```bash
# 1. Prejsť do projektu
cd /var/www/earnings-table

# 2. Nastaviť merge stratégiu
git config pull.rebase false

# 3. Stiahnuť a zlúčiť zmeny
git pull origin main --no-rebase

# 4. Ak sú konflikty, Git ti ukáže ktoré súbory
#    V tom prípade ich musíš manuálne vyriešiť a potom:
#    git add .
#    git commit -m "Merge: Resolve conflicts"

# 5. Nastaviť skripty ako spustiteľné
chmod +x quick-pull-and-restart.sh upload-data-to-git.sh

# 6. Overiť, že všetko funguje
git status
```

---

## 📤 Potom môžeš uploadovať dáta:

```bash
# Použiť skript
./upload-data-to-git.sh "Update: Production data"

# Alebo manuálne
git add .
git commit -m "Update: Production data sync $(date +%Y-%m-%d)"
git push origin main
```

---

## 🔍 Ak chceš vidieť, čo sa stalo:

```bash
# Zobraziť históriu
git log --oneline --graph --all -10

# Zobraziť status
git status
```

