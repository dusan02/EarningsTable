# 🧭 Earnings Table - Daily Lifecycle & Operations Runbook

## 📋 Executive Summary

This document provides a comprehensive overview of the daily operations, lifecycle management, and technical architecture of the Earnings Table application. It answers all operational questions about scheduling, data flow, error handling, and system maintenance.

---

## 🧭 Daily Lifecycle (Morning to Evening)

### ⏰ Reset Schedule

- **Reset Time**: `07:00 America/New_York` (exactly)
- **Cron Expression**: `0 7 * * *` with timezone `America/New_York`
- **Implementation**: `modules/cron/src/manager.ts` line 55-63

### 🔄 Reset Steps Order

1. **Stop cron jobs** (via mutex lock - no explicit stop needed)
2. **Clear database** (atomic transaction)
3. **Wait 500ms** (warm-up buffer)
4. **Resume cron jobs** (automatic via scheduler)

### 🔒 Atomicity & Mutex

- **Atomic Clear**: ✅ Yes - uses `$transaction` for all tables
- **Mutex Protection**: ✅ Yes - `withLock()` function prevents overlapping jobs
- **Lock Message**: `"⏸️ Skip {name}: another task is running"`
- **Implementation**: `modules/cron/src/manager.ts` lines 10-29

### ⏱️ Time Buffer

- **Buffer Duration**: 500ms after reset
- **Purpose**: Warm-up time, prevent race conditions
- **Implementation**: `await sleep(500)` in reset function

---

## 🗄️ Data Deletion (DB Clear)

### 📊 Tables Cleared (Order)

1. **FinalReport** (first - no dependencies)
2. **PolygonData** (second)
3. **FinhubData** (third - source data)

### 🔄 Transaction Implementation

```typescript
// Centralized in DatabaseManager.clearAllTables()
await prisma.finalReport.deleteMany();
await prisma.polygonData.deleteMany();
await prisma.finhubData.deleteMany();
await prisma.cronStatus.deleteMany();
```

- **Returns**: Success confirmation
- **Location**: `modules/cron/src/core/DatabaseManager.ts` lines 505-515
- **Usage**: Called by `modules/cron/src/manager.ts` and `modules/cron/src/clear-db-cron.ts`

### 💾 Backup Strategy

- **Automatic Backup**: ✅ Yes - before every clear operation
- **Backup Location**: `./backup_YYYY-MM-DDTHH-mm-ss.db`
- **Retention**: Manual cleanup required
- **Implementation**: `modules/cron/src/restart.ts` lines 109-111

### 🔄 Symbol Propagation

- **Method**: `createMany` with `skipDuplicates: true`
- **Function**: `db.copySymbolsToPolygonData()`
- **Location**: `modules/cron/src/core/DatabaseManager.ts`

### ✅ Boolean Fields

- **Strict 0/1**: ✅ Yes - no `null` values
- **Implementation**: Explicit boolean conversion in all upsert operations

---

## ⏱️ Scheduling & Intervals (Cron Jobs)

### 📅 Cron Expressions & Timezone

- **Finnhub**: `0 7 * * *` (daily at 07:00 NY)
- **Polygon**: `0 */4 * * *` (every 4 hours)
- **Timezone**: `America/New_York` (consistent across all jobs)
- **Note**: Current implementation uses separate cron schedules, not alternating pattern

### 🔒 Mutex Protection

- **Implementation**: `isExecuting` flag in `BaseCronJob`
- **Skip Message**: `"⏸️ Skip {name}: previous run still executing"`
- **Location**: `modules/cron/src/core/BaseCronJob.ts` lines 86-90

### 🔄 Parallel vs Sequential

- **CronManager**: Parallel startup (`Promise.allSettled`)
- **Job Execution**: Sequential (mutex prevents overlap)
- **Design Intent**: Parallel startup for efficiency, sequential execution for data consistency

### 🚀 RunOnStart

- **Finnhub**: `runOnStart: false`
- **Polygon**: `runOnStart: false`
- **Reason**: Prevents immediate execution on startup, waits for scheduled time

---

## 🌐 Finnhub (Earnings)

### 📅 Date Handling

- **Source**: NY timezone via `todayIsoNY()`
- **Function**: `modules/shared/src/config.ts` lines 53-62
- **Format**: `YYYY-MM-DD` (ISO date string)

### 💾 Batch Operations

- **Batch Size**: 100 records per transaction
- **Method**: `$transaction` with `upsert` operations
- **Implementation**: `modules/cron/src/core/DatabaseManager.ts` lines 90-100

### 📊 Empty API Response

- **Behavior**: Log warning, return early (no data written)
- **Message**: `"⚠️ No earnings reports found for today"`
- **Location**: `modules/cron/src/jobs/FinnhubCronJob.ts` lines 26-29

### 🔄 Symbol Propagation

- **Trigger**: After FinhubData upsert
- **Function**: `db.copySymbolsToPolygonData()`
- **Method**: `createMany` with `skipDuplicates: true`

### 📈 Surprise Metrics

- **EPS Surprise**: `((actual / estimate) * 100) - 100` (percentage)
- **Revenue Surprise**: `((actual / estimate) * 100) - 100` (percentage)
- **Consistency**: ✅ Same formula across all calculations

---

## 📈 Polygon (Prices & Market Cap)

### 📊 Previous Close Strategy

- **Method**: **Grouped aggs** (1 call per day)
- **API**: `/v2/aggs/grouped/locale/us/market/stocks/{date}`
- **Cache**: 24 hours TTL
- **Implementation**: `modules/cron/src/core/priceService.ts` lines 101-120

### 🚀 Bulk Snapshot Strategy

- **Batch Size**: 80 tickers per batch
- **Concurrency**: 10 concurrent requests
- **API**: `/v2/snapshot/locale/us/markets/stocks/tickers`
- **Cache**: 2 minutes TTL
- **Implementation**: `modules/cron/src/core/priceService.ts` lines 200-250

### 🚫 Price Fallback Policy

- **Never Overwrite**: ✅ Correct - `price=null` if no live price
- **Change Calculation**: Only with valid live price
- **Implementation**: `pickPrice()` function with strict validation

### 🎯 Price Selection Priority

1. **preMarket** (pre-market)
2. **lastTrade** (live)
3. **afterHours** (after-hours)
4. **minute** (minute data)
5. **day** (daily close)
6. **prevDay** (previous day fallback)

### ⏰ Timestamp Normalization

- **Function**: `normTs()` handles ns/µs/ms/s conversion
- **Guards**: Max +5 min future, max 24h old
- **Implementation**: `modules/cron/src/core/priceService.ts` lines 150-170

### 📊 Market Cap Calculations

- **Requirements**: `shares` + `prevClose` + `price` (all required)
- **Formula**: `marketCap = price * shares`
- **Diff Formula**: `marketCapDiff = (price - prevClose) * shares`
- **Library**: Decimal.js for precision

### 🏷️ Price Source Tracking

- **Field**: `priceSource` in `PolygonData` table
- **Values**: `'pre'|'live'|'ah'|'min'|'day'|'prevDay'`
- **Purpose**: Audit trail for debugging
- **Storage**: ✅ Implemented in database schema (added 2025-01-16)

### 💾 Cache Strategy

- **Shares Outstanding**: 24 hours TTL
- **Previous Close**: 24 hours TTL
- **Snapshots**: 2 minutes TTL
- **Implementation**: In-memory `Map` objects with timestamp tracking

---

## 🖼️ Logo Management

### 📊 Database Schema

- **Table**: `FinhubData` (moved from PolygonData)
- **Fields**: `logoUrl`, `logoSource`, `logoFetchedAt`
- **Schema**: `modules/database/prisma/schema.prisma` lines 25-28

### ⏰ Fetch Schedule

- **Trigger**: Missing logo OR older than 30 days
- **Function**: `db.getSymbolsNeedingLogoRefresh()`
- **Batch Processing**: 5 symbols, concurrency 3

### 🎯 Source Priority

1. **Yahoo Finance** (via Clearbit)
2. **Finnhub** (profile2 API)
3. **Polygon** (branding.logo_url)
4. **Clearbit** (domain-based)

### 💾 Storage Details

- **Location**: `modules/web/public/logos/{SYMBOL}.webp`
- **Size**: 256x256 pixels
- **Format**: WebP with 95% quality
- **Processing**: Sharp library for resize/convert

### 🔄 Upsert Protection

- **Policy**: Only update when new logo available
- **Implementation**: Conditional update in `updateLogoInfo()`
- **Preservation**: Existing logos not overwritten

---

## 🔁 Retry & Rate Limiting

### 🔄 Retry Strategy

- **Triggers**: 429 (rate limit) and 5xx (server errors)
- **Max Attempts**: 2 retries
- **Backoff**: Exponential with jitter (300ms base)
- **Implementation**: `modules/cron/src/core/priceService.ts` lines 49-77

### 🚦 Concurrency Control

- **Library**: `p-limit(10)` for API calls
- **Batching**: 80 symbols per batch
- **Rate Limiting**: Built into retry logic

### ⏰ Server-Recommended Delays

- **Implementation**: ✅ Yes - respects `Retry-After` headers (added 2025-01-16)
- **Fallback**: Exponential backoff with jitter if no header
- **Logging**: Retry attempts logged with delay type

---

## 🧪 Quality Controls & Validation

### 📊 Post-Run Logging

- **Symbols Processed**: ✅ Logged
- **Filled Prices**: ✅ Logged
- **Filled Market Caps**: ✅ Logged
- **Final Reports**: ✅ Logged
- **Implementation**: Comprehensive logging throughout

### 🧪 Test Coverage

- **Price Selection**: ✅ Tested (timestamp normalization)
- **Change Calculation**: ✅ Tested (Decimal.js precision)
- **Market Cap Diff**: ✅ Tested (BigInt handling)
- **Missing Data**: ✅ Tested (null handling)

### 📝 Logging Levels

- **DEBUG**: Price candidates, timestamp details
- **INFO**: Summary statistics, completion status
- **ERROR**: API failures, validation errors

---

## 🧩 FinalReport Pipeline

### ⏰ Generation Timing

- **Trigger**: After every Polygon cron job
- **Function**: `db.generateFinalReport()`
- **Location**: `modules/cron/src/jobs/PolygonCronJob.ts` line 39

### ✅ Boolean Logic

- **Rule**: All three booleans must be `true`
- **Fields**: `symbolBoolean`, `priceBoolean`, `marketCapBoolean`
- **Combined**: `Boolean` field (logical AND)
- **Filter**: Only `Boolean: true` records in FinalReport

### 📊 Surprise Consistency

- **EPS Surprise**: Percentage across all calculations
- **Revenue Surprise**: Percentage across all calculations
- **Formula**: `((actual / estimate) * 100) - 100`

### 🔢 Rounding Strategy

- **Database**: Raw values (no rounding)
- **UI**: `toFixed(2)` for display
- **Calculations**: Decimal.js for precision

---

## 🛠️ DevOps & Process Management

### 🔄 Process Management

- **Current**: Node.js processes (no PM2)
- **Supervisor**: `modules/cron/src/manager.ts`
- **Restart**: `modules/cron/src/restart.ts`

### 🛑 Graceful Shutdown

- **Registration**: Once per CronManager instance
- **Signals**: SIGINT, SIGTERM
- **Actions**: Stop all jobs, disconnect Prisma
- **Implementation**: `modules/cron/src/core/BaseCronJob.ts` lines 142-156

### 🪟 Windows File Lock

- **Issue**: EPERM errors during `prisma generate`
- **Solution**: Kill all Node processes before generate
- **Implementation**: `modules/cron/src/restart.ts` lines 26-40

### 🌍 Environment Variables

- **Consistency**: ✅ Same across all processes
- **Required**: `DATABASE_URL`, `FINNHUB_TOKEN`, `POLYGON_API_KEY`
- **Timezone**: `CRON_TZ=America/New_York`

---

## 🔐 Security & Limits

### 🔑 API Key Protection

- **URL Logging**: ✅ No API keys in logs
- **Environment**: Stored in `.env` files
- **Frontend**: Never exposes full URLs

### 🖼️ Logo Security

- **Frontend**: Only serves local `/logos/*.webp`
- **No External**: Direct API calls blocked
- **Processing**: Server-side only

### 🚦 Input Limits

- **Symbol Limit**: No explicit limit (relies on API limits)
- **Batch Size**: 80 symbols max per batch
- **Concurrency**: 10 max concurrent requests

---

## 🧨 Edge Cases

### 🌐 Polygon API Down

- **Behavior**: Skip symbol, continue with batch
- **Logging**: Error logged, not fatal
- **Recovery**: Retry on next scheduled run

### 📊 Missing Shares Outstanding

- **Behavior**: Skip market cap calculation
- **Result**: `marketCap=null`, `marketCapDiff=null`
- **Boolean**: `marketCapBoolean=false`

### 📈 Missing Previous Close

- **Behavior**: Skip change calculation
- **Result**: `change=null`
- **Boolean**: `priceBoolean=false`

### 💥 Reset Failure

- **Protection**: Transaction rollback
- **Recovery**: Manual re-run safe
- **Logging**: Detailed error information

---

## ✅ Acceptance Criteria for Day D

### 🌅 07:00 NY Reset

- **Database Cleared**: ✅ Log shows counts
- **Cron Jobs Stopped**: ✅ Mutex prevents overlap
- **Safe Restart**: ✅ 500ms buffer

### ⏰ 07:02+ Operations

- **Finnhub + Polygon**: ✅ Running per schedule
- **First Polygon**: ✅ Fills prevClose map + pre-market prices
- **FinalReport**: ✅ Records with all three booleans

### 📊 Data Completeness

- **Missing Data**: ✅ Logged and tracked
- **Old Data**: ✅ Refreshed as needed
- **Logo Updates**: ✅ 30-day refresh cycle

### 🔒 Concurrency Safety

- **No Overlap**: ✅ Mutex prevents double execution
- **Skip Messages**: ✅ Clear logging of skipped jobs
- **Status Tracking**: ✅ Last run times and durations

### 📈 Daily Summary

- **Count Summary**: ✅ Symbols processed, prices filled, market caps filled
- **Duration**: ✅ Execution times logged
- **Error Rate**: ✅ 4xx errors (except 404) tracked

---

## 🚀 Quick Commands

### 📋 Daily Operations

```bash
# Clear all data and restart
npm run restart --quick

# Run Finnhub job once
npm run finnhub_data:once

# Run Polygon job once
npm run polygon_data:once

# Check status
npm run status

# Manual reset
npm run supervisor:reset
```

### 🔧 Maintenance

```bash
# Full restart with data clear
npm run restart --full

# Soft restart (no data clear)
npm run restart --soft

# Clear data only (uses centralized DatabaseManager.clearAllTables())
npm run restart --clear-only

# Direct database clear (standalone)
npx tsx modules/cron/src/clear-db-cron.ts

# Regenerate Prisma client
npm run prisma:generate
```

### 📊 Monitoring

```bash
# List all cron jobs
npm run list

# Check job status
npm run status

# View logs
# (Check console output for detailed logging)
```

---

## 📞 Support & Troubleshooting

### 🚨 Common Issues

1. **EPERM Errors**: Kill all Node processes, then regenerate Prisma
2. **Empty Tables**: Check API keys and network connectivity
3. **Logo Issues**: Verify Sharp installation and file permissions
4. **Database Lock**: Use restart script to clear locks

### 🔍 Debug Information

- **Logs**: Comprehensive logging at all levels
- **Status**: Real-time job status via `npm run status`
- **Health**: Service health checks available
- **Database**: Prisma Studio for direct inspection

### 📧 Contact

- **Issues**: GitHub Issues
- **Documentation**: This runbook
- **Code**: Well-commented source code

---

_Last Updated: 2025-01-16_
_Version: 1.0_
_Status: Production Ready_
