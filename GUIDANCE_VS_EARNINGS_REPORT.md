# 📊 GUIDANCE vs EARNINGS TIMING ANALYSIS REPORT

## EarningsTable.com System - Data Display Logic Issue

---

## 🎯 **EXECUTIVE SUMMARY**

The EarningsTable.com system displays both **earnings data** and **guidance data** simultaneously, creating potential confusion for users. This report analyzes the timing mismatch between when companies issue guidance versus when they report actual earnings, and identifies the UX implications of showing forward-looking guidance data alongside historical earnings data.

---

## 📅 **CURRENT SYSTEM BEHAVIOR**

### **Data Display Logic:**

- **Earnings Data**: Shows actual EPS/Revenue from completed earnings calls
- **Guidance Data**: Shows company estimates for future fiscal periods
- **Timing**: Both are displayed simultaneously without clear temporal context

### **Example: HPE (Hewlett Packard Enterprise)**

**Date**: September 3, 2025

- **Earnings Status**: Q3 2025 earnings scheduled for today (not yet reported)
- **Guidance Displayed**: Q3 2025 EPS: $0.42, Revenue: $8.23B
- **User Confusion**: User sees guidance numbers but doesn't know earnings haven't occurred yet

---

## 🔍 **ROOT CAUSE ANALYSIS**

### **1. Data Source Independence**

- **Earnings Data**: Sourced from Finnhub API (historical/current)
- **Guidance Data**: Sourced from Benzinga API (forward-looking)
- **Issue**: No coordination between data sources for temporal relevance

### **2. Guidance Timing Logic**

- **Companies issue guidance** for future periods (Q3 2025, Q4 2025, FY 2025)
- **Guidance is typically provided** during previous earnings calls
- **Example**: Q2 2025 earnings call → Guidance for Q3 2025
- **Current Display**: Shows Q3 2025 guidance even though Q3 2025 earnings haven't occurred

### **3. User Experience Gap**

- **User Expectation**: Guidance numbers represent current/actual performance
- **Reality**: Guidance numbers represent future estimates
- **Risk**: Misleading investment decisions based on misunderstood data

---

## 📊 **TECHNICAL IMPLEMENTATION DETAILS**

### **Database Structure:**

```sql
-- Earnings Data (Historical)
earningstickerstoday:
  - eps_actual: DECIMAL(10,2) NULL
  - revenue_actual: BIGINT NULL
  - report_date: DATE

-- Guidance Data (Forward-Looking)
benzinga_guidance:
  - estimated_eps_guidance: DECIMAL(12,4)
  - estimated_revenue_guidance: BIGINT
  - fiscal_period: ENUM('Q1','Q2','Q3','Q4','FY')
  - fiscal_year: INT
```

### **Data Flow:**

1. **Daily Cron Jobs** fetch earnings calendar from Finnhub
2. **Parallel Process** fetches guidance data from Benzinga
3. **Dashboard API** combines both datasets without temporal filtering
4. **Frontend** displays guidance columns alongside earnings data

---

## ⚠️ **IDENTIFIED PROBLEMS**

### **1. Temporal Misalignment**

- **Problem**: Guidance for Q3 2025 displayed when Q3 2025 earnings haven't occurred
- **Impact**: Users may think guidance represents actual performance
- **Example**: HPE Q3 2025 guidance shows $0.42 EPS, but Q3 2025 earnings are still pending

### **2. Data Relevance Confusion**

- **Problem**: No clear indication of which data is historical vs. forward-looking
- **Impact**: Users cannot distinguish between actual results and company estimates
- **Risk**: Investment decisions based on misunderstood data context

### **3. Missing Context Information**

- **Problem**: No timestamps or source information for guidance data
- **Impact**: Users don't know when guidance was issued or how current it is
- **Example**: Q3 2025 guidance might be from Q2 2025 earnings call (3 months old)

---

## 🎯 **BUSINESS IMPLICATIONS**

### **1. User Trust Issues**

- **Risk**: Users may lose confidence in system accuracy
- **Impact**: Reduced user engagement and potential churn
- **Mitigation**: Clear data labeling and temporal context

### **2. Investment Decision Risk**

- **Risk**: Users may make decisions based on misunderstood data
- **Impact**: Potential financial losses for users
- **Mitigation**: Clear disclaimers and data validation

### **3. Competitive Positioning**

- **Risk**: Users may prefer competitors with clearer data presentation
- **Impact**: Market share loss in financial data space
- **Mitigation**: Improved UX and data transparency

---

## 🛠️ **RECOMMENDED SOLUTIONS**

### **1. Temporal Data Filtering**

```php
// Filter guidance to only show for periods where earnings have occurred
$relevantGuidance = "
    SELECT * FROM benzinga_guidance g
    WHERE g.fiscal_period = ?
    AND g.fiscal_year = ?
    AND EXISTS (
        SELECT 1 FROM earningstickerstoday e
        WHERE e.ticker = g.ticker
        AND e.report_date <= CURRENT_DATE
    )
";
```

### **2. Enhanced Data Labeling**

- **Add timestamps** to guidance data (when issued)
- **Clear indicators** for forward-looking vs. historical data
- **Source attribution** (e.g., "Guidance from Q2 2025 earnings call")

### **3. User Interface Improvements**

- **Separate sections** for earnings vs. guidance
- **Color coding** (green for actual, blue for guidance)
- **Tooltips** explaining data context and timing
- **Disclaimers** about forward-looking statements

### **4. Data Validation Logic**

- **Cross-reference** guidance periods with earnings completion
- **Filter out** guidance for periods where earnings haven't occurred
- **Prioritize** most recent and relevant guidance data

---

## 📈 **IMPLEMENTATION PRIORITY**

### **High Priority (Week 1-2):**

1. Implement temporal data filtering
2. Add clear data labeling
3. Update dashboard API logic

### **Medium Priority (Week 3-4):**

1. Enhance user interface
2. Add data source timestamps
3. Implement data validation

### **Low Priority (Week 5-6):**

1. User education features
2. Advanced filtering options
3. Performance optimization

---

## 🔍 **TESTING SCENARIOS**

### **1. HPE Test Case**

- **Scenario**: Q3 2025 earnings scheduled but not reported
- **Expected**: Q3 2025 guidance should be filtered out or clearly labeled
- **Actual**: Q3 2025 guidance displayed without context

### **2. Completed Earnings Test Case**

- **Scenario**: Q2 2025 earnings completed, guidance for Q3 2025 available
- **Expected**: Q3 2025 guidance displayed with clear labeling
- **Actual**: Should work correctly with new filtering logic

### **3. Mixed Data Test Case**

- **Scenario**: Some companies have completed earnings, others don't
- **Expected**: Guidance only shown for companies with completed earnings
- **Actual**: Current system shows all guidance regardless of earnings status

---

## 📊 **SUCCESS METRICS**

### **1. User Understanding**

- **Metric**: User confusion reduction (survey-based)
- **Target**: 80% reduction in guidance-related support tickets
- **Measurement**: Pre/post implementation user testing

### **2. Data Accuracy**

- **Metric**: Guidance relevance score
- **Target**: 95% of displayed guidance is temporally relevant
- **Measurement**: Automated validation of guidance vs. earnings timing

### **3. User Engagement**

- **Metric**: Dashboard usage time
- **Target**: 20% increase in average session duration
- **Measurement**: Analytics tracking of user behavior

---

## 🚨 **RISK ASSESSMENT**

### **High Risk:**

- **Data Misinterpretation**: Users making decisions based on misunderstood guidance
- **User Trust Loss**: System perceived as unreliable or confusing

### **Medium Risk:**

- **Implementation Complexity**: Temporal filtering logic may introduce bugs
- **Performance Impact**: Additional database queries for validation

### **Low Risk:**

- **User Interface Changes**: Minor UX adjustments unlikely to cause issues
- **Data Source Modifications**: API changes are well-controlled

---

## 📝 **CONCLUSION**

The current EarningsTable.com system has a **critical UX flaw** where forward-looking guidance data is displayed alongside historical earnings data without proper temporal context. This creates user confusion and potential investment decision risks.

**Immediate action is required** to implement temporal data filtering and enhanced data labeling. The solution involves:

1. **Technical Implementation**: Filter guidance data based on earnings completion status
2. **User Interface**: Clear separation and labeling of different data types
3. **Data Validation**: Cross-reference guidance periods with actual earnings data

**Success depends on** clear communication of data context and temporal relevance to users, ensuring they understand the difference between company guidance and actual performance results.

---

## 🔗 **RELATED DOCUMENTATION**

- **API Documentation**: Finnhub Earnings API, Benzinga Guidance API
- **Database Schema**: EarningsTickersToday, benzinga_guidance tables
- **Cron Job Logic**: Data fetching and processing workflows
- **Frontend Code**: Dashboard rendering and data display logic

---

_Report Generated: September 3, 2025_  
_System: EarningsTable.com_  
_Issue: Guidance vs Earnings Timing Mismatch_
