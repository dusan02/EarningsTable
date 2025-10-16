# EarningsTable

Web aplikácia pre zobrazenie earnings dát s oddelenými cron jobmi pre načítavanie dát z Finnhub API.

## Štruktúra projektu

```
EarningsTable/
├── src/                    # Web aplikácia
│   ├── web.ts             # Express server s HTML tabuľkou
│   ├── config.ts          # Konfigurácia
│   └── repository.ts      # Databázové operácie
├── cron/                  # Samostatné cron joby
│   ├── src/
│   │   ├── cron.ts        # Hlavný earnings cron
│   │   ├── run-once.ts    # Jednorazové načítanie
│   │   ├── news_cron.ts   # Príklad nového cronu
│   │   ├── finnhub.ts     # Finnhub API integrácia
│   │   ├── repository.ts  # Databázové operácie
│   │   └── config.ts      # Konfigurácia
│   ├── prisma/            # Databázová schéma
│   └── package.json       # Dependencies pre cron joby
├── prisma/                # Hlavná databázová schéma
└── package.json           # Dependencies pre web aplikáciu
```

## Spustenie

### Web aplikácia:

```bash
npm start          # spustí web server na porte 3000
npm run web        # alternatívny spôsob
```

### Cron joby:

```bash
cd cron
npm install        # inštalácia dependencies

# Earnings cron job
npm run finnhub_data        # kontinuálny cron
npm run finnhub_data:once   # jednorazové načítanie

# Príklad nového cronu
npm run news_cron           # financial news cron
```

### Databáza:

```bash
npm run prisma:studio       # Prisma Studio na porte 5555
npm run prisma:generate     # generovanie Prisma clienta
npm run prisma:migrate      # migrácie databázy
```

## Environment premenné

Vytvorte `.env` súbor v koreňovom priečinku a v `cron/` priečinku:

```
DATABASE_URL="file:./dev.db"
FINNHUB_TOKEN="your_finnhub_api_key_here"
```

## Pridávanie nových cronov

1. Vytvorte nový súbor v `cron/src/` priečinku
2. Pridajte script do `cron/package.json`
3. Použite existujúce moduly (`config.ts`, `repository.ts`, atď.)

## Príklady nových cronov

- `news_cron.ts` - načítavanie finančných správ
- `prices_cron.ts` - načítavanie cien akcií
- `analysts_cron.ts` - načítavanie analystických odporúčaní

## Funkcie

- **Web aplikácia** - Express server s HTML tabuľkou earnings dát
- **Cron joby** - samostatné, škálovateľné cron joby pre rôzne dáta
- **Finnhub API** - integrácia s Finnhub API pre earnings dáta
- **SQLite databáza** - jednoduchá lokálna databáza
- **TypeScript** - čistý, čitateľný kód s typovou bezpečnosťou

## Odkazy

- **Web aplikácia:** http://localhost:3000
- **Prisma Studio:** http://localhost:5555
- **Finnhub API:** https://finnhub.io/

## Vývoj

```bash
# Web aplikácia
npm start

# Cron joby
cd cron
npm run finnhub_data:once   # test
npm run finnhub_data        # produkcia

# Databáza
npm run prisma:studio
```
