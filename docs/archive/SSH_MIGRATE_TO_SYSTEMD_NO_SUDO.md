# 🔄 Migrácia z PM2 na systemd (ako root)

## ✅ Zistenie

**Proces beží perfektne priamo (bez PM2):**

- ✅ Beží stabilne bez reštartov
- ✅ Keep-alive heartbeat funguje
- ✅ Žiadne SIGINT/SIGTERM eventy

**Záver:** Problém je v **PM2 watchdog**, nie v kóde!

---

## 📋 Postup migrácie (ako root - bez sudo)

### 1. Vytvoriť systemd service súbor

```bash
cd /srv/EarningsTable

# Skontrolovať, či súbor existuje
ls -la earnings-table.service

# Ak nie, vytvoriť ho:
cat > /etc/systemd/system/earnings-table.service << 'EOF'
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
Environment="FINNHUB_TOKEN=<FINNHUB_TOKEN_REDACTED>"
Environment="POLYGON_API_KEY=Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
Environment="CRON_TZ=America/New_York"
ExecStart=/usr/bin/node /srv/EarningsTable/simple-server.js
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF
```

### 2. Aktivovať a spustiť service

```bash
# Reload systemd
systemctl daemon-reload

# Enable service (spustí sa pri boote)
systemctl enable earnings-table

# Start service
systemctl start earnings-table

# Skontrolovať status
systemctl status earnings-table
```

### 3. Zastaviť PM2 proces

```bash
# Zastaviť PM2 proces
pm2 stop earnings-table

# Alebo úplne odstrániť z PM2 (voliteľné)
# pm2 delete earnings-table
```

### 4. Skontrolovať logy

```bash
# Systemd logy v reálnom čase
journalctl -u earnings-table -f

# Alebo posledných 100 riadkov
journalctl -u earnings-table -n 100
```

---

## 🔍 Kontrola

### Skontrolovať, či service beží:

```bash
systemctl status earnings-table
```

### Skontrolovať logy:

```bash
journalctl -u earnings-table -f
```

### Skontrolovať, či server odpovedá:

```bash
curl http://localhost:5555/api/health
```

### Skontrolovať port:

```bash
netstat -tlnp | grep 5555
# alebo
ss -tlnp | grep 5555
```

---

## ✅ Výhody systemd

1. **Stabilnejší** - nemá watchdog problém
2. **Lepšia integrácia** - natívna podpora v Linuxe
3. **Lepšie logy** - `journalctl` je výkonnejší
4. **Automatický restart** - ak proces padne, systemd ho reštartuje
5. **Bez watchdog problému** - systemd neposiela signály každých 5 minút

---

## 🔄 Návrat na PM2 (ak by bolo potrebné)

Ak by si chcel vrátiť PM2:

```bash
# Zastaviť systemd service
systemctl stop earnings-table
systemctl disable earnings-table

# Spustiť PM2
pm2 start ecosystem.config.js --only earnings-table
```

---

## 📝 Poznámky

- **Port:** Service používa port 5555 (ako PM2)
- **Environment variables:** Všetky sú nastavené v service súbore
- **Restart:** `Restart=always` - systemd reštartuje proces, ak padne
- **Logs:** Logy sú v `journalctl`, nie v PM2 logoch

---

## 🎯 Odporúčanie

**Použiť systemd** - je to najlepšie riešenie, lebo:

- Proces beží stabilne priamo (bez PM2) ✅
- Systemd nemá watchdog problém ✅
- Systemd je natívna súčasť Linuxu ✅
- Lepšie logy a monitoring ✅
