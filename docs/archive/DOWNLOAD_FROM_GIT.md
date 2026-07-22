# 📥 Stiahnutie zmien z GitHubu na lokálne PC

## Príkazy pre Windows PowerShell:

```powershell
# 1. Prejsť do projektu
cd D:\Projects\EarningsTable

# 2. Stiahnuť najnovšie zmeny z GitHubu
git pull origin main

# 3. (Voliteľné) Skontrolovať, čo sa stiahlo
git log --oneline -5
```

---

## Ak sa vyskytnú problémy:

### Problém: "divergent branches"

```powershell
cd D:\Projects\EarningsTable
git config pull.rebase false
git pull origin main --no-rebase
```

### Problém: "Your branch is ahead"

```powershell
# Zobraziť status
git status

# Ak chceš stiahnuť bez commitnutia lokálnych zmien:
git stash
git pull origin main
git stash pop
```

---

## Overenie:

```powershell
# Zobraziť posledné commity
git log --oneline -5

# Zobraziť status
git status
```

