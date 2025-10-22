/**
 * Retry utilities for API calls
 */

export interface RetryOptions {
  maxTries?: number;
  baseDelay?: number;
  maxDelay?: number;
  jitter?: boolean;
}

/**
 * Retry function with exponential backoff and jitter
 */
export async function withRetry<T>(
  fn: () => Promise<T>, 
  options: RetryOptions = {}
): Promise<T> {
  const {
    maxTries = 4,
    baseDelay = 300,
    maxDelay = 5000,
    jitter = true
  } = options;

  let lastError: any;
  
  for (let attempt = 0; attempt < maxTries; attempt++) {
    try {
      return await fn();
    } catch (error: any) {
      lastError = error;
      
      // Don't retry on 401 (unauthorized) or 403 (forbidden)
      if (error.status === 401 || error.status === 403) {
        throw error;
      }
      
      // Don't retry on last attempt
      if (attempt === maxTries - 1) {
        throw error;
      }
      
      // Calculate delay with exponential backoff
      const delay = Math.min(
        baseDelay * Math.pow(2, attempt),
        maxDelay
      );
      
      // Add jitter to prevent thundering herd
      const jitterDelay = jitter 
        ? delay + Math.random() * 400 
        : delay;
      
      console.log(`Retry attempt ${attempt + 1}/${maxTries} after ${Math.round(jitterDelay)}ms delay`);
      await new Promise(resolve => setTimeout(resolve, jitterDelay));
    }
  }
  
  throw lastError;
}

/**
 * Rate limiter for API calls
 */
export class RateLimiter {
  private queue: Array<() => void> = [];
  private running = 0;
  
  constructor(
    private maxConcurrent: number = 5,
    private minDelay: number = 100
  ) {}
  
  async execute<T>(fn: () => Promise<T>): Promise<T> {
    return new Promise((resolve, reject) => {
      this.queue.push(async () => {
        try {
          const result = await fn();
          resolve(result);
        } catch (error) {
          reject(error);
        } finally {
          this.running--;
          this.processQueue();
        }
      });
      
      this.processQueue();
    });
  }
  
  private processQueue() {
    if (this.running >= this.maxConcurrent || this.queue.length === 0) {
      return;
    }
    
    const next = this.queue.shift();
    if (next) {
      this.running++;
      setTimeout(next, this.minDelay);
    }
  }
}

/**
 * Safe API call with retry and rate limiting
 */
export async function safeApiCall<T>(
  fn: () => Promise<T>,
  retryOptions?: RetryOptions
): Promise<T | null> {
  try {
    return await withRetry(fn, retryOptions);
  } catch (error: any) {
    console.warn(`API call failed after retries:`, error.message);
    return null;
  }
}
