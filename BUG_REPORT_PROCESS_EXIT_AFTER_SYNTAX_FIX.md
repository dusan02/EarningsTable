# Bug Report: Process Still Exiting After Syntax Fix

## Status Update
✅ **Syntax Error FIXED**: The esbuild syntax error on line 392/400 is resolved. The process now starts successfully.

❌ **NEW PROBLEM**: Process is still exiting prematurely and restarting continuously (96 restarts in PM2).

## Current Situation

### Evidence from Production Logs:
1. **Syntax error is gone** - No more "Expected ';' but found ')'" errors
2. **Process starts successfully** - Logs show:
   ```
   🚀 Starting Cron Manager...
   ✅ All cron jobs started successfully
   Press Ctrl+C to stop all cron jobs
   ```
3. **Process executes pipeline** - Successfully runs:
   ```
   ✅ Optimized pipeline completed in 6374ms
   ```
4. **Process then exits** - Logs show:
   ```
   🛑 Graceful shutdown initiated
   ↩️ SIGINT: shutting down…
   ✅ Database disconnected
   ```
5. **PM2 restarts it** - Process restarts immediately (96 restarts total)

## Root Cause Analysis

The process is receiving `SIGINT` or `SIGTERM` signals, causing graceful shutdown. Possible causes:

1. **Keep-alive mechanism not working**: The `await new Promise<void>((resolve) => {})` pattern may not be sufficient to keep the Node.js event loop alive in all scenarios.

2. **PM2 detecting process as "failed"**: PM2 might be sending SIGTERM if it detects the process as unresponsive or failed.

3. **Event loop draining**: Even with `setInterval`, if there are no other active handles, Node.js might exit.

4. **tsx/esbuild behavior**: The `tsx` runtime might have different behavior than standard Node.js regarding process lifecycle.

## Current Keep-Alive Code (Line 390-402)

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

## Proposed Solutions

### Solution 1: More Robust Keep-Alive with Explicit Loop
Replace the current keep-alive with an explicit infinite loop that keeps the event loop active:

```typescript
console.log('Press Ctrl+C to stop all cron jobs');
// Keep-alive - explicit infinite loop to prevent process exit
process.stdin.resume();

// Keep event loop active with periodic checks
const keepAlive = setInterval(() => {
  // Periodic heartbeat to keep event loop active
  if (process.listenerCount('SIGINT') === 0 || process.listenerCount('SIGTERM') === 0) {
    // Ensure signal handlers are still registered
    console.log('🔄 Keep-alive heartbeat');
  }
}, 60000);

// Infinite loop with delay to keep process alive
// This ensures the event loop never drains
while (true) {
  await new Promise<void>((resolve) => setTimeout(resolve, 60000));
}
```

### Solution 2: Use process.stdin for Keep-Alive
Keep stdin open and use it as a keep-alive mechanism:

```typescript
console.log('Press Ctrl+C to stop all cron jobs');
// Keep-alive - use stdin to prevent process exit
process.stdin.resume();
process.stdin.setEncoding('utf8');
process.stdin.on('data', () => {}); // Ignore input but keep stream open

// Keep event loop active
const keepAlive = setInterval(() => {
  // Heartbeat
}, 60000);

// Never-resolving Promise with explicit handler
await new Promise<void>((resolve) => {
  // Store resolve in a way that prevents GC
  (global as any).__keepAliveResolve = resolve;
  // Never call resolve
});
```

### Solution 3: Check PM2 Configuration
The issue might be PM2 configuration. Check if `max_restarts` or other settings are causing issues:

```javascript
// ecosystem.config.js
{
  name: "earnings-cron",
  // ... existing config ...
  autorestart: true,
  max_restarts: 10,  // This might be too low if process exits for other reasons
  restart_delay: 5000,
  // Add these:
  min_uptime: "10s",  // Process must run for 10s to be considered stable
  max_memory_restart: "300M",
  // Prevent PM2 from killing the process:
  kill_timeout: 5000,
  wait_ready: false,
  listen_timeout: 10000,
}
```

### Solution 4: Add Process Exit Prevention
Explicitly prevent process exit and log when it's attempted:

```typescript
console.log('Press Ctrl+C to stop all cron jobs');

// Prevent accidental process exit
const originalExit = process.exit;
process.exit = function(code?: number) {
  console.error(`⚠️ Process.exit(${code}) called - preventing exit`);
  console.trace('Stack trace:');
  // Don't actually exit unless it's a graceful shutdown
  if (code === 0 && process.listenerCount('SIGINT') > 0) {
    // This is likely a graceful shutdown, allow it
    originalExit.call(process, code);
  } else {
    console.error('🚫 Blocked unexpected process exit');
  }
};

// Keep-alive mechanism
process.stdin.resume();
const keepAlive = setInterval(() => {
  // Heartbeat
}, 60000);

await new Promise<void>((resolve) => {
  // Never resolve
});
```

## Recommended Approach

**Try Solution 1 first** (explicit while loop) as it's the most straightforward and doesn't interfere with signal handling. If that doesn't work, investigate PM2 configuration (Solution 3) and add exit prevention (Solution 4).

## Testing Steps

1. Apply the fix locally
2. Test locally: `npm run start` in `modules/cron` - process should stay alive
3. Commit and push
4. On production:
   ```bash
   cd /var/www/earnings-table/modules/cron
   rm -rf node_modules/.cache 2>/dev/null || true
   cd /var/www/earnings-table
   git pull origin feat/skeleton-loading-etag
   pm2 delete earnings-cron
   pm2 start ecosystem.config.js --only earnings-cron
   sleep 10
   pm2 logs earnings-cron --lines 100
   pm2 status
   ```
5. Monitor for at least 5 minutes - restart count should not increase
6. Check logs for "Graceful shutdown" messages - should only appear on manual stop

## Expected Outcome

- Process stays alive indefinitely
- No "Graceful shutdown" messages unless manually stopped
- PM2 restart count stabilizes (doesn't increase)
- Cron jobs execute every 5 minutes as scheduled
- No syntax errors
- No premature exits

## Additional Debugging

If the problem persists, add more logging to understand when/why the process exits:

```typescript
// Add before keep-alive
process.on('beforeExit', (code) => {
  console.error(`⚠️ Process beforeExit event: ${code}`);
  console.trace('Stack trace:');
});

process.on('exit', (code) => {
  console.error(`⚠️ Process exit event: ${code}`);
});

// Log all signal handlers
console.log('Signal handlers:', {
  SIGINT: process.listenerCount('SIGINT'),
  SIGTERM: process.listenerCount('SIGTERM'),
  beforeExit: process.listenerCount('beforeExit'),
  exit: process.listenerCount('exit'),
});
```

