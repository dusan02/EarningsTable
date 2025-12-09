# 🔄 Migrácia earnings-cron na systemd

## 🎯 Cieľ

Migrovať `earnings-cron` z PM2 na systemd (rovnako ako `earnings-table`), aby sa vyriešil problém s PM2 watchdog a logy sa začali zapisovať do databázy.

---

## 📋 Postup

### 1. Vytvoriť systemd service

```bash
cd /srv/EarningsTable

cat > /etc/systemd/system/earnings-cron.service << 'EOF'
[Unit]
Description=Earnings Table Cron Jobs
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/srv/EarningsTable/modules/cron
Environment="NODE_ENV=production"
Environment="CRON_TZ=America/New_York"
Environment="DATABASE_URL=file:/srv/EarningsTable/modules/database/prisma/prod.db"
Environment="FINNHUB_TOKEN=d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
Environment="POLYGON_API_KEY=Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
ExecStart=/usr/bin/node node_modules/.bin/tsx src/main.ts start
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
systemctl daemon-reload
systemctl enable earnings-cron
systemctl start earnings-cron
systemctl status earnings-cron
```

### 3. Zastaviť PM2 proces

```bash
pm2 stop earnings-cron
# alebo úplne odstrániť
# pm2 delete earnings-cron
```

### 4. Skontrolovať logy

```bash
# Systemd logy
journalctl -u earnings-cron -f

# Alebo posledných 100 riadkov
journalctl -u earnings-cron -n 100
```

### 5. Skontrolovať, či sa logy zapisujú do databázy

```bash
# Počkať 5 minút (aby synthetic tests mohli bežať)
sleep 300

# Skontrolovať logy
sqlite3 -header -column modules/database/prisma/prod.db "SELECT id, jobType, status, datetime(startedAt, 'localtime') as startedAt, datetime(completedAt, 'localtime') as completedAt, duration, recordsProcessed FROM cron_execution_log ORDER BY startedAt DESC LIMIT 10;"
```

---

## ✅ Očakávané výsledky

Po migrácii:
- ✅ Cron job beží cez systemd (bez PM2 watchdog problému)
- ✅ Logy sa zapisujú do `CronExecutionLog` tabuľky
- ✅ `CronStatus` sa aktualizuje správne
- ✅ Synthetic tests bežia každú minútu
- ✅ FinnhubCronJob beží o 7:00 NY time
- ✅ PolygonCronJob beží každé 4 hodiny

---

## 🔍 Kontrola

### Skontrolovať status:
```bash
systemctl status earnings-cron
```

### Skontrolovať logy:
```bash
journalctl -u earnings-cron -f
```

### Skontrolovať databázu:
```bash
sqlite3 -header -column modules/database/prisma/prod.db "SELECT * FROM cron_execution_log ORDER BY startedAt DESC LIMIT 10;"
```

---

## 📝 Poznámky

- **WorkingDirectory:** `/srv/EarningsTable/modules/cron` (nie root)
- **ExecStart:** Používa `tsx` pre TypeScript súbory
- **Environment:** Všetky potrebné premenné sú nastavené
- **Restart:** `always` - systemd reštartuje proces, ak padne

---

## 🎯 Výhody systemd

1. **Stabilnejší** - nemá watchdog problém
2. **Lepšie logy** - `journalctl` je výkonnejší
3. **Automatický restart** - ak proces padne, systemd ho reštartuje
4. **Bez watchdog problému** - systemd neposiela signály každých 5 minút
5. **Konzistentné** - rovnaké riešenie ako `earnings-table`

