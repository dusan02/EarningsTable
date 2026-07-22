# 🚀 Production Quick Reference - EarningsTable

## 📋 Essential Information

### 🗄️ Database

- **Schema**: `modules/database/prisma/schema.prisma`
- **Provider**: SQLite (dev) / PostgreSQL (prod recommended)
- **Tables**: FinhubData, PolygonData, FinalReport, CronStatus
- **Migrations**: `modules/database/prisma/migrations/`
- **Command**: `npx prisma migrate deploy`

### 🌐 Web Application

- **Main Server**: `modules/web/src/web.ts` (Express.js + TypeScript)
- **Fallback Server**: `simple-server.js` (Node.js)
- **Port**: 5555
- **Frontend**: `index.html` (React-like UI with mobile cards)

### ⏰ Cron Jobs

- **Scheduler**: `modules/cron/src/cron-scheduler.ts`
- **Schedule**:
  - Data clearing: `0 7 * * 1-5` (07:00 weekdays)
  - Data fetching: `*/5 * * * 1-5` (Every 5 min, 07:05-06:55)
- **Jobs**: Finnhub (earnings), Polygon (market data), Final Report (combined)

### 🔑 Environment Variables

```bash
DATABASE_URL="file:/path/to/database.db"
FINNHUB_TOKEN="d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
POLYGON_API_KEY="Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"
PORT=5555
NODE_ENV=production
CRON_TZ=America/New_York
```

## 🚀 Quick Deployment

### 1. Automated

```bash
./deploy-production.sh
```

### 2. Manual

```bash
# Clone & setup
git clone https://github.com/dusan02/EarningsTable.git /var/www/earnings-table
cd /var/www/earnings-table

# Install dependencies
npm install --production
cd modules/database && npm install --production && npx prisma generate && cd ../..
cd modules/cron && npm install --production && cd ../..
cd modules/shared && npm install --production && cd ../..
cd modules/web && npm install --production && cd ../..

# Setup database
cd modules/database && npx prisma migrate deploy && cd ../..

# Start with PM2
npm install -g pm2
pm2 start simple-server.js --name "earnings-table" -- --port 5555
pm2 start ecosystem.config.js
pm2 save && pm2 startup

# Setup cron
(crontab -l 2>/dev/null; echo "0 7 * * * cd /var/www/earnings-table/modules/cron && npm run run-all >> /var/log/earnings-cron.log 2>&1") | crontab -
```

## 📊 API Endpoints

| Endpoint               | Method | Description                 |
| ---------------------- | ------ | --------------------------- |
| `/`                    | GET    | Main dashboard (index.html) |
| `/health`              | GET    | Health check with DB status |
| `/api/final-report`    | GET    | Main data for dashboard     |
| `/api/earnings`        | GET    | Raw earnings data           |
| `/api/cron-status`     | GET    | Cron job status             |
| `/logos/{SYMBOL}.webp` | GET    | Company logos               |
| `/favicon.ico`         | GET    | Favicon                     |

## 🔧 Key Commands

### Development

```bash
# Start web app
cd modules/web && npm start

# Start cron jobs
cd modules/cron && npm run run-all

# Prisma Studio
cd modules/database && npx prisma studio --port 5556

# Clear database
node clear-all-data.js
```

### Production

```bash
# PM2 management
pm2 status
pm2 logs earnings-table
pm2 restart earnings-table
pm2 stop earnings-table

# Manual cron run
cd modules/cron && npm run run-all

# Health check
curl http://localhost:5555/health
```

## 📁 Key Files

### Configuration

- `modules/database/prisma/schema.prisma` - Database schema
- `modules/shared/src/config.ts` - Shared configuration
- `modules/cron/src/cron-scheduler.ts` - Cron scheduler
- `ecosystem.config.js` - PM2 configuration
- `.env` - Environment variables

### Deployment

- `deploy-production.sh` - Automated deployment script
- `simple-server.js` - Production server (fallback)
- `modules/web/src/web.ts` - Full-featured server

### Documentation

- `PRODUCTION_MIGRATION_GUIDE.md` - Complete migration guide
- `SETUP_GUIDE.md` - Development setup
- `QUICK_START.md` - Quick start guide

## 🗄️ Database Tables

| Table          | Purpose       | Key Fields                                                                                           |
| -------------- | ------------- | ---------------------------------------------------------------------------------------------------- |
| `finnhub_data` | Earnings data | symbol, reportDate, epsActual, epsEstimate, revenueActual, revenueEstimate, logoUrl                  |
| `polygon_data` | Market data   | symbol, marketCap, price, change, size, name                                                         |
| `final_report` | Combined data | symbol, name, size, marketCap, price, change, epsActual, epsEst, epsSurp, revActual, revEst, revSurp |
| `cron_status`  | Job status    | jobType, lastRunAt, status, recordsProcessed, errorMessage                                           |

## ⏰ Cron Schedule

| Time       | Job             | Description                                                     |
| ---------- | --------------- | --------------------------------------------------------------- |
| 07:00      | Data Clearing   | Clear all tables (weekdays only)                                |
| 07:05+     | Data Fetching   | Fetch earnings + market data (every 5 min until 06:55 next day) |
| Continuous | Logo Processing | Download company logos (batch processing)                       |

## 🔍 Monitoring

### Health Checks

```bash
# API health
curl http://localhost:5555/health

# Cron status
curl http://localhost:5555/api/cron-status

# Data count
curl http://localhost:5555/api/final-report | jq '.count'
```

### Logs

```bash
# PM2 logs
pm2 logs earnings-table
pm2 logs earnings-cron

# Cron logs
tail -f /var/log/earnings-cron.log
```

## 🛠️ Troubleshooting

### Common Issues

1. **Database connection**: Check `DATABASE_URL`
2. **API keys**: Verify `FINNHUB_TOKEN` and `POLYGON_API_KEY`
3. **Cron failures**: Check `pm2 logs earnings-cron`
4. **Logo issues**: Verify `/logos/` directory permissions

### Quick Fixes

```bash
# Restart services
pm2 restart all

# Clear and reload data
cd modules/cron && npm run run-all

# Check database
cd modules/database && npx prisma studio
```

---

**For complete documentation, see [PRODUCTION_MIGRATION_GUIDE.md](PRODUCTION_MIGRATION_GUIDE.md)**
