// Spoločné utility funkcie

/**
 * Konvertuje hodnotu na číslo alebo null
 */
export function toNumber(value: any): number | null {
  if (value === null || value === undefined || value === '') return null;
  const num = Number(value);
  return Number.isFinite(num) ? num : null;
}

/**
 * Konvertuje hodnotu na integer alebo null
 */
export function toInteger(value: any): number | null {
  if (value === null || value === undefined || value === '') return null;
  const num = parseInt(value, 10);
  return Number.isFinite(num) ? num : null;
}

/**
 * Konvertuje hodnotu na BigInt alebo null
 */
export function toBigInt(value: any): bigint | null {
  if (value === null || value === undefined || value === '') return null;
  try {
    return BigInt(value);
  } catch {
    return null;
  }
}

/**
 * Formátuje číslo s čiarkami (napr. 1,234,567)
 */
export function formatNumber(num: number): string {
  return new Intl.NumberFormat('en-US').format(num);
}

/**
 * Formátuje BigInt s čiarkami
 */
export function formatBigInt(num: bigint): string {
  return new Intl.NumberFormat('en-US').format(Number(num));
}

/**
 * Formátuje dátum do ISO formátu
 */
export function formatDate(date: Date): string {
  return date.toISOString().split('T')[0];
}

/**
 * Formátuje čas do čitateľného formátu
 */
export function formatTime(date: Date): string {
  return new Intl.DateTimeFormat('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    timeZone: 'America/New_York'
  }).format(date);
}

/**
 * Loguje správu s timestampom
 */
export function logWithTimestamp(message: string, level: 'info' | 'warn' | 'error' = 'info'): void {
  const timestamp = new Date().toISOString();
  const prefix = `[${timestamp}]`;
  
  switch (level) {
    case 'info':
      console.log(`${prefix} ${message}`);
      break;
    case 'warn':
      console.warn(`${prefix} ⚠️  ${message}`);
      break;
    case 'error':
      console.error(`${prefix} ❌ ${message}`);
      break;
  }
}

/**
 * Čaká určitý počet milisekúnd
 */
export function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Retry mechanizmus pre async funkcie
 */
export async function retry<T>(
  fn: () => Promise<T>,
  maxAttempts: number = 3,
  delay: number = 1000
): Promise<T> {
  let lastError: Error;
  
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      return await fn();
    } catch (error) {
      lastError = error as Error;
      
      if (attempt === maxAttempts) {
        throw lastError;
      }
      
      logWithTimestamp(`Attempt ${attempt} failed, retrying in ${delay}ms...`, 'warn');
      await sleep(delay);
    }
  }
  
  throw lastError!;
}
