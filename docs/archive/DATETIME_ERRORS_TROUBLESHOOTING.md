# DateTime Errors Troubleshooting Guide

## 🚨 Problem Overview

**Error**: `P2023: Inconsistent column data: Could not convert value "1761004800000" of the field 'reportDate' to type 'DateTime'.`

This error occurs when Prisma tries to read DateTime fields that contain invalid data types in SQLite database.

## 🔍 Root Causes

### 1. INTEGER Timestamps in DateTime Fields
- **Problem**: DateTime fields stored as INTEGER (milliseconds) instead of TEXT (ISO 8601)
- **Cause**: Code writing `Date.now()` or `+new Date()` instead of `new Date()`
- **Location**: Usually in `DatabaseManager.ts` upsert operations

### 2. TEXT Fields with Numerical Content
- **Problem**: DateTime fields stored as TEXT but containing numerical strings like `"1761004800000"`
- **Cause**: String conversion of milliseconds instead of proper Date objects
- **Location**: Data normalization or conversion functions

## 🛠️ Diagnostic Commands

### Check for INTEGER Timestamps
```bash
sqlite3 /var/www/earnings-table/modules/database/prisma/prod.db "
SELECT rowid, symbol, reportDate, typeof(reportDate) AS type 
FROM final_report 
WHERE typeof(reportDate) <> 'text';
"
```

### Check for Numerical TEXT Content
```bash
sqlite3 /var/www/earnings-table/modules/database/prisma/prod.db "
SELECT rowid, symbol, reportDate, typeof(reportDate) AS type 
FROM final_report 
WHERE typeof(reportDate)='text' AND reportDate GLOB '[0-9]*' AND length(reportDate) IN (10,13);
"
```

## 🔧 Fix Procedures

### Fix 1: Convert INTEGER to ISO 8601
```bash
sqlite3 /var/www/earnings-table/modules/database/prisma/prod.db "
UPDATE final_report SET reportDate = strftime('%Y-%m-%dT%H:%M:%SZ', reportDate/1000, 'unixepoch') 
WHERE typeof(reportDate) = 'integer';
"
```

### Fix 2: Convert Numerical TEXT to ISO 8601
```bash
sqlite3 /var/www/earnings-table/modules/database/prisma/prod.db "
UPDATE final_report SET reportDate = strftime('%Y-%m-%dT%H:%M:%SZ', CAST(reportDate AS INTEGER)/1000, 'unixepoch') 
WHERE typeof(reportDate)='text' AND reportDate GLOB '[0-9]*' AND length(reportDate)=13;
"
```

## 🔄 Post-Fix Verification
```bash
pm2 restart earnings-table
pm2 restart earnings-cron
curl -s https://www.earningstable.com/api/final-report | head -c 200
```

## 📝 Notes
- **Date**: 2025-10-22
- **Error Value**: "1761004800000"
- **Tables Fixed**: final_report, finnhub_data
- **Result**: All DateTime errors resolved, API working
