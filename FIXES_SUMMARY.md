# 🔧 Súhrn opráv pre localhost server

## ✅ Opravené problémy

### 1. **Chýbajúce npm závislosti**

- ✅ Pridané `compression` a `dotenv` do `package.json`
- ⚠️ **Dôležité**: Použite `npm install --legacy-peer-deps` kvôli TypeScript konfliktu

### 2. **Prisma Query Engine - Windows podpora**

- ✅ Opravené hľadanie query engine súborov
- Predtým: hľadal len `.so.node` (Linux/Mac)
- Teraz: hľadá aj `.dll.node` (Windows) ✅
- V logu vidíte: `query_engine-windows.dll.node` - teraz sa nájde!

### 3. **Prisma Schema Engine**

- ✅ Opravené hľadanie schema engine súborov
- Teraz ignoruje `.node`, `.js`, `.d.ts` súbory
- Schema engine sa používa len pri migráciách, nie pri runtime

### 4. **DATABASE_URL warning**

- ✅ Pridané varovanie, ak DATABASE_URL nie je nastavený
- Zobrazí sa návod, ako ho nastaviť

### 5. **Chýbajúce súbory v route handlers**

- ✅ Pridané error handling pre `test-logos.html` a `test-logo-display.html`
- Vráti 404 namiesto crashu

## 🚀 Ako spustiť

### Krok 1: Inštalácia závislostí

```bash
npm install --legacy-peer-deps
```

### Krok 2: Nastavenie DATABASE_URL

Vytvorte `.env` súbor v root priečinku:

```env
DATABASE_URL="file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"
PORT=3001
```

Alebo nastavte environment premennú v PowerShell:

```powershell
$env:DATABASE_URL = "file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"
```

### Krok 3: Spustenie servera

```bash
npm run start:server
```

Server by mal bežať na: **http://localhost:3001**

## 📊 Čo sa zmenilo v kóde

### `simple-server.js`

1. **Prisma Query Engine detection** (riadok ~379-382):

   ```javascript
   // Predtým: len .so.node
   // Teraz: .so.node ALEBO .dll.node (Windows)
   const queryEngine = files.find(
     (f) =>
       f.includes("query_engine") &&
       (f.endsWith(".so.node") || f.endsWith(".dll.node"))
   );
   ```

2. **Prisma Schema Engine detection** (riadok ~385-393):

   ```javascript
   // Teraz ignoruje .node, .js, .d.ts súbory
   const schemaEngine = files.find((f) => {
     const isSchemaEngine = f.includes("schema-engine");
     const isNotNode = !f.includes(".node");
     const isNotJs = !f.endsWith(".js");
     const isNotDts = !f.endsWith(".d.ts");
     return isSchemaEngine && isNotNode && isNotJs && isNotDts;
   });
   ```

3. **DATABASE_URL warning** (riadok ~8-14):
   ```javascript
   if (!process.env.DATABASE_URL) {
     console.warn("[BOOT/web] ⚠️ DATABASE_URL is not set!");
     // ... návod
   }
   ```

## 🐛 Riešenie problémov

### Problém: `npm install` zlyhá

**Riešenie**: Použite `--legacy-peer-deps`:

```bash
npm install --legacy-peer-deps
```

**Dôvod**: Prisma vyžaduje TypeScript >=5.1.0, ale react-scripts potrebuje 4.9.5

### Problém: `DATABASE_URL=undefined`

**Riešenie**:

1. Vytvorte `.env` súbor v root priečinku
2. Alebo nastavte environment premennú v PowerShell

### Problém: `Query engine not found`

**Riešenie**: ✅ **OPRAVENÉ** - teraz hľadá aj `.dll.node` súbory pre Windows

### Problém: Server sa spustí, ale API nefunguje

**Kontrola**:

1. Skontrolujte, či DATABASE_URL je nastavený
2. Skontrolujte, či databáza existuje na správnej ceste
3. Skontrolujte konzolu pre error messages

## ✅ Status

- ✅ Server sa spustí
- ✅ Prisma client sa načíta
- ✅ Query engine sa nájde (Windows)
- ⚠️ DATABASE_URL musí byť nastavený (cez .env alebo env premennú)
- ✅ Všetky route handlers majú error handling

**Server je pripravený na použitie!** 🎉
