# Database Module

Databázový modul pre EarningsTable aplikáciu.

## Funkcie

- **Prisma ORM** - moderný ORM pre TypeScript/JavaScript
- **SQLite databáza** - jednoduchá lokálna databáza
- **Migrácie** - verziovanie databázovej schémy
- **Prisma Studio** - GUI pre správu databázy

## Štruktúra

```
database/
├── prisma/
│   ├── schema.prisma      # Databázová schéma
│   ├── migrations/        # Migračné súbory
│   └── dev.db            # SQLite databáza
├── package.json          # Dependencies
└── README.md            # Dokumentácia
```

## Použitie

### Inštalácia dependencies:

```bash
cd modules/database
npm install
```

### Generovanie Prisma clienta:

```bash
npm run generate
```

### Spustenie migrácií:

```bash
npm run migrate
```

### Prisma Studio:

```bash
npm run studio
```

## Databázová schéma

### EarningsReport tabuľka

| Pole            | Typ      | Popis                           |
| --------------- | -------- | ------------------------------- |
| id              | Int      | Primárny kľúč                   |
| reportDate      | DateTime | Dátum reportu                   |
| symbol          | String   | Ticker symbol                   |
| epsActual       | Float?   | Skutočné EPS                    |
| epsEstimate     | Float?   | Očakávané EPS                   |
| revenueActual   | BigInt?  | Skutočný revenue (USD)          |
| revenueEstimate | BigInt?  | Očakávaný revenue (USD)         |
| hour            | String?  | Čas reportu ("bmo"/"amc"/"dmh") |
| quarter         | Int?     | Štvrťrok                        |
| year            | Int?     | Rok                             |
| createdAt       | DateTime | Čas vytvorenia záznamu          |
| updatedAt       | DateTime | Čas poslednej aktualizácie      |

**Indexy:**

- `@@unique([reportDate, symbol])` - zabráni duplikátom
- `@@index([reportDate])` - rýchle vyhľadávanie podľa dátumu
- `@@index([symbol])` - rýchle vyhľadávanie podľa symbolu

## Environment premenné

```env
DATABASE_URL="file:./prisma/dev.db"
```
