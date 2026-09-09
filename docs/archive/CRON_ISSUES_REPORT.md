# 🚨 Cron Jobs Issues Report - Production Server

## 📋 Current Status

- **Server**: Production (www.earningstable.com)
- **Date**: 2025-10-17
- **Web Status**: ✅ Working (Nginx + Node.js on port 5555)
- **Cron Status**: ❌ Multiple issues

## 🔍 **Issues Identified**

### 1. **Missing Dependencies**

- **Problem**: `dotenv` module not installed in main project
- **Error**: `Error: Cannot find module 'dotenv'`
- **Impact**: earnings-table PM2 process fails (60 restarts)
- **Location**: `/var/www/earnings-table/simple-server.js`

### 2. **Missing TypeScript Executor**

- **Problem**: `tsx` not installed in cron module
- **Error**: `sh: 1: tsx: not found`
- **Impact**: Cron jobs cannot run
- **Location**: `/var/www/earnings-table/modules/cron/`

### 3. **PM2 Process Status**

```
┌────┬────────────────────┬──────────┬──────┬───────────┬──────────┬──────────┐
│ id │ name               │ mode     │ ↺    │ status    │ cpu      │ memory   │
├────┼────────────────────┼──────────┼──────┼───────────┼──────────┼──────────┤
│ 1  │ earnings-cron      │ fork     │ 15   │ errored   │ 0%       │ 0b       │
│ 0  │ earnings-table     │ fork     │ 60   │ errored   │ 0%       │ 0b       │
└────┴────────────────────┴──────────┴──────┴───────────┴──────────┴──────────┘
```

### 4. **Cron Configuration**

- **Crontab**: ✅ Configured (2 entries)
  - `0 7 * * *` - Daily at 7:00 NY time
  - `0 * * * *` - Every hour (testing)
- **Log File**: ❌ Missing (`/var/log/earnings-cron.log`)

## 🧪 **Manual Test Results**

### ✅ **Successful Manual Run**

```bash
# This worked:
DATABASE_URL="file:/var/www/earnings-table/modules/database/prisma/dev.db" FINNHUB_TOKEN="<FINNHUB_TOKEN_REDACTED>" POLYGON_API_KEY="Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX" npm run run-all

# Output: ✅ 18 symbols processed successfully
```

### ❌ **Failed PM2 Runs**

- **earnings-table**: 60 restarts due to missing `dotenv`
- **earnings-cron**: 15 restarts due to missing `tsx`

## 🔧 **Required Fixes**

### 1. **Install Missing Dependencies**

```bash
# Main project
cd /var/www/earnings-table
npm install dotenv

# Cron module
cd modules/cron
npm install tsx
```

### 2. **Fix PM2 Processes**

```bash
# Restart with proper environment
cd /var/www/earnings-table
pm2 delete all
pm2 start simple-server.js --name earnings-table --update-env
pm2 save
```

### 3. **Fix Cron Module**

```bash
cd /var/www/earnings-table/modules/cron
pm2 start "npm run run-all" --name earnings-cron --cron "*/5 * * * *"
pm2 save
```

## 📊 **Expected Cron Schedule**

### **Daily Cycle Manager** (Recommended)

- 🧹 **03:00** - Clear database
- 📊 **03:05** - Start Finnhub → Polygon sequence
- 🔄 **03:10-23:55** - Every 5 minutes
- 🔄 **00:00-02:30** - Every 5 minutes

### **Current Crontab**

- `0 7 * * *` - Daily at 7:00 NY time
- `0 * * * *` - Every hour (testing)

## 🎯 **Success Criteria**

After fixes:

- ✅ PM2 processes running without errors
- ✅ Cron jobs executing every 5 minutes
- ✅ Data updating automatically
- ✅ Logs being written to `/var/log/earnings-cron.log`

## 📝 **Next Steps**

1. **Install dependencies** (`dotenv`, `tsx`)
2. **Restart PM2 processes** with proper environment
3. **Test manual cron execution**
4. **Verify automatic scheduling**
5. **Monitor logs for 24 hours**

## 🔍 **Root Cause Analysis**

The issues stem from:

1. **Incomplete dependency installation** during deployment
2. **Missing development dependencies** (`tsx`) in production
3. **Environment variable loading** not working in PM2
4. **Process management** not properly configured

**Expected Resolution Time**: 15-30 minutes
**Impact**: Data not updating automatically (manual runs work)
**Priority**: Medium (web functionality unaffected)
