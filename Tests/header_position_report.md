# PODROBNÝ REPORT: Problém s pozíciou hlavičky tabuľky

## 🎯 **PROBLÉM**

Hlavička tabuľky sa zobrazuje na nesprávnom mieste a sticky pozícia nefunguje správne.

## 📋 **AKTUÁLNE SPRÁVANIE**

### **Po načítaní stránky:**

- Hlavička tabuľky sa zobrazuje na **riadkoch 3 a 4** (nesprávne miesto)
- Modrá čiara je viditeľná, ale na nesprávnej pozícii

### **Pri scrollovaní:**

- **Žiadna zafixovaná pozícia** - ani modrá čiara, ani hlavička sa nefixujú
- Hlavička sa pohybuje s obsahom ako normálny element

## 🔍 **ANALÝZA KÓDU**

### **1. CSS pre hlavičku tabuľky:**

```css
.earnings-table thead th {
  background: #e3f2fd;
  color: #374151;
  font-weight: 600;
  padding: 16px 8px;
  text-align: center;
  border: 1px solid #2563eb; /* tenká modrá čiara po celom obvode */
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  position: sticky;
  top: 220px; /* pod captionom */
  z-index: 80;
  white-space: nowrap;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
}
```

### **2. CSS pre caption:**

```css
.earnings-table caption {
  position: sticky;
  top: 180px; /* pod header a KPI kartami */
  z-index: 85;
  background: #fff;
  padding: 8px 0;
  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.06);
  border-bottom: 1px solid #e5e7eb;
}
```

### **3. HTML štruktúra:**

```html
<!-- Header -->
<header id="pageHeader" class="header" role="banner">
  <h1 class="earnings-title">Earnings Table</h1>
  <p class="subtitle">Today's Earnings Dashboard</p>
</header>

<!-- KPI Cards -->
<section id="kpis" class="winners-container" aria-label="Market Statistics">
  <!-- 12 kariet -->
</section>

<!-- Table Container -->
<section class="table-container" id="tableContainer">
  <div id="tableCaption">
    Today's Stock Earnings Data - Companies ranked by Market Cap
  </div>
  <table class="earnings-table">
    <thead id="tableHead">
      <tr>
        <th class="sortable-header" data-sort="rank">Rank</th>
        <!-- ďalšie stĺpce -->
      </tr>
    </thead>
    <tbody id="tableBody">
      <!-- dáta -->
    </tbody>
  </table>
</section>
```

## 🚨 **IDENTIFIKOVANÉ PROBLÉMY**

### **1. Nesprávne CSS selektory:**

- CSS pravidlo `.earnings-table thead th` sa aplikuje na všetky `<th>` elementy
- Ale `.sortable-header` má vlastné CSS pravidlo, ktoré môže prepísať sticky pozíciu

### **2. Konflikt CSS pravidiel:**

```css
/* Toto pravidlo môže prepísať sticky pozíciu */
.sortable-header {
  cursor: pointer;
  user-select: none;
  position: relative; /* ← TOTO PREPÍŠE position: sticky */
  padding: 12px 16px !important;
  min-height: 50px;
  background-color: #e3f2fd;
  transition: background-color 0.2s ease;
  text-align: center !important;
  border: 1px solid #2563eb;
}
```

### **3. Nesprávne z-index hodnoty:**

- Caption: `z-index: 85`
- Hlavička: `z-index: 80`
- Ale hlavička by mala byť vyššie ako caption

### **4. Fixné CSS hodnoty:**

- `top: 180px` pre caption
- `top: 220px` pre hlavičku
- Tieto hodnoty nemusí zodpovedať skutočným výškam elementov

## 🔧 **ODPORÚČANÉ OPRAVY**

### **1. Opraviť CSS selektory:**

```css
/* Pre caption */
.earnings-table caption {
  position: sticky;
  top: 180px;
  z-index: 85;
  background: #fff;
  padding: 8px 0;
  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.06);
  border-bottom: 1px solid #e5e7eb;
}

/* Pre hlavičku - špecifickejšie pravidlo */
.earnings-table thead th.sortable-header {
  position: sticky !important; /* prepísať position: relative */
  top: 220px !important;
  z-index: 90; /* vyššie ako caption */
  background: #e3f2fd;
  color: #374151;
  font-weight: 600;
  padding: 16px 8px;
  text-align: center;
  border: 1px solid #2563eb;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s ease;
}
```

### **2. Alebo použiť JavaScript pre dynamické výšky:**

```javascript
function setStickyHeights() {
  const header = document.getElementById("pageHeader");
  const kpis = document.getElementById("kpis");
  const caption = document.getElementById("tableCaption");

  const headerHeight = header ? header.offsetHeight : 0;
  const kpisHeight = kpis ? kpis.offsetHeight : 0;
  const captionHeight = caption ? caption.offsetHeight : 0;

  const captionTop = headerHeight + kpisHeight;
  const headerTop = captionTop + captionHeight;

  document.documentElement.style.setProperty(
    "--caption-top",
    `${captionTop}px`
  );
  document.documentElement.style.setProperty("--header-top", `${headerTop}px`);
}
```

### **3. CSS s CSS premennými:**

```css
.earnings-table caption {
  position: sticky;
  top: var(--caption-top, 180px);
  z-index: 85;
  /* ... */
}

.earnings-table thead th.sortable-header {
  position: sticky !important;
  top: var(--header-top, 220px) !important;
  z-index: 90;
  /* ... */
}
```

## 📊 **OČAKÁVANÉ VÝSLEDKY**

Po oprave by sa malo zobrazovať:

1. **Caption** na správnej pozícii pod KPI kartami
2. **Hlavička tabuľky** na správnej pozícii pod captionom
3. **Sticky pozícia** funguje pri scrollovaní
4. **Modrá čiara** okolo hlavičky je správne umiestnená

## 🎯 **PRIORITA OPRAV**

1. **VYSOKÁ:** Opraviť CSS konflikt s `.sortable-header`
2. **STREDNÁ:** Upraviť z-index hodnoty
3. **NÍZKA:** Implementovať dynamické výšky

---

**Vytvorené:** 2024-12-19  
**Súbor:** `public/dashboard-fixed.html`  
**Problém:** Hlavička tabuľky na nesprávnej pozícii
