# 🚀 Earnings Table - Deployment Guide

## 📋 Prerequisites

- Node.js 18+
- Your existing EarningsTable project with database
- API keys for Finnhub and Polygon

## 🛠️ Setup Instructions

### 1. Install Dependencies

```bash
# Install server dependencies
npm install express cors @prisma/client prisma
npm install -D @types/express @types/cors @types/node typescript ts-node-dev

# Install client dependencies (if not already installed)
npm install react react-dom @types/react @types/react-dom
npm install -D tailwindcss autoprefixer postcss
```

### 2. Environment Variables

Create `.env` file in your project root:

```env
# Database
DATABASE_URL="file:D:/Projects/EarningsTable/modules/database/prisma/dev.db"

# API Keys
FINNHUB_TOKEN="d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
POLYGON_API_KEY="Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX"

# Server
PORT=3000
NODE_ENV=production
```

### 3. Build and Start

```bash
# Build TypeScript
npm run build

# Start server
npm start
```

## 🌐 API Endpoints

### GET /api/final-report

Returns all FinalReport data for the table.

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "symbol": "AAPL",
      "name": "Apple Inc.",
      "size": "Mega",
      "marketCap": "2500000000000",
      "marketCapDiff": "50000000000",
      "price": 150.25,
      "change": 2.15,
      "epsActual": 1.52,
      "epsEst": 1.43,
      "epsSurp": 6.29,
      "revActual": "89498000000",
      "revEst": "88500000000",
      "revSurp": 1.13
    }
  ],
  "count": 12,
  "timestamp": "2025-10-14T16:00:00.000Z"
}
```

### GET /api/final-report/stats

Returns summary statistics.

**Response:**

```json
{
  "success": true,
  "data": {
    "totalCompanies": 12,
    "sizeDistribution": {
      "Mega": 5,
      "Large": 3,
      "Mid": 2,
      "Small": 2
    },
    "averageChange": 1.85,
    "averageEpsSurprise": 8.45,
    "averageRevSurprise": 2.15
  }
}
```

### GET /api/final-report/:symbol

Returns data for specific company.

### POST /api/final-report/refresh

Triggers data refresh (runs cron jobs).

## 🔄 Data Flow

1. **Cron Jobs** → Update FinhubData and PolygonData
2. **generateFinalReport()** → Creates FinalReport entries
3. **API Endpoint** → Serves data to React app
4. **React Component** → Displays formatted table

## 📱 Features

- **Real-time Data**: Auto-refresh every 5 minutes
- **Responsive Design**: Works on all devices
- **Dark/Light Theme**: Toggle between themes
- **Search & Sort**: Interactive table functionality
- **Error Handling**: Graceful error states
- **Loading States**: Smooth loading indicators

## 🚀 Production Deployment

### Option 1: Vercel (Recommended)

1. **Connect your GitHub repository to Vercel**
2. **Set environment variables in Vercel dashboard**
3. **Deploy automatically on git push**

### Option 2: Docker

```dockerfile
FROM node:18-alpine

WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production

COPY . .
RUN npm run build

EXPOSE 3000
CMD ["npm", "start"]
```

### Option 3: Traditional Server

```bash
# Install PM2 for process management
npm install -g pm2

# Start with PM2
pm2 start server.js --name earnings-table

# Save PM2 configuration
pm2 save
pm2 startup
```

## 🔧 Configuration

### Auto-refresh Settings

Modify refresh interval in `web-integration.tsx`:

```typescript
// Auto-refresh every 5 minutes
const interval = setInterval(fetchData, 5 * 60 * 1000);
```

### CORS Settings

Configure CORS in `server.ts` for production:

```typescript
app.use(
  cors({
    origin: ["https://yourdomain.com", "https://www.yourdomain.com"],
    credentials: true,
  })
);
```

## 📊 Monitoring

### Health Check

```bash
curl http://localhost:3000/api/health
```

### Logs

```bash
# View server logs
pm2 logs earnings-table

# View real-time logs
pm2 logs earnings-table --lines 100
```

## 🛡️ Security

- **Environment Variables**: Never commit API keys
- **CORS**: Configure for your domain only
- **Rate Limiting**: Add rate limiting for production
- **HTTPS**: Use SSL certificates in production

## 🔍 Troubleshooting

### Common Issues

1. **Database Connection Error**

   - Check DATABASE_URL path
   - Ensure database file exists
   - Run `npx prisma generate`

2. **API Key Errors**

   - Verify FINNHUB_TOKEN and POLYGON_API_KEY
   - Check API key permissions

3. **Build Errors**
   - Run `npm install` to ensure dependencies
   - Check TypeScript configuration

### Debug Mode

```bash
# Enable debug logging
DEBUG=* npm run dev
```

## 📈 Performance

- **Database Indexing**: Ensure proper indexes on symbol columns
- **Caching**: Consider Redis for API response caching
- **CDN**: Use CDN for static assets
- **Compression**: Enable gzip compression

## 🎯 Next Steps

1. **Add Authentication**: Implement user login
2. **Export Features**: Add CSV/Excel export
3. **Real-time Updates**: WebSocket integration
4. **Advanced Filtering**: Date ranges, sectors, etc.
5. **Mobile App**: React Native version
