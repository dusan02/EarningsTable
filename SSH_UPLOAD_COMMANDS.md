# 📤 Príkazy pre upload dát z SSH servera na GitHub

## ⚠️ Dôležité
Tieto príkazy musia byť spustené **na SSH serveri** (nie na Windows)!

---

## 🔗 Krok 1: Pripojiť sa na SSH server

```bash
ssh root@bardusa
# alebo
ssh your-username@your-server-ip
```

---

## 📤 Krok 2: Upload dát na GitHub

```bash
# 1. Prejsť do projektu
cd /var/www/earnings-table

# 2. Skontrolovať status
git status

# 3. Pridať všetky zmeny
git add .

# 4. Commit s dátumom
git commit -m "Update: Production data sync $(date +%Y-%m-%d)"

# 5. Push na GitHub
git push origin main
```

---

## ✅ Overenie

```bash
# Skontrolovať, či push prebehol úspešne
git log --oneline -1

# Skontrolovať remote
git remote -v
```

---

## 🚀 Rýchla verzia (ak už máš skripty)

```bash
cd /var/www/earnings-table
./upload-data-to-git.sh "Update: Production data"
```

---

## ⚠️ Ak sa vyskytnú problémy

### Problém: "Permission denied"
```bash
# Skontrolovať Git konfiguráciu
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

### Problém: "Repository not found" alebo "Authentication failed"
```bash
# Skontrolovať remote URL
git remote -v

# Ak je zlé, opraviť:
git remote set-url origin https://github.com/dusan02/EarningsTable.git
```

### Problém: "Nothing to commit"
```bash
# To je v poriadku - znamená to, že nie sú žiadne zmeny na commitnutie
# Môžeš preskočiť commit a push
```

---

## 📝 Alternatívne commit správy

```bash
# S dátumom a časom
git commit -m "Update: Production data sync $(date +%Y-%m-%d\ %H:%M:%S)"

# S konkrétnym popisom
git commit -m "Update: Production data sync - $(date +%Y-%m-%d)"

# Jednoduchá správa
git commit -m "Sync production data"
```

