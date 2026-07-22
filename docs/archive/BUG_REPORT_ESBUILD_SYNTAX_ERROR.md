# Bug Report: esbuild Syntax Error on Production

## Problem Summary
The cron process on production is failing with an `esbuild` syntax error on line 392 of `modules/cron/src/main.ts`:
```
ERROR: Expected ";" but found ")"
```

The error occurs despite the code being syntactically correct locally. The process restarts continuously (19 restarts shown in PM2 status).

## Error Details
- **File**: `modules/cron/src/main.ts`
- **Line**: 392 (according to error, but actual issue is on line 400)
- **Error Type**: `TransformError` from esbuild
- **Node Version**: v18.20.8
- **Environment**: Production server (Linux)

## Current Code (Problematic Section)

### Line 388-402 in `main.ts`:
```typescript
console.log('Press Ctrl+C to stop all cron jobs');
// Keep-alive - cron joby udržiavajú event loop nažive
// Použijeme jednoduchý keep-alive mechanizmus
process.stdin.resume();

// Udržať proces nažive - Promise, ktorý sa nikdy nerozrieši
// setInterval zabezpečí, že event loop zostane aktívny
const keepAlive = setInterval(() => {
  // Keep process alive - cron jobs maintain the event loop
}, 60000);

// Await na Promise, ktorý sa nikdy nerozrieši
await new Promise(() => {
  // Nikdy nerozriešiť -> proces zostane nažive
});
```

### Line 160 (Similar Pattern):
```typescript
// Keep-alive
await new Promise<void>(() => {}); // nikdy nerezolvni -> udrží event loop
```

## Root Cause Analysis
The `esbuild` parser on production is having issues parsing the `await new Promise(() => {})` pattern. The Promise constructor expects an executor function with `(resolve, reject)` parameters, but we're providing an empty arrow function `() => {}` without parameters.

While this works in TypeScript/Node.js runtime, `esbuild`'s parser is stricter and may be interpreting this as a syntax error.

## Proposed Solution

Replace the problematic `await new Promise(() => {})` pattern with an explicit executor function that accepts `resolve` and `reject` parameters but never calls them:

### Fixed Code for Line 400:
```typescript
console.log('Press Ctrl+C to stop all cron jobs');
// Keep-alive - cron joby udržiavajú event loop nažive
process.stdin.resume();

// Udržať proces nažive - setInterval zabezpečí, že event loop zostane aktívny
const keepAlive = setInterval(() => {
  // Keep process alive - cron jobs maintain the event loop
}, 60000);

// Await na Promise, ktorý sa nikdy nerozrieši
// Explicitné parametre pre esbuild parser
await new Promise<void>((resolve) => {
  // Nikdy nerozriešiť -> proces zostane nažive
  // resolve sa nikdy nezavolá
});
```

### Fixed Code for Line 160:
```typescript
// Keep-alive
await new Promise<void>((resolve) => {
  // nikdy nerezolvni -> udrží event loop
});
```

## Alternative Solution (More Robust)

If the above doesn't work, use a `while(true)` loop with a delay:

```typescript
console.log('Press Ctrl+C to stop all cron jobs');
// Keep-alive - cron joby udržiavajú event loop nažive
process.stdin.resume();

// Udržať proces nažive - setInterval zabezpečí, že event loop zostane aktívny
const keepAlive = setInterval(() => {
  // Keep process alive - cron jobs maintain the event loop
}, 60000);

// Keep process alive with explicit loop
while (true) {
  await new Promise<void>((resolve) => setTimeout(resolve, 60000));
}
```

## Testing Steps
1. Apply the fix locally
2. Build/test locally: `npm run build` or `npm run start`
3. Commit and push to `feat/skeleton-loading-etag`
4. On production server:
   ```bash
   cd /var/www/earnings-table/modules/cron
   rm -rf node_modules/.cache 2>/dev/null || true
   cd /var/www/earnings-table
   git pull origin feat/skeleton-loading-etag
   pm2 restart earnings-cron
   sleep 5
   pm2 logs earnings-cron --lines 50
   ```
5. Verify no syntax errors in logs
6. Check PM2 status: `pm2 status` - should show stable process (no excessive restarts)

## Expected Outcome
- No syntax errors in PM2 logs
- Process stays alive (no premature exits)
- PM2 restart count should stabilize
- Cron jobs execute every 5 minutes as scheduled

