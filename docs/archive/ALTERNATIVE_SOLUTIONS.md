# 🔄 Alternatívne riešenia - PM2 watchdog problém

## 🎯 Nový prístup

Namiesto workaroundu, ktorý ignoruje signály, skúsme **zistiť skutočnú príčinu** alebo **použiť iný prístup**.

---

## 🔍 Riešenie 1: Spustiť proces priamo (bez PM2) - diagnostika

**Cieľ:** Zistiť, či proces skutočne padá, alebo len PM2 posiela signály.

### Príkazy na SSH:
```bash
cd /srv/EarningsTable

# Zastaviť PM2 proces
pm2 stop earnings-table

# Spustiť proces priamo (v screen alebo tmux)
screen -S earnings-test
# alebo: tmux new -s earnings-test

# Spustiť proces priamo
node simple-server.js

# Nechať bežať 10-15 minút a sledovať, či padá
# Ak nepadá = problém je v PM2
# Ak padá = problém je v kóde
```

**Výhody:**
- Zistíme, či proces skutočne padá
- Zistíme, či je problém v PM2 alebo v kóde

---

## 🔧 Riešenie 2: Použiť systemd namiesto PM2

**Cieľ:** Systemd je stabilnejší a nemá watchdog problém.

### Vytvoriť systemd service:
```bash
sudo nano /etc/systemd/system/earnings-table.service
```

### Obsah:
```ini
[Unit]
Description=Earnings Table API Server
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/srv/EarningsTable
Environment="NODE_ENV=production"
Environment="PORT=5555"
Environment="DATABASE_URL=file:/srv/EarningsTable/modules/database/prisma/prod.db"
Environment="FINNHUB_TOKEN=d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
Environment="POLYGON_API_KEY=Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
ExecStart=/usr/bin/node /srv/EarningsTable/simple-server.js
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

### Aktivovať:
```bash
sudo systemctl daemon-reload
sudo systemctl enable earnings-table
sudo systemctl start earnings-table
sudo systemctl status earnings-table
```

**Výhody:**
- Systemd je stabilnejší
- Nemá watchdog problém
- Lepšia integrácia so systémom

---

## 🔧 Riešenie 3: Aktualizovať PM2 a vypnúť watchdog

**Cieľ:** Aktualizovať PM2 na najnovšiu verziu a vypnúť watchdog.

### Príkazy:
```bash
# Aktualizovať PM2
npm install -g pm2@latest
pm2 update

# Skontrolovať PM2 verziu
pm2 --version

# Skontrolovať watchdog nastavenia
pm2 conf earnings-table

# Možno pridať do ecosystem.config.js:
# pmx: false  # Vypnúť PM2 monitoring
```

---

## 🔧 Riešenie 4: Odstrániť workaround a pridať lepšie error handling

**Cieľ:** Namiesto ignorovania signálov, zistiť, prečo proces padá.

### Zmeny v simple-server.js:
1. Odstrániť workaround (ignorovanie SIGINT/SIGTERM)
2. Pridať lepšie error handling:
   - `process.on('uncaughtException')`
   - `process.on('unhandledRejection')`
   - Lepšie logovanie chýb

### Príklad:
```javascript
// Catch uncaught exceptions
process.on('uncaughtException', (error) => {
  console.error('💥 Uncaught Exception:', error);
  console.error('Stack:', error.stack);
  // Log and exit gracefully
  process.exit(1);
});

// Catch unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
  console.error('💥 Unhandled Rejection at:', promise);
  console.error('Reason:', reason);
  // Log but don't exit (might be recoverable)
});
```

---

## 🔧 Riešenie 5: Vypnúť PM2 autorestart a nechať proces bežať

**Cieľ:** Vypnúť PM2 autorestart a nechať proces bežať bez reštartov.

### Zmena v ecosystem.config.js:
```javascript
{
  name: "earnings-table",
  // ...
  autorestart: false,  // Vypnúť autorestart
  // ...
}
```

**Poznámka:** Toto nie je ideálne, lebo ak proces padne, nebude sa reštartovať.

---

## 📊 Odporúčanie

**Najlepšie riešenie:**
1. **Najprv:** Riešenie 1 (spustiť priamo) - zistiť, či proces skutočne padá
2. **Ak proces nepadá:** Riešenie 2 (systemd) - stabilnejšie riešenie
3. **Ak proces padá:** Riešenie 4 (lepšie error handling) - zistiť, prečo padá

---

## 🎯 Postup

1. **Spustiť proces priamo** (Riešenie 1) - zistiť skutočnú príčinu
2. **Na základe výsledku** zvoliť najlepšie riešenie
3. **Implementovať riešenie** a otestovať

---

## ⚠️ Poznámka

Workaround s ignorovaním signálov **nie je ideálne riešenie**, lebo:
- Skrýva skutočný problém
- Môže spôsobiť problémy pri graceful shutdown
- Nevyrieši základný problém

**Lepšie je zistiť skutočnú príčinu a vyriešiť ju.**

