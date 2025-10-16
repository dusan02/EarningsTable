# 🕐 Cron Module - Refactored

## Overview

Refactored cron module with improved architecture, better separation of concerns, and enhanced maintainability.

## 🏗️ Architecture

### Core Components

- **`BaseCronJob`** - Abstract base class for all cron jobs
- **`CronManager`** - Manages all cron jobs lifecycle
- **`DatabaseManager`** - Unified database operations
- **`FinnhubCronJob`** - Finnhub earnings data cron
- **`PolygonCronJob`** - Polygon market data cron

### Directory Structure

```
src/
├── core/
│   ├── BaseCronJob.ts      # Base cron job class
│   ├── CronManager.ts      # Cron jobs manager
│   └── DatabaseManager.ts  # Database operations
├── jobs/
│   ├── FinnhubCronJob.ts   # Finnhub cron job
│   └── PolygonCronJob.ts   # Polygon cron job
├── main.ts                 # Main entry point
├── run-once.ts            # One-time execution
└── restart.ts             # Restart utilities
```

## 🚀 Usage

### Start All Cron Jobs

```bash
npm start
# or
npm run cron start
```

### Start Individual Cron Jobs

```bash
# Start only Finnhub cron
npm run finnhub_data

# Start only Polygon cron
npm run polygon_data
```

### One-time Execution

```bash
# Run Finnhub job once
npm run finnhub_data:once

# Run Polygon job once
npm run polygon_data:once

# Run both jobs once
npm run run:both
```

### Management Commands

```bash
# Check status of all cron jobs
npm run status

# List available cron jobs
npm run list

# Show help
npm run cron help
```

## 📊 Available Cron Jobs

| Job                       | Schedule      | Description                          |
| ------------------------- | ------------- | ------------------------------------ |
| **Finnhub Earnings Data** | `0 7 * * *`   | Daily earnings data at 07:00 NY time |
| **Polygon Market Data**   | `0 */4 * * *` | Market data every 4 hours            |

## 🔧 Configuration

### Environment Variables

```bash
FINNHUB_TOKEN=your_finnhub_token
POLYGON_API_KEY=your_polygon_api_key
DATABASE_URL=file:./prisma/dev.db
CRON_TZ=America/New_York
```

### Cron Job Configuration

Each cron job can be configured with:

- **name** - Display name
- **schedule** - Cron expression
- **timezone** - Timezone (default: America/New_York)
- **runOnStart** - Run immediately on start (default: false)
- **description** - Human-readable description

## 🛠️ Development

### Adding New Cron Jobs

1. **Create new cron job class:**

```typescript
import { BaseCronJob } from "../core/BaseCronJob.js";

export class MyCronJob extends BaseCronJob {
  constructor() {
    super({
      name: "My Custom Job",
      schedule: "0 */6 * * *", // Every 6 hours
      description: "My custom cron job description",
    });
  }

  async execute(): Promise<void> {
    // Your cron job logic here
  }
}
```

2. **Register in CronManager:**

```typescript
// In CronManager.ts
this.registerCronJob(new MyCronJob());
```

### Database Operations

Use the unified `DatabaseManager`:

```typescript
import { db } from "./core/DatabaseManager.js";

// FinhubData operations
await db.upsertFinhubData(data);
await db.getFinhubDataByDate(date);
await db.clearFinhubData();

// PolygonData operations
await db.upsertPolygonData(data);
await db.clearPolygonData();

// General operations
await db.clearAllData();
await db.disconnect();
```

## 🔄 Restart System

The restart system remains unchanged and provides:

- Application restart (kill + start services)
- Database restart (regenerate client, update schema)
- Data clearing (all data, FinhubData, PolygonData)

```bash
# Clear all data and restart application
npm run restart -- --clear-db --restart-app

# Restart application only
npm run restart -- --restart-app
```

## 📈 Benefits of Refactoring

### Before Refactoring:

- ❌ Duplicate code across cron jobs
- ❌ Scattered database operations
- ❌ No unified management
- ❌ Hard to extend

### After Refactoring:

- ✅ **DRY Principle** - No code duplication
- ✅ **Single Responsibility** - Each class has one purpose
- ✅ **Unified Management** - Centralized cron job control
- ✅ **Easy Extension** - Simple to add new cron jobs
- ✅ **Better Testing** - Isolated components
- ✅ **Type Safety** - Full TypeScript support
- ✅ **Error Handling** - Consistent error management
- ✅ **Graceful Shutdown** - Proper cleanup on exit

## 🎯 Next Steps

1. **Add Monitoring** - Health checks and metrics
2. **Add Logging** - Structured logging system
3. **Add Testing** - Unit and integration tests
4. **Add Configuration** - External config files
5. **Add Notifications** - Email/Slack alerts on failures
