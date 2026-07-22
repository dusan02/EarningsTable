# 🚨 FINNHUB API KEY ISSUE REPORT

## 📋 **Problem Summary**

Finnhub API calls are failing with **401 Unauthorized** error despite having the correct API key configured.

## 🔍 **Root Cause Analysis**

### 1. **Environment Variable Mismatch**

- **Code expects**: `FINNHUB_TOKEN` (in `modules/shared/src/config.ts`)
- **Environment file has**: `FINNHUB_API_KEY`
- **Result**: API key is `undefined` in requests

### 2. **Evidence from Logs**

```
params: { from: '2025-10-17', to: '2025-10-17', token: undefined }
```

The `token` parameter is `undefined`, confirming the environment variable is not being read.

### 3. **Configuration Chain**

```
.env file → modules/shared/src/config.ts → CONFIG.FINNHUB_TOKEN → API calls
```

## 🛠️ **Attempted Fixes**

### ✅ **Fix 1: Corrected Environment Variable Name**

```bash
# Changed from:
FINNHUB_API_KEY="d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"

# To:
FINNHUB_TOKEN="d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
```

### ❌ **Result**: Still failing with 401 Unauthorized

## 🔍 **Further Investigation Needed**

### **Possible Issues:**

1. **API Key Validity**

   - The provided API key might be invalid/expired
   - Key might not have proper permissions for earnings calendar endpoint

2. **Environment Loading**

   - `dotenv` might not be loading correctly in the cron module
   - Environment variables might not be passed to the TypeScript execution

3. **API Endpoint Issues**
   - Finnhub API might be down or rate-limited
   - Endpoint URL might be incorrect

## 🧪 **Debugging Steps**

### **Step 1: Verify Environment Loading**

```bash
cd /var/www/earnings-table/modules/cron
node -e "require('dotenv').config(); console.log('FINNHUB_TOKEN:', process.env.FINNHUB_TOKEN)"
```

### **Step 2: Test API Key Manually**

```bash
curl "https://finnhub.io/api/v1/calendar/earnings?from=2025-10-17&to=2025-10-17&token=d28f1dhr01qjsuf342ogd28f1dhr01qjsuf342p0"
```

### **Step 3: Check API Key Status**

- Verify the API key is active in Finnhub dashboard
- Check if it has earnings calendar permissions
- Verify rate limits haven't been exceeded

## 🎯 **Recommended Actions**

### **Immediate:**

1. **Test API key manually** with curl command
2. **Verify environment loading** in cron module
3. **Check Finnhub dashboard** for API key status

### **Alternative:**

1. **Use Polygon-only approach** (if Polygon API key works)
2. **Generate new Finnhub API key** if current one is invalid
3. **Check API documentation** for any recent changes

## 📊 **Current Status**

- ❌ **Finnhub API**: 401 Unauthorized
- ❓ **Polygon API**: Not tested yet
- ✅ **Environment Setup**: Corrected variable names
- ✅ **Scripts**: Available and documented

## 🔧 **Next Steps**

1. Run debugging commands above
2. Test Polygon API separately
3. Verify API key validity with Finnhub support if needed
4. Consider using mock data for development if APIs are problematic

---

**Report Generated**: 2025-10-17 14:19 UTC  
**Issue**: FINNHUB_API_KEY_ISSUE_REPORT.md
