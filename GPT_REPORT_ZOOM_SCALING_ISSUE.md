# 🚨 GPT REPORT: ZOOM SCALING ISSUE - GUIDANCE TABLE HEADERS

## 📋 **PROBLEM DESCRIPTION**

**Issue**: Guidance table headers are not scaling correctly with zoom levels

- **Zoom 100%**: ✅ Headers are correct height
- **Zoom 110%**: ✅ Headers are correct height
- **Zoom 125%**: ❌ **FAILED** - Guidance header is not as tall as left table header

**Root Cause**: CSS height rules for Guidance table headers are not being applied correctly at higher zoom levels, causing height mismatch between left and right table headers.

## 🔍 **TECHNICAL ANALYSIS**

### **Current CSS Implementation**

#### **1. Guidance Table Header CSS Rules:**

```css
/* Guidance table specific header styling */
.guidance-table th {
  background: #e3f2fd;
  border-bottom: 2px solid var(--color-gray-300);
  font-weight: 700;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  height: var(--table-row-height) !important;
  min-height: var(--table-row-height) !important;
  max-height: var(--table-row-height) !important;
  padding: var(--spacing-1) var(--spacing-1) !important;
  vertical-align: middle !important;
  box-sizing: border-box !important;
}

/* Force guidance table header height at all zoom levels */
.guidance-table thead tr {
  height: var(--table-row-height) !important;
  min-height: var(--table-row-height) !important;
  max-height: var(--table-row-height) !important;
}

.guidance-table thead tr th {
  height: var(--table-row-height) !important;
  min-height: var(--table-row-height) !important;
  max-height: var(--table-row-height) !important;
  padding: var(--spacing-1) var(--spacing-1) !important;
  vertical-align: middle !important;
  box-sizing: border-box !important;
}
```

#### **2. Zoom Scaling Media Query:**

```css
/* Zoom scaling support for guidance table headers */
@media screen and (min-resolution: 1.25dppx) {
  .guidance-table thead tr,
  .guidance-table thead tr th {
    height: calc(var(--table-row-height) * 1.25) !important;
    min-height: calc(var(--table-row-height) * 1.25) !important;
    max-height: calc(var(--table-row-height) * 1.25) !important;
  }
}
```

#### **3. CSS Variables:**

```css
:root {
  --table-row-height: 42px;
  --spacing-1: 8px;
  --spacing-2: 12px;
}
```

### **2. Table Structure Analysis**

#### **Left Table (EPS & Revenue):**

```html
<table class="earnings-table-left">
  <thead>
    <tr>
      <th>Ticker</th>
      <th>Market Cap</th>
      <!-- ... other headers -->
    </tr>
  </thead>
</table>
```

#### **Right Table (Guidance):**

```html
<table class="earnings-table-right guidance-table">
  <thead>
    <tr>
      <th>EPS Guide</th>
      <th>EPS G Surp</th>
      <th>Rev Guide</th>
      <th>Rev G Surp</th>
      <th>Period</th>
      <th>Notes</th>
    </tr>
  </thead>
</table>
```

## 🚨 **ISSUE DETAILS**

### **Problem 1: CSS Specificity Conflicts**

- **Left table headers**: Use `.earnings-table-left th` selector
- **Guidance table headers**: Use `.guidance-table th` selector
- **Conflicting rules**: Different CSS selectors may have different specificity

### **Problem 2: Zoom Scaling Detection**

- **Media query**: `@media screen and (min-resolution: 1.25dppx)`
- **Browser support**: May not work consistently across all browsers
- **Zoom detection**: `window.devicePixelRatio` vs CSS media queries

### **Problem 3: JavaScript Height Synchronization**

- **Current approach**: CSS-only solution
- **Missing**: JavaScript-based height synchronization for headers
- **Dynamic adjustment**: No real-time height matching

## 🔧 **ATTEMPTED SOLUTIONS**

### **Solution 1: Enhanced CSS Rules**

- ✅ Added `min-height` and `max-height` with `!important`
- ✅ Added specific `.guidance-table thead tr` rules
- ✅ Added zoom scaling media query
- ❌ **Result**: Problem persists

### **Solution 2: CSS Specificity**

- ✅ Used `!important` declarations
- ✅ Added higher specificity selectors
- ❌ **Result**: Problem persists

### **Solution 3: Zoom Media Query**

- ✅ Added `@media screen and (min-resolution: 1.25dppx)`
- ✅ Used `calc(var(--table-row-height) * 1.25)`
- ❌ **Result**: Problem persists

## 🎯 **RECOMMENDED APPROACHES FOR GPT**

### **Approach 1: JavaScript Height Synchronization**

```javascript
function syncHeaderHeights() {
  const leftHeader = document.querySelector(".earnings-table-left thead tr");
  const guidanceHeader = document.querySelector(".guidance-table thead tr");

  if (leftHeader && guidanceHeader) {
    const leftHeight = leftHeader.getBoundingClientRect().height;
    guidanceHeader.style.height = leftHeight + "px";
    guidanceHeader.style.minHeight = leftHeight + "px";
    guidanceHeader.style.maxHeight = leftHeight + "px";
  }
}

// Run on zoom change
window.addEventListener("resize", syncHeaderHeights);
// Run on zoom detection
setInterval(() => {
  if (window.devicePixelRatio !== lastZoom) {
    syncHeaderHeights();
    lastZoom = window.devicePixelRatio;
  }
}, 100);
```

### **Approach 2: CSS Transform Scaling**

```css
/* Instead of height changes, use transform scaling */
@media screen and (min-resolution: 1.25dppx) {
  .guidance-table thead {
    transform: scaleY(1.25);
    transform-origin: top;
  }
}
```

### **Approach 3: Unified Header System**

```css
/* Force both tables to use identical header styling */
.earnings-table-left thead tr,
.earnings-table-right.guidance-table thead tr {
  height: var(--table-row-height) !important;
  min-height: var(--table-row-height) !important;
  max-height: var(--table-row-height) !important;
  display: table-row !important;
}

.earnings-table-left thead tr th,
.earnings-table-right.guidance-table thead tr th {
  height: var(--table-row-height) !important;
  min-height: var(--table-row-height) !important;
  max-height: var(--table-row-height) !important;
  display: table-cell !important;
  vertical-align: middle !important;
  box-sizing: border-box !important;
}
```

## 📊 **TESTING SCENARIOS**

### **Test Case 1: Zoom 100%**

- **Expected**: Both headers same height (42px)
- **Actual**: ✅ Working correctly

### **Test Case 2: Zoom 110%**

- **Expected**: Both headers same height (~46px)
- **Actual**: ✅ Working correctly

### **Test Case 3: Zoom 125%**

- **Expected**: Both headers same height (52.5px)
- **Actual**: ❌ **FAILING** - Guidance header shorter

### **Test Case 4: Zoom 150%**

- **Expected**: Both headers same height (63px)
- **Actual**: ❌ **LIKELY FAILING**

## 🎯 **SUCCESS CRITERIA**

### **Primary Goal:**

- Guidance table headers must match left table header height at ALL zoom levels
- No visual misalignment between left and right table sections

### **Secondary Goals:**

- Smooth zoom scaling without layout jumps
- Consistent header appearance across all zoom levels
- Maintain table readability at all zoom levels

## 🔍 **DEBUGGING STEPS FOR GPT**

### **Step 1: Inspect Current State**

- Check computed CSS values in browser dev tools
- Compare left vs guidance header heights
- Verify CSS rules are being applied

### **Step 2: Test CSS Solutions**

- Try unified header CSS approach
- Test CSS transform scaling
- Verify media query functionality

### **Step 3: Implement JavaScript Solution**

- Add header height synchronization
- Test zoom change detection
- Verify real-time height matching

### **Step 4: Cross-Browser Testing**

- Test in Chrome, Firefox, Safari, Edge
- Verify zoom behavior consistency
- Check CSS compatibility

## 📝 **CONCLUSION**

The current CSS-only approach has failed to resolve the zoom scaling issue. The problem requires either:

1. **JavaScript-based height synchronization** for real-time header matching
2. **CSS transform scaling** instead of height changes
3. **Unified header system** with identical CSS rules

**Recommendation**: Implement JavaScript height synchronization as it provides the most reliable solution across all zoom levels and browsers.

---

**Report Created**: 3rd September 2025  
**Issue Status**: ❌ **UNRESOLVED**  
**Priority**: 🔴 **HIGH** - Affects user experience at common zoom levels  
**Complexity**: 🟡 **MEDIUM** - Requires JavaScript implementation
