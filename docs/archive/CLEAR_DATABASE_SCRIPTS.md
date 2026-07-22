# 🗑️ Database Clearing Scripts - Centralized Documentation

## 📋 Overview

After cleanup, we now have a **centralized approach** to database clearing with no duplicates.

## 🎯 Single Source of Truth

### **Main Implementation**: `DatabaseManager.clearAllTables()`

- **Location**: `modules/cron/src/core/DatabaseManager.ts` (lines 505-515)
- **Function**: Clears all tables in correct order
- **Tables Cleared**: FinalReport → PolygonData → FinhubData → CronStatus
- **Method**: Individual `deleteMany()` calls (not transaction - simpler)

```typescript
async clearAllTables(): Promise<void> {
  console.log('🛑 Clearing all database tables...');

  // Clear tables in correct order (respecting foreign key constraints)
  await prisma.finalReport.deleteMany();
  await prisma.polygonData.deleteMany();
  await prisma.finhubData.deleteMany();
  await prisma.cronStatus.deleteMany();

  console.log('✅ All tables cleared successfully');
}
```

## 🔧 Usage Methods

### 1. **Cron Job Wrapper**: `ClearDatabaseCronJob`

- **Location**: `modules/cron/src/clear-db-cron.ts`
- **Purpose**: Standalone script for manual clearing
- **Usage**: `npx tsx modules/cron/src/clear-db-cron.ts`

### 2. **Manager Integration**: `clearAllData()`

- **Location**: `modules/cron/src/manager.ts` (lines 32-44)
- **Purpose**: Used by daily reset cron job
- **Usage**: Called automatically at 07:00 NY

### 3. **Restart Integration**: `clearDatabase()`

- **Location**: `modules/cron/src/restart.ts` (lines 104-123)
- **Purpose**: Used by restart scripts with backup
- **Usage**: `npm run restart --clear-only`

## 🚫 Removed Duplicates

The following duplicate scripts were **removed**:

- ❌ `scripts/clear-db.js`
- ❌ `scripts/clear-db.ts`
- ❌ `clear-db-test.js`
- ❌ `modules/cron/src/utils/clear-db.ts`
- ❌ `modules/cron/src/restart.ts` - `clearFinhubData()` and `clearPolygonData()` methods

## 📝 Updated References

### **scripts/restart-simple.ts**

- **Before**: `npx ts-node scripts/clear-db.ts`
- **After**: `npx tsx modules/cron/src/clear-db-cron.ts`

### **Documentation Updates**

- **DAILY_LIFECYCLE_RUNBOOK.md**: Updated transaction implementation section
- **SETUP_GUIDE.md**: Added centralized clear script command

## 🎯 Benefits of Centralization

1. **Single Source of Truth**: All clearing logic in one place
2. **Consistency**: Same clearing order and method everywhere
3. **Maintainability**: Changes only need to be made in one location
4. **No Duplicates**: Eliminated 8 duplicate implementations
5. **Clear Documentation**: Easy to understand and use

## 🚀 Quick Commands

```bash
# Direct database clear (standalone)
npx tsx modules/cron/src/clear-db-cron.ts

# Clear via restart script (with backup)
npm run restart --clear-only

# Clear via manager (daily reset)
# Automatically called at 07:00 NY timezone
```

## 📊 Table Clearing Order

1. **FinalReport** - No dependencies
2. **PolygonData** - No dependencies
3. **FinhubData** - Source data
4. **CronStatus** - Status tracking

This order ensures no foreign key constraint violations.

---

_Last Updated: 2025-10-20_  
_Status: Centralized and Clean_
