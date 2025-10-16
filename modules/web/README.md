# Web Module

Web aplikácia pre zobrazenie earnings dát v HTML tabuľke.

## Funkcie

- **Express server** - webový server
- **HTML tabuľka** - zobrazenie earnings dát
- **REST API** - `/api/earnings` endpoint
- **Responsive design** - prispôsobenie pre rôzne zariadenia

## Štruktúra

```
web/
├── src/
│   ├── web.ts            # Express server s HTML tabuľkou
│   ├── config.ts         # Konfigurácia
│   └── repository.ts     # Databázové operácie
├── package.json          # Dependencies
└── README.md            # Dokumentácia
```

## Použitie

### Inštalácia dependencies:

```bash
cd modules/web
npm install
```

### Spustenie web servera:

```bash
npm start
```

### Development mode (s watch):

```bash
npm run dev
```

## API Endpoints

### GET /api/earnings

Vráti zoznam earnings reportov z databázy.

**Response:**

```json
[
  {
    "id": 1,
    "reportDate": "2025-01-13T00:00:00.000Z",
    "symbol": "AAPL",
    "epsActual": 1.52,
    "epsEstimate": 1.5,
    "revenueActual": 123900000000,
    "revenueEstimate": 120000000000,
    "hour": "amc",
    "quarter": 4,
    "year": 2024,
    "createdAt": "2025-01-13T10:00:00.000Z",
    "updatedAt": "2025-01-13T10:00:00.000Z"
  }
]
```

## Web rozhranie

Web rozhranie poskytuje:

- **HTML tabuľku** s earnings dátami
- **Responsive design** pre mobilné zariadenia
- **Automatické obnovovanie** dát každých 30 sekúnd
- **Filtrovanie** podľa symbolu
- **Zoradenie** podľa dátumu

## Environment premenné

```env
DATABASE_URL="file:./prisma/dev.db"
PORT=3000
```

## Port

Web server beží na porte **3000** (http://localhost:3000)
