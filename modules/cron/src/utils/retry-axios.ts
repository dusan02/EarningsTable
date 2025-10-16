import axios, { AxiosRequestConfig, AxiosResponse } from 'axios';

interface RetryConfig {
  maxAttempts?: number;
  baseDelay?: number;
  maxDelay?: number;
  jitter?: boolean;
  retryCondition?: (error: any) => boolean;
}

const DEFAULT_CONFIG: Required<RetryConfig> = {
  maxAttempts: 3,
  baseDelay: 1000,
  maxDelay: 10000,
  jitter: true,
  retryCondition: (error) => {
    // Retry on network errors, 5xx, and 429 (rate limit)
    if (!error.response) return true; // Network error
    const status = error.response.status;
    return status >= 500 || status === 429;
  }
};

function calculateDelay(attempt: number, baseDelay: number, maxDelay: number, jitter: boolean): number {
  const exponentialDelay = baseDelay * Math.pow(2, attempt - 1);
  const delay = Math.min(exponentialDelay, maxDelay);
  
  if (jitter) {
    // Add random jitter (±25%)
    const jitterAmount = delay * 0.25;
    return delay + (Math.random() * 2 - 1) * jitterAmount;
  }
  
  return delay;
}

export async function retryAxios<T = any>(
  config: AxiosRequestConfig,
  retryConfig: RetryConfig = {}
): Promise<AxiosResponse<T>> {
  const finalConfig = { ...DEFAULT_CONFIG, ...retryConfig };
  let lastError: any;

  for (let attempt = 1; attempt <= finalConfig.maxAttempts; attempt++) {
    try {
      const response = await axios(config);
      return response;
    } catch (error) {
      lastError = error;
      
      // Don't retry on last attempt
      if (attempt === finalConfig.maxAttempts) {
        break;
      }
      
      // Check if we should retry this error
      if (!finalConfig.retryCondition(error)) {
        break;
      }
      
      const delay = calculateDelay(attempt, finalConfig.baseDelay, finalConfig.maxDelay, finalConfig.jitter);
      console.warn(`⚠️ Attempt ${attempt} failed, retrying in ${Math.round(delay)}ms... (${error.message})`);
      
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
  
  throw lastError;
}

// Convenience function for GET requests
export async function retryGet<T = any>(
  url: string,
  config: AxiosRequestConfig = {},
  retryConfig: RetryConfig = {}
): Promise<AxiosResponse<T>> {
  return retryAxios<T>({ ...config, method: 'GET', url }, retryConfig);
}
