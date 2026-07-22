# 🔄 Migrácia z PM2 na systemd

## ✅ Zistenie

**Proces beží perfektne priamo (bez PM2):**

- ✅ Beží stabilne bez reštartov
- ✅ Keep-alive heartbeat funguje
- ✅ Žiadne SIGINT/SIGTERM eventy
- ✅ Server beží normálne

**Záver:** Problém je v **PM2 watchdog**, nie v kóde!

---

## 🎯 Riešenie: Použiť systemd namiesto PM2

Systemd je stabilnejší a nemá watchdog problém.

---

## 📋 Postup migrácie

### 1. Vytvoriť systemd service súbor

```bash
cd /srv/EarningsTable
sudo nano /etc/systemd/system/earnings-table.service
```

Skopírovať obsah z `earnings-table.service` súboru.

### 2. Aktivovať a spustiť service

```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable service (spustí sa pri boote)
sudo systemctl enable earnings-table

# Start service
sudo systemctl start earnings-table

# Skontrolovať status
sudo systemctl status earnings-table
```

### 3. Zastaviť PM2 proces

```bash
# Zastaviť PM2 proces (neodstraňovať, len pre prípad)
pm2 stop earnings-table

# Alebo úplne odstrániť z PM2
pm2 delete earnings-table
```

### 4. Skontrolovať logy

```bash
# Systemd logy
sudo journalctl -u earnings-table -f

# Alebo posledných 100 riadkov
sudo journalctl -u earnings-table -n 100
```

---

## 🔍 Kontrola

### Skontrolovať, či service beží:

```bash
sudo systemctl status earnings-table
```

### Skontrolovať logy:

```bash
sudo journalctl -u earnings-table -f
```

### Skontrolovať, či server odpovedá:

```bash
curl http://localhost:5555/api/health
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
sudo systemctl stop earnings-table
sudo systemctl disable earnings-table

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

- Proces beží stabilne priamo (bez PM2)
- Systemd nemá watchdog problém
- Systemd je natívna súčasť Linuxu
- Lepšie logy a monitoring
