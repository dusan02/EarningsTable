# Deployment Guide

## Lokálne spustenie

### 1. Inštalácia dependencies

```bash
# Hlavný projekt
npm install

# Database modul
cd modules/database
npm install

# Cron modul
cd ../cron
npm install

# Web modul
cd ../web
npm install

# Shared modul
cd ../shared
npm install
```

### 2. Environment setup

Vytvorte `.env` súbor v root priečinku:

```env
FINNHUB_TOKEN=your_finnhub_api_key
DATABASE_URL=file:./prisma/dev.db
CRON_TZ=America/New_York
CRON_EXPR=0 7 * * *
PORT=3000
NODE_ENV=development
```

### 3. Databázová migrácia

```bash
cd modules/database
npm run migrate
```

### 4. Spustenie aplikácie

#### Web server:

```bash
cd modules/web
npm start
```

#### Cron job:

```bash
cd modules/cron
npm run finnhub_data
```

## Produkčné nasadenie

### Docker (odporúčané)

#### Dockerfile pre web modul:

```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY modules/web/package*.json ./
RUN npm ci --only=production
COPY modules/web/src ./src
COPY modules/shared ./shared
COPY modules/database ./database
EXPOSE 3000
CMD ["npm", "start"]
```

#### Dockerfile pre cron modul:

```dockerfile
FROM node:18-alpine
WORKDIR /app
COPY modules/cron/package*.json ./
RUN npm ci --only=production
COPY modules/cron/src ./src
COPY modules/shared ./shared
COPY modules/database ./database
CMD ["npm", "run", "finnhub_data"]
```

### Environment premenné pre produkciu

```env
FINNHUB_TOKEN=your_production_finnhub_token
DATABASE_URL=postgresql://user:password@host:port/database
CRON_TZ=America/New_York
CRON_EXPR=0 7 * * *
PORT=3000
NODE_ENV=production
```

### Docker Compose

```yaml
version: "3.8"
services:
  web:
    build: ./modules/web
    ports:
      - "3000:3000"
    environment:
      - DATABASE_URL=postgresql://postgres:password@db:5432/earnings
      - FINNHUB_TOKEN=${FINNHUB_TOKEN}
    depends_on:
      - db

  cron:
    build: ./modules/cron
    environment:
      - DATABASE_URL=postgresql://postgres:password@db:5432/earnings
      - FINNHUB_TOKEN=${FINNHUB_TOKEN}
    depends_on:
      - db

  db:
    image: postgres:15
    environment:
      - POSTGRES_DB=earnings
      - POSTGRES_USER=postgres
      - POSTGRES_PASSWORD=password
    volumes:
      - postgres_data:/var/lib/postgresql/data

volumes:
  postgres_data:
```

## Monitoring

### Health checks

- Web server: `GET http://localhost:3000/api/earnings`
- Cron job: Kontrola logov

### Logy

- Web server: Console output
- Cron job: Console output s timestampom

### Metriky

- Počet earnings reportov v databáze
- Čas posledného cron jobu
- API response times
