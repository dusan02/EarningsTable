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

      // Normalize HTTP status from various error shapes (axios, fetch, custom).
      const status = error?.response?.status ?? error?.status ?? error?.statusCode ?? 0;

      // Don't retry on client errors that won't change (auth, not found, bad request).
      // 401/403: auth issues; 404: resource missing; 400: bad request.
      if (status === 401 || status === 403 || status === 404 || status === 400) {
        throw error;
      }

      // Don't retry on last attempt
      if (attempt === maxTries - 1) {
        throw error;
      }

      // Calculate delay with exponential backoff.
      // For 429 (rate limit) use a longer base delay to respect the limit.
      const isRateLimited = status === 429;
      const effectiveBase = isRateLimited ? Math.max(baseDelay, 2000) : baseDelay;
      const delay = Math.min(
        effectiveBase * Math.pow(2, attempt),
        maxDelay
      );

      // Add jitter to prevent thundering herd
      const jitterDelay = jitter
        ? delay + Math.random() * 400
        : delay;

      console.log(`Retry attempt ${attempt + 1}/${maxTries} after ${Math.round(jitterDelay)}ms delay${isRateLimited ? ' (rate-limited)' : ''}`);
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
      // Wrap fn so we count concurrency only while actually executing,
      // not while waiting in the setTimeout delay.
      const task = async () => {
        this.running++;
        try {
          const result = await fn();
          resolve(result);
        } catch (error) {
          reject(error);
        } finally {
          this.running--;
          this.processQueue();
        }
      };
      this.queue.push(task);
      this.processQueue();
    });
  }

  private processQueue() {
    if (this.running >= this.maxConcurrent || this.queue.length === 0) {
      return;
    }

    const next = this.queue.shift();
    if (next) {
      // Schedule actual execution; running is incremented inside the task.
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
    const status = error?.response?.status ?? error?.status ?? error?.statusCode ?? 'n/a';
    console.warn(`API call failed after retries (status=${status}):`, error?.message ?? error);
    return null;
  }
}
