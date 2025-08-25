# 🚀 DEPLOYMENT CHECKLIST

## 📋 **Pred deploymentom:**

### **✅ Testy:**
- [ ] Všetky testy prebehli úspešne
- [ ] Databázové pripojenie funguje
- [ ] API volania fungujú
- [ ] Cron joby sú nastavené

### **✅ Konfigurácia:**
- [ ] `config.php` je správne nastavený
- [ ] API kľúče sú platné
- [ ] Databázové údaje sú správne
- [ ] Časové pásmo je nastavené

### **✅ Bezpečnosť:**
- [ ] Citlivé súbory sú v `.gitignore`
- [ ] Logy sú v bezpečnej zložke
- [ ] Backup súbory sú v archíve
- [ ] Testovacie súbory nie sú v produkcii

### **✅ Štruktúra:**
- [ ] Všetky súbory sú v správnych zložkách
- [ ] Dokumentácia je aktuálna
- [ ] README súbory sú vytvorené
- [ ] Projekt je organizovaný

## 🚀 **Deployment proces:**

### **1. Backup:**
```bash
make backup
```

### **2. Testy:**
```bash
make test
```

### **3. Synchronizácia:**
```bash
make sync
```

### **4. Overenie:**
- [ ] Webová stránka funguje
- [ ] API endpointy fungujú
- [ ] Cron joby bežia
- [ ] Logy sa vytvárajú

## 📊 **Post-deployment:**

### **✅ Monitoring:**
- [ ] Logy sa kontrolujú
- [ ] API limity sa sledujú
- [ ] Výkon sa meria
- [ ] Chyby sa riešia

### **✅ Údržba:**
- [ ] Denné zálohy
- [ ] Týždenné testy
- [ ] Mesačné aktualizácie
- [ ] Kvartálne audity

## 🎯 **Kritické body:**
- **API kľúče** - musia byť platné
- **Databáza** - musí byť dostupná
- **Cron joby** - musia bežať
- **Logy** - musia sa zapisovať

## 📞 **Kontakt:**
- **Problémy:** Skontrolujte logy v `logs/`
- **API problémy:** Overte API kľúče
- **Databáza:** Skontrolujte pripojenie
- **Cron joby:** Overte nastavenia

**Deployment je pripravený!** 🚀
