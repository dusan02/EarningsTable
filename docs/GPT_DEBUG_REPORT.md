# 🐛 GPT Debug Report: Earnings Dashboard Guidance Data Issues

## 📋 Executive Summary

**Problem:** The earnings dashboard was not displaying corporate guidance data for ticker CRM, and later exhibited "tickers in groups" behavior (multiple records per ticker).

**Root Cause:** Multiple layered issues in database schema, data validation, and API query logic.

**Solution:** Implemented comprehensive fixes across the entire data pipeline from ingestion to display.

**Result:** ✅ Dashboard now correctly displays CRM guidance data with one record per ticker.

---

## 🔍 Problem Analysis

### Phase 1: Missing CRM Guidance Data

**User Report:** "CRM - Guidance - mám prázdnu tabulku !"

**Initial Investigation:**

- CRM guidance data was available from Benzinga API
- Data was not appearing in the dashboard
- API endpoint was returning `null` for CRM's `eps_guide` and `revenue_guide`

### Phase 2: "Tickers in Groups" Issue

**User Report:** "vracia mi to tickeri po skupinách"

**Investigation:**

- API was returning multiple guidance records for the same ticker
- Example: CXM appeared 6 times with different fiscal periods
- Dashboard was showing duplicate entries

---

## 🚨 Root Causes Identified

### 1. Database Schema Issues

```
❌ Missing benzinga_guidance table
❌ Missing guidance_import_failures table
❌ Overly restrictive CHECK constraints
❌ Limited fiscal_period ENUM values
```

### 2. Data Ingestion Problems

```
❌ Cron job not loading config.php
❌ Validation too strict (5% tolerance)
❌ Unit conversion issues (B/M/K to USD)
❌ Duplicate require_once statements
```

### 3. API Query Logic Flaws

```
❌ JOIN logic filtering guidance by earnings date
❌ Missing ROW_NUMBER() to limit records per ticker
❌ Overly broad filter excluding entire tickers
❌ Incorrect subquery structure
```

---

## 🛠️ Solutions Implemented

### 1. Database Schema Fixes

**Created missing tables:**

```sql
-- benzinga_guidance table with comprehensive schema
CREATE TABLE benzinga_guidance (
    ticker VARCHAR(16),
    fiscal_period ENUM('Q1','Q2','Q3','Q4','FY','2H','3Q','1H','4Q'),
    fiscal_year INT,
    estimated_eps_guidance DECIMAL(18,4),
    estimated_revenue_guidance DECIMAL(18,2),
    -- ... additional fields
    PRIMARY KEY (ticker, fiscal_period, fiscal_year)
);

-- guidance_import_failures for logging validation errors
CREATE TABLE guidance_import_failures (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    ticker VARCHAR(16),
    payload JSON,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Removed problematic constraints:**

```sql
-- Dropped overly strict CHECK constraints
ALTER TABLE benzinga_guidance DROP CONSTRAINT chk_eps_in_range;
ALTER TABLE benzinga_guidance DROP CONSTRAINT chk_rev_in_range;
```

**Expanded ENUM values:**

```sql
-- Added missing fiscal periods
ALTER TABLE benzinga_guidance
MODIFY COLUMN fiscal_period ENUM('Q1','Q2','Q3','Q4','FY','2H','3Q','1H','4Q');
```

### 2. Data Validation Improvements

**Implemented lenient validation:**

```php
class UnifiedValidator {
    const GUIDANCE_DEFAULT_TOLERANCE = 0.15; // 15% tolerance

    public function validateGuidance(array $g, array $opts = []): array {
        $mode = $opts['mode'] ?? 'lenient';
        $tol = $opts['tolerance'] ?? self::GUIDANCE_DEFAULT_TOLERANCE;

        // Hard sanity checks (always enforced)
        if (abs($epsMid) > 500) return [false, 'EPS out of sane bounds'];
        if ($revMid < 0) return [false, 'Revenue negative'];

        // Soft checks (warnings only in lenient mode)
        $warns = [];
        if ($mid < (1 - $tol) * $low) $warns[] = "mid << low by > tol";

        return [true, $normalizedData + ['_warnings' => $warns]];
    }
}
```

**Added unit normalization:**

```php
private function normalizeRevenueUnits($v) {
    if (str_ends_with($x,'B')) return (float)$x * 1e9;
    if (str_ends_with($x,'M')) return (float)$x * 1e6;
    if (str_ends_with($x,'K')) return (float)$x * 1e3;
    return (float)$x;
}
```

### 3. API Query Logic Fix

**Before (Problematic):**

```sql
-- This was filtering guidance by earnings date
FROM EarningsTickersToday e
LEFT JOIN benzinga_guidance g ON e.ticker = g.ticker
WHERE e.report_date = ?  -- ❌ Only showed guidance for companies reporting today
```

**After (Fixed):**

```sql
-- Start from guidance data, then LEFT JOIN earnings
FROM (
    SELECT
        ticker,
        estimated_eps_guidance,
        estimated_revenue_guidance,
        -- ... other fields
        ROW_NUMBER() OVER (PARTITION BY ticker ORDER BY
            CASE WHEN release_type = 'final' THEN 1 ELSE 2 END,
            last_updated DESC
        ) as rn
    FROM benzinga_guidance g1
    WHERE g1.fiscal_period IN ('Q1','Q2','Q3','Q4','FY','2H','3Q','1H','4Q')
    AND g1.fiscal_year IN (2024, 2025, 2026)
    AND g1.estimated_eps_guidance != ''           -- ✅ Filter individual records
    AND g1.estimated_revenue_guidance != ''      -- ✅ Not entire tickers
    AND g1.estimated_eps_guidance IS NOT NULL
    AND g1.estimated_revenue_guidance IS NOT NULL
) g
LEFT JOIN EarningsTickersToday e ON g.ticker = e.ticker AND e.report_date = ?
LEFT JOIN TodayEarningsMovements t ON g.ticker = t.ticker
WHERE g.rn = 1  -- ✅ Only one record per ticker
ORDER BY g.ticker
```

### 4. Cron Job Fixes

**Fixed config loading:**

```php
// cron/5_benzinga_guidance_updates.php
echo "1. Loading config.php...\n";
require_once dirname(__DIR__) . '/config.php';  // ✅ Load config first
echo "✅ Config loaded\n";
```

**Implemented proper validation:**

```php
// BenzingaGuidance.php
[$ok, $norm] = $this->validator->validateGuidance($guidanceRow, [
    'mode' => 'lenient',  // ✅ Use lenient validation
    'tolerance' => 0.15
]);

if (!$ok) {
    $this->logGuidanceFailure($ticker, $guidanceRow, $norm);
    return false;
}
```

---

## 🔧 Key Technical Insights

### 1. ROW_NUMBER() Window Function

**Purpose:** Ensure only one guidance record per ticker
**Logic:**

- `PARTITION BY ticker` - Group by ticker
- `ORDER BY` - Prioritize final > preliminary, then most recent
- `WHERE g.rn = 1` - Select only the top record per ticker

### 2. Filter Strategy Change

**Before:** Exclude entire tickers with `NOT IN` subquery
**After:** Filter individual records before applying `ROW_NUMBER()`
**Benefit:** CRM can appear even if it has some empty guidance records

### 3. Validation Philosophy

**Before:** Strict validation rejecting records outside 5% tolerance
**After:** Lenient validation with warnings, only reject obviously invalid data
**Benefit:** More guidance data reaches the dashboard

---

## 📊 Results Verification

### Database State

```
✅ benzinga_guidance: 116 records (was 0)
✅ CRM guidance: 4 records
✅ guidance_import_failures: Logging validation issues
```

### API Output

```
✅ CRM appears in API response
✅ Company name: "Salesforce, Inc."
✅ Market cap: $241.7B (Large)
✅ One record per ticker (no more "groups")
✅ Guidance data populated for all displayed tickers
```

### Dashboard Display

```
✅ CRM visible in earnings table
✅ Correct company information
✅ EPS Guide and Revenue Guide values displayed
✅ Fiscal period information shown
```

---

## 🎯 Lessons Learned

### 1. Data Pipeline Debugging

- **Start from the source:** Verify data ingestion before debugging display
- **Check each layer:** Database → API → Frontend
- **Use logging:** Implement comprehensive logging at each step

### 2. SQL Query Optimization

- **ROW_NUMBER()** is essential for "latest per group" scenarios
- **Filter early:** Apply filters in subqueries before window functions
- **Test incrementally:** Build complex queries step by step

### 3. Validation Strategy

- **Lenient > Strict** for guidance data (business requirement)
- **Log warnings** instead of rejecting borderline cases
- **Unit normalization** is critical for financial data

### 4. Database Design

- **Avoid overly restrictive constraints** for guidance data
- **Plan for data quality issues** in schema design
- **Use appropriate data types** (DECIMAL for financial values)

---

## 🚀 Future Improvements

### 1. Monitoring

- Add dashboard metrics for guidance data freshness
- Implement alerts for validation warning thresholds
- Track API response times and data quality

### 2. Performance

- Consider caching for frequently accessed guidance data
- Optimize ROW_NUMBER() query with proper indexes
- Implement pagination for large datasets

### 3. Data Quality

- Regular cleanup of old guidance records
- Automated validation rule tuning
- Data source reliability monitoring

---

## 📝 Conclusion

The debugging process revealed a **multi-layered problem** requiring fixes across the entire data pipeline:

1. **Database schema** - Missing tables and overly restrictive constraints
2. **Data ingestion** - Validation too strict, missing configuration
3. **API logic** - Incorrect JOIN strategy and missing record limiting
4. **Frontend display** - Dependent on all previous layers working correctly

**Key Success Factors:**

- ✅ **Systematic approach** - Debug each layer independently
- ✅ **Incremental fixes** - Test each change before proceeding
- ✅ **Comprehensive logging** - Track data flow at each step
- ✅ **User feedback loop** - Verify fixes meet actual requirements

The final solution provides a **robust, scalable foundation** for corporate guidance data display with proper error handling and data quality management.

---

_Report generated: September 4, 2025_  
_Status: ✅ RESOLVED_  
_Impact: High - Core dashboard functionality restored_
