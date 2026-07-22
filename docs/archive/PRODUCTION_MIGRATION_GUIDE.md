# 🚀 Production Migration Guide - EarningsTable

## 📋 Overview

This guide provides complete documentation for migrating the EarningsTable application to production. It covers all components, configurations, and deployment procedures.

## 🏗️ Architecture Overview

```
EarningsTable/
├── modules/
│   ├── database/          # Prisma + SQLite database module
│   ├── cron/             # Cron jobs for data fetching
│   ├── web/              # Express.js web application
│   ├── shared/           # Shared types and utilities
│   └── docs/             # Documentation
├── index.html            # Main frontend (React-like UI)
├── simple-server.js      # Production server (fallback)
├── ecosystem.config.js   # PM2 configuration
├── deploy-production.sh  # Deployment script
└── .env                  # Environment variables
```

## 🗄️ Database Schema (Prisma)

### Location: `modules/database/prisma/schema.prisma`

**Provider**: SQLite (development) / PostgreSQL (production recommended)

### Tables:

#### 1. **FinhubData** (`finnhub_data`)

```prisma
model FinhubData {
  id               Int       @id @default(autoincrement())
  reportDate       DateTime
  symbol           String
  epsActual        Float?    // Actual EPS value
  epsEstimate      Float?    // Estimated EPS
  revenueActual    BigInt?   // Actual revenue (USD)
  revenueEstimate  BigInt?   // Estimated revenue
  hour             String?   // "amc" | "bmo" | "dmh" | null
  quarter          Int?
  year             Int?

  // LOGO fields
  logoUrl          String?   // "/logos/AAPL.webp"
  logoSource       String?   // "polygon" | "clearbit" | "yahoo" | "finnhub"
  logoFetchedAt    DateTime?

  createdAt        DateTime  @default(now())
  updatedAt        DateTime  @updatedAt

  @@unique([reportDate, symbol])
  @@index([reportDate])
  @@index([symbol])
  @@map("finnhub_data")
}
```

#### 2. **PolygonData** (`polygon_data`)

```prisma
model PolygonData {
  id               Int       @id @default(autoincrement())
  symbol           String    @unique
  symbolBoolean    Boolean?
  marketCap        BigInt?
  previousMarketCap BigInt?
  marketCapDiff    BigInt?
  marketCapBoolean Boolean?
  price            Float?
  previousCloseRaw Float?
  previousCloseAdj Float?
  previousCloseSource String?
  changeFromPrevClosePct Float?
  changeFromOpenPct Float?
  sessionRef String?   // 'premarket' | 'regular' | 'afterhours'
  qualityFlags Json? // JSON array of quality flags
  change           Float?   // Percentage change
  size             String?   // Mega, Large, Mid, Small
  name             String?   // Company name
  priceBoolean     Boolean?
  Boolean          Boolean?
  priceSource      String?   // 'pre'|'live'|'ah'|'min'|'day'|'prevDay'

  createdAt        DateTime  @default(now())
  updatedAt        DateTime  @updatedAt

  @@index([symbol])
  @@index([createdAt])
  @@map("polygon_data")
}
```

#### 3. **FinalReport** (`final_report`)

```prisma
model FinalReport {
  id            Int       @id @default(autoincrement())
  symbol        String    @unique
  name          String?
  size          String?
  marketCap     BigInt?
  marketCapDiff BigInt?
  price         Float?
  change        Float?
  epsActual     Float?
  epsEst        Float?
  epsSurp       Float?
  revActual     BigInt?
  revEst        BigInt?
  revSurp       Float?

  // LOGO fields (copied from FinhubData)
  logoUrl       String?
  logoSource    String?
  logoFetchedAt DateTime?

  createdAt     DateTime  @default(now())
  updatedAt     DateTime  @updatedAt

  @@index([symbol])
  @@index([createdAt])
  @@map("final_report")
}
```

#### 4. **CronStatus** (`cron_status`)

```prisma
model CronStatus {
  id            Int       @id @default(autoincrement())
  jobType       String    @unique // "finnhub" | "polygon" | "final_report" | "pipeline"
  lastRunAt     DateTime
  status        String    // "success" | "error" | "running"
  recordsProcessed Int?
  errorMessage  String?

  createdAt     DateTime  @default(now())
  updatedAt     DateTime  @updatedAt

  @@index([jobType])
  @@index([lastRunAt])
  @@map("cron_status")
}
```

### Migrations:

- **Location**: `modules/database/prisma/migrations/`
- **Latest**: `20251020094242_add_quality_flags`
- **Command**: `npx prisma migrate deploy`

## 🌐 Web Application

### Location: `modules/web/src/web.ts`

**Framework**: Express.js with TypeScript

### API Endpoints:

#### 1. **Health Check**

```
GET /health
Response: {
  "status": "ok",
  "timestamp": "2025-10-21T07:17:56.099Z",
  "database": {
    "connected": true,
    "tables": {
      "finhubData": 82,
      "polygonData": 82,
      "finalReport": 67
    }
  }
}
```

#### 2. **Earnings Data**

```
GET /api/earnings?date=2025-10-21&symbol=AAPL&limit=100
Response: {
  "success": true,
  "count": 82,
  "data": [...]
}
```

#### 3. **Final Report (Main Dashboard Data)**

```
GET /api/final-report?symbol=AAPL&limit=100
Response: {
  "success": true,
  "count": 67,
  "data": [...]
}
```

#### 4. **Cron Status**

```
GET /api/cron-status
Response: {
  "success": true,
  "lastUpdate": "2025-10-21T07:17:49.784Z",
  "cronStatuses": [...]
}
```

#### 5. **Main Dashboard**

```
GET /
Serves: index.html (React-like UI with mobile cards)
```

### Static Files:

- **Logos**: `/logos/{SYMBOL}.webp` → `modules/web/public/logos/`
- **Favicon**: `/favicon.ico` → `favicon.svg`

## ⏰ Cron Jobs

### Location: `modules/cron/src/cron-scheduler.ts`

### Schedule:

- **Data Clearing**: `0 7 * * 1-5` (07:00 every weekday, NY timezone)
- **Data Fetching**: `*/5 * * * 1-5` (Every 5 minutes, Mon-Fri, 07:05-06:55)

### Jobs:

#### 1. **Finnhub Job** (`FinnhubCronJob`)

- **Purpose**: Fetch earnings data and company logos
- **API**: `https://finnhub.io/api/v1/calendar/earnings`
- **Frequency**: Daily at 07:00 NY time
- **Output**: FinhubData table + logo files

#### 2. **Polygon Job** (`PolygonCronJob`)

- **Purpose**: Fetch market cap and price data
- **API**: `https://api.polygon.io/v3/reference/tickers/{symbol}`
- **Frequency**: Every 5 minutes during market hours
- **Output**: PolygonData table

#### 3. **Final Report Generation**

- **Purpose**: Combine FinhubData + PolygonData
- **Frequency**: After each data fetch
- **Output**: FinalReport table

#### 4. **Logo Processing**

- **Sources**: Yahoo Finance, Finnhub, Polygon, Clearbit
- **Format**: 256x256px WebP, 95% quality, transparent background
- **Storage**: `modules/web/public/logos/{SYMBOL}.webp`

### Commands:

```bash
# Run all jobs once
npm run run-all

# Run individual jobs
npm run finnhub_data:once
npm run polygon_data:once

# Start scheduler
npm start

# Run once with force
npm run start:once
```

## 🔧 Environment Variables

### Required Variables:

```bash
# Database
DATABASE_URL="file:/path/to/database.db"  # SQLite
# OR for PostgreSQL:
DATABASE_URL="postgresql://user:password@localhost:5432/earnings?schema=public"

# API Keys
FINNHUB_TOKEN="your_finnhub_api_key"
POLYGON_API_KEY="your_polygon_api_key"

# Server
PORT=5555
NODE_ENV=production

# Cron
CRON_TZ=America/New_York
CRON_EXPR=0 7 * * *
POLYGON_CRON_EXPR=0 */4 * * *

# Optional
USE_REDIS_LOCK=true
REDIS_URL=redis://127.0.0.1:6379
FORCE_RUN=false
SKIP_RESET_CHECK=false
```

### Current API Keys (Development):

- **Finnhub**: `d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0`
- **Polygon**: `Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX`

## 📦 Dependencies

### Root Package (`package.json`):

```json
{
  "dependencies": {
    "@prisma/client": "^6.17.1",
    "express": "^5.1.0",
    "react": "^18.2.0",
    "sqlite3": "^5.1.7"
  }
}
```

### Web Module (`modules/web/package.json`):

```json
{
  "dependencies": {
    "@prisma/client": "^5.20.0",
    "express": "^4.18.2",
    "dotenv": "^16.4.5"
  }
}
```

### Cron Module (`modules/cron/package.json`):

```json
{
  "dependencies": {
    "@prisma/client": "^5.20.0",
    "axios": "^1.7.2",
    "node-cron": "^3.0.3",
    "sharp": "^0.34.4",
    "redis": "^4.6.13"
  }
}
```

### Database Module (`modules/database/package.json`):

```json
{
  "dependencies": {
    "@prisma/client": "^5.20.0"
  }
}
```

## 🚀 Deployment

### 1. **Automated Deployment**

```bash
# Run deployment script
./deploy-production.sh
```

### 2. **Manual Deployment Steps**

#### Step 1: Server Setup

```bash
# Create project directory
sudo mkdir -p /var/www/earnings-table
sudo chown -R $USER:$USER /var/www/earnings-table

# Clone repository
git clone https://github.com/dusan02/EarningsTable.git /var/www/earnings-table
cd /var/www/earnings-table
```

#### Step 2: Install Dependencies

```bash
# Main project
npm install --production

# Database module
cd modules/database
npm install --production
npx prisma generate
cd ../..

# Cron module
cd modules/cron
npm install --production
cd ../..

# Shared module
cd modules/shared
npm install --production
cd ../..

# Web module
cd modules/web
npm install --production
cd ../..
```

#### Step 3: Environment Configuration

```bash
# Create .env file
cat > .env << EOF
DATABASE_URL="file:/var/www/earnings-table/modules/database/prisma/dev.db"
FINNHUB_TOKEN="d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
POLYGON_API_KEY="Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
PORT=5555
NODE_ENV=production
CRON_TZ=America/New_York
EOF
```

#### Step 4: Database Setup

```bash
cd modules/database
npx prisma migrate deploy
cd ../..
```

#### Step 5: PM2 Setup

```bash
# Install PM2 globally
npm install -g pm2

# Start web service
pm2 start simple-server.js --name "earnings-table" -- --port 5555

# Start cron service
pm2 start ecosystem.config.js

# Save PM2 configuration
pm2 save
pm2 startup
```

#### Step 6: Cron Job Setup

```bash
# Add cron job for data fetching
(crontab -l 2>/dev/null; echo "0 7 * * * cd /var/www/earnings-table/modules/cron && npm run run-all >> /var/log/earnings-cron.log 2>&1") | crontab -
```

### 3. **Production Server Options**

#### Option A: Simple Server (`simple-server.js`)

- **Use Case**: Quick deployment, basic functionality
- **Features**: API endpoints, static file serving
- **Start**: `node simple-server.js --port 5555`

#### Option B: Full Web Module (`modules/web/src/web.ts`)

- **Use Case**: Full-featured deployment
- **Features**: All API endpoints, health checks, cron status
- **Start**: `cd modules/web && npm start`

## 🔍 Monitoring & Maintenance

### Health Checks:

```bash
# API Health
curl http://localhost:5555/health

# Cron Status
curl http://localhost:5555/api/cron-status

# Data Count
curl http://localhost:5555/api/final-report
```

### Logs:

```bash
# PM2 Logs
pm2 logs earnings-table
pm2 logs earnings-cron

# Cron Logs
tail -f /var/log/earnings-cron.log

# Application Logs
tail -f /var/log/earnings-table.log
```

### Database Management:

```bash
# Prisma Studio
cd modules/database
npx prisma studio --port 5556

# Database Reset
cd modules/cron
npx tsx src/clear-db-cron.ts

# Manual Data Fetch
cd modules/cron
npm run run-all
```

## 🛠️ Troubleshooting

### Common Issues:

#### 1. **Database Connection Errors**

```bash
# Check DATABASE_URL
echo $DATABASE_URL

# Test Prisma connection
cd modules/database
npx prisma db push
```

#### 2. **API Key Issues**

```bash
# Test Finnhub API
curl "https://finnhub.io/api/v1/calendar/earnings?from=2025-10-21&to=2025-10-21&token=YOUR_TOKEN"

# Test Polygon API
curl "https://api.polygon.io/v3/reference/tickers/AAPL?apiKey=YOUR_KEY"
```

#### 3. **Cron Job Failures**

```bash
# Check cron status
cd modules/cron
npm run status

# Manual run
npm run run-all

# Check logs
pm2 logs earnings-cron
```

#### 4. **Logo Issues**

```bash
# Test logo serving
curl http://localhost:5555/logos/AAPL.webp

# Check logo directory
ls -la modules/web/public/logos/
```

## 📊 Performance Metrics

### Typical Performance:

- **Finnhub Job**: ~30-40 seconds (82 records)
- **Polygon Job**: ~10-20 seconds (82 records)
- **Final Report**: ~0.5 seconds (67 records)
- **Total Cycle**: ~45-60 seconds

### Resource Usage:

- **Memory**: ~200-300MB per process
- **CPU**: Low during idle, moderate during data fetch
- **Storage**: ~50-100MB for database + logos

## 🔐 Security Considerations

### Production Checklist:

- [ ] Change default API keys
- [ ] Use PostgreSQL instead of SQLite
- [ ] Set up SSL/TLS certificates
- [ ] Configure firewall rules
- [ ] Set up log rotation
- [ ] Configure backup procedures
- [ ] Set up monitoring alerts

### Environment Security:

```bash
# Secure .env file
chmod 600 .env

# Use environment-specific keys
# Never commit API keys to repository
```

## 📝 Maintenance Schedule

### Daily:

- Monitor cron job execution
- Check API health endpoints
- Review error logs

### Weekly:

- Update dependencies
- Review database size
- Check logo freshness

### Monthly:

- Backup database
- Review API usage limits
- Update documentation

---

**Last Updated**: 2025-10-21  
**Version**: 1.0.0  
**Status**: Production Ready
