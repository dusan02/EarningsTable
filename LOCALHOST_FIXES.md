# 🔧 Opravy pre spustenie localhost servera

## ✅ Opravené problémy

### 1. **Chýbajúce npm závislosti**

**Problém**: `simple-server.js` používa moduly, ktoré nie sú v `package.json`:

- `compression` - používaný pre gzip kompresiu
- `dotenv` - používaný pre načítanie environment premenných

**Riešenie**: ✅ Pridané do `package.json` dependencies:

```json
"compression": "^1.7.4",
"dotenv": "^16.3.1"
```

### 2. **Chýbajúce súbory v route handlers**

**Problém**: Server odkazuje na súbory, ktoré neexistujú:

- `test-logos.html`
- `test-logo-display.html`

**Riešenie**: ✅ Pridané error handling - ak súbor neexistuje, vráti 404 namiesto crashu

### 3. **Pridaný npm script pre spustenie servera**

**Riešenie**: ✅ Pridaný script do `package.json`:

```json
"start:server": "node simple-server.js"
```

## 🚀 Ako spustiť server

### Krok 1: Inštalácia závislostí

**Dôležité**: Kvôli konfliktu TypeScript verzií (Prisma vyžaduje >=5.1.0, ale react-scripts potrebuje 4.9.5), použite:

```bash
npm install --legacy-peer-deps
```

Alebo ak chcete inštalovať len nové závislosti (compression, dotenv):

```bash
npm install compression dotenv --legacy-peer-deps
```

### Krok 2: Nastavenie environment premenných

Vytvorte `.env` súbor v root priečinku (alebo použite existujúci):

```env
DATABASE_URL="file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"
PORT=3001
FINNHUB_TOKEN=your_token_here
POLYGON_API_KEY=your_key_here
```

### Krok 3: Spustenie servera

```bash
npm run start:server
```

Alebo priamo:

```bash
node simple-server.js
```

Server by mal bežať na: **http://localhost:3001**

## 📋 Kontrolný zoznam pred uploadom na GitHub

- ✅ Chýbajúce závislosti pridané
- ✅ Error handling pre chýbajúce súbory
- ✅ Syntax chyby opravené
- ✅ `.env` je v `.gitignore` (skontrolované)
- ⚠️ **DÔLEŽITÉ**: API keys sú v PowerShell skriptoch (`clear-and-restart.ps1`, `start-all.ps1`, atď.)
  - Tieto súbory obsahujú hardcoded API keys
  - Pred uploadom na GitHub buď:
    - Odstráňte API keys a použite environment premenné
    - Alebo pridajte tieto súbory do `.gitignore`
- ⚠️ Skontrolovať, či Prisma client je vygenerovaný (`npx prisma generate`)

## 🔍 Ďalšie poznámky

1. **Prisma Client**: Uistite sa, že Prisma client je vygenerovaný:

   ```bash
   npx prisma generate --schema=modules/database/prisma/schema.prisma
   ```

2. **Databáza**: Skontrolujte, či databázový súbor existuje na ceste v `DATABASE_URL`

3. **Port**: Default port je 3001, ale môže byť zmenený cez `PORT` environment premennú

## 🐛 Ak server stále nefunguje

1. **DATABASE_URL je undefined**:

   - Vytvorte `.env` súbor v root priečinku
   - Alebo nastavte environment premennú: `$env:DATABASE_URL = "file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"`

2. **Prisma Query Engine not found**:

   - Server hľadá `.dll.node` (Windows) alebo `.so.node` (Linux/Mac)
   - Skontrolujte, či Prisma client je vygenerovaný: `npx prisma generate --schema=modules/database/prisma/schema.prisma`
   - Oprava: ✅ Aktualizovaný kód na podporu Windows (.dll.node)

3. **npm install zlyhá**:

   - Použite `npm install --legacy-peer-deps` kvôli TypeScript konfliktu
   - Prisma vyžaduje TypeScript >=5.1.0, ale react-scripts potrebuje 4.9.5

4. Skontrolujte konzolu pre error messages
5. Skontrolujte, či databáza existuje na správnej ceste
6. Skontrolujte, či port 3001 nie je už obsadený iným procesom
