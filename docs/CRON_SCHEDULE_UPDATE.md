# ⏰ Cron Schedule Update - Benzinga 5-Minute Updates

## 🚀 **NOVÁ ARCHITEKTÚRA CRONOV**

### **DENNÉ CRONY (02:00 NY time):**

| Cron                     | Súbor                                | Frekvencia | Účel                                      |
| ------------------------ | ------------------------------------ | ---------- | ----------------------------------------- |
| **1. Master**            | `1_enhanced_master_cron.php`         | Denné      | Orchestrácia všetkých cronov              |
| **2. Clear Data**        | `2_clear_old_data.php`               | 02:00      | Čistenie starých dát                      |
| **3. Daily Setup**       | `3_daily_data_setup_static.php`      | 02:05      | Statické dáta (tickery, market cap)       |
| **4. Regular Updates**   | `4_regular_data_updates_dynamic.php` | Denné      | Dynamické dáta (ceny, zmeny)              |
| **5. Benzinga Guidance** | `5_benzinga_guidance_updates.php`    | Denné      | Corporate guidance + consensus porovnanie |

### **2-MINÚTOVÉ CRONY:**

| Cron          | Súbor                        | Frekvencia       | Účel                                    |
| ------------- | ---------------------------- | ---------------- | --------------------------------------- |
| **1. Master** | `1_enhanced_master_cron.php` | **Každú minútu** | **VŠETKO** - orchestrácia všetkých úloh |

## 🔧 **NASTAVENIE V CPANEL:**

### **JEDINÝ CRON (každú minútu):**

```bash
# Master cron - riadi všetko (denné aj 2-minútové úlohy)
* * * * * php /home/username/public_html/cron/1_enhanced_master_cron.php
```

## 📈 **VÝHODY NOVEJ ARCHITEKTÚRY:**

## 🎯 **ZÁVER:**

**Nové nastavenie:**

- **Master cron** - riadi všetko každú minútu
- **Denné úlohy** - Clear Data (02:00), Daily Setup (02:05)
- **2-minútové úlohy** - spúšťajú sa každých 2 minúty
- **Polygon + Benzinga** - bežia **SEKVENČNE** (stabilnejšie pre produkciu)
- **Consensus porovnanie** - integrované do Benzinga guidance

**Výsledok:** Jeden cron riadi všetko + stabilné sekvenčné spustenie!
