import http from 'http';
import { setTimeout as sleep } from 'timers/promises';

const WEB_URL = 'http://localhost:3001/health';
const STUDIO_PORT = 5555;

export async function waitForHealth(url: string, timeoutMs = 20000): Promise<void> {
  const start = Date.now();
  console.log(`🔍 Waiting for health check: ${url}`);
  
  while (Date.now() - start < timeoutMs) {
    try {
      const ok = await new Promise<boolean>((resolve) => {
        const req = http.get(url, (res) => {
          resolve(res.statusCode === 200);
        });
        req.on('error', () => resolve(false));
        req.setTimeout(3000, () => { 
          req.destroy(); 
          resolve(false); 
        });
      });
      
      if (ok) {
        console.log(`✅ Health check passed: ${url}`);
        return;
      }
    } catch (error) {
      // Ignore connection errors during startup
    }
    
    await sleep(500);
  }
  
  throw new Error(`Health check timed out: ${url}`);
}

export async function waitForPort(port: number, timeoutMs = 15000): Promise<void> {
  const start = Date.now();
  console.log(`🔍 Waiting for port: ${port}`);
  
  while (Date.now() - start < timeoutMs) {
    try {
      const ok = await new Promise<boolean>((resolve) => {
        const req = http.request({
          hostname: 'localhost',
          port: port,
          method: 'GET',
          timeout: 2000
        }, (res) => {
          resolve(true);
        });
        
        req.on('error', () => resolve(false));
        req.on('timeout', () => {
          req.destroy();
          resolve(false);
        });
        
        req.end();
      });
      
      if (ok) {
        console.log(`✅ Port ${port} is available`);
        return;
      }
    } catch (error) {
      // Ignore connection errors during startup
    }
    
    await sleep(500);
  }
  
  throw new Error(`Port ${port} not available within ${timeoutMs}ms`);
}

export async function checkWebHealth(): Promise<boolean> {
  try {
    const ok = await new Promise<boolean>((resolve) => {
      const req = http.get(WEB_URL, (res) => {
        resolve(res.statusCode === 200);
      });
      req.on('error', () => resolve(false));
      req.setTimeout(3000, () => { 
        req.destroy(); 
        resolve(false); 
      });
    });
    return ok;
  } catch {
    return false;
  }
}

export async function checkStudioHealth(): Promise<boolean> {
  try {
    const ok = await new Promise<boolean>((resolve) => {
      const req = http.request({
        hostname: 'localhost',
        port: STUDIO_PORT,
        method: 'GET',
        timeout: 2000
      }, (res) => {
        resolve(true);
      });
      
      req.on('error', () => resolve(false));
      req.on('timeout', () => {
        req.destroy();
        resolve(false);
      });
      
      req.end();
    });
    return ok;
  } catch {
    return false;
  }
}
