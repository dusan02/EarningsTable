# 🔧 Riešenie divergent branches na SSH serveri

## ⚠️ Problém

Keď sa zobrazí:
```
fatal: Need to specify how to reconcile divergent branches.
```

Znamená to, že lokálny branch na SSH serveri a remote branch na GitHube sa rozchádzajú.

---

## ✅ Riešenie 1: Merge (odporúčané)

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
```

---

## ✅ Riešenie 2: Rebase (ak chceš čistú históriu)

```bash
# Na SSH serveri
cd /var/www/earnings-table

# Nastaviť rebase stratégiu
git config pull.rebase true

# Stiahnuť a rebase
git pull origin main --rebase

# Ak sú konflikty, vyriešiť ich a potom:
git add .
git rebase --continue
```

---

## ✅ Riešenie 3: Force pull (ak nepotrebuješ lokálne zmeny)

⚠️ **POZOR:** Toto prepíše všetky lokálne zmeny na SSH serveri!

```bash
# Na SSH serveri
cd /var/www/earnings-table

# Uložiť lokálne zmeny (ak sú dôležité)
git stash

# Resetovať na remote
git fetch origin
git reset --hard origin/main

# Alebo ak chceš obnoviť uložené zmeny:
git stash pop
```

---

## 📋 Postup krok za krokom (odporúčané)

```bash
# 1. Prejsť do projektu
cd /var/www/earnings-table

# 2. Skontrolovať status
git status

# 3. Nastaviť merge stratégiu
git config pull.rebase false

# 4. Stiahnuť zmeny
git pull origin main --no-rebase

# 5. Ak sú konflikty, vyriešiť ich
# (Git ti ukáže, ktoré súbory majú konflikty)

# 6. Po vyriešení konfliktov:
git add .
git commit -m "Merge: Resolve conflicts with remote"

# 7. Teraz môžeš stiahnuť skripty
# (ak ešte nie sú stiahnuté)
git pull origin main

# 8. Nastaviť skripty ako spustiteľné
chmod +x quick-pull-and-restart.sh upload-data-to-git.sh
```

---

## 🔍 Diagnostika

```bash
# Zobraziť lokálne a remote branchy
git branch -a

# Zobraziť posledné commity
git log --oneline --graph --all -10

# Zobraziť rozdiely
git log HEAD..origin/main  # čo je na remote, ale nie lokálne
git log origin/main..HEAD  # čo je lokálne, ale nie na remote
```

---

## 💡 Prečo sa to stalo?

- Na SSH serveri boli lokálne commity, ktoré nie sú na GitHube
- Na GitHube boli commity (napr. naše nové skripty), ktoré nie sú na SSH serveri
- Git nevie automaticky rozhodnúť, ako ich zlúčiť

---

## ✅ Po vyriešení

Keď už máš všetko synchronizované, môžeš použiť:

```bash
# Rýchly pull a restart
./quick-pull-and-restart.sh

# Upload dát
./upload-data-to-git.sh "Update: Production data"
```

