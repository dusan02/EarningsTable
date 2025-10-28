// modules/shared/src/circuit-breaker.ts
import axios, { AxiosRequestConfig, AxiosResponse } from 'axios';

interface CircuitBreakerConfig {
  failureThreshold: number;
  recoveryTimeout: number;
  monitoringPeriod: number;
}

interface CircuitState {
  failures: number;
  lastFailureTime: number;
  state: 'CLOSED' | 'OPEN' | 'HALF_OPEN';
}

export class CircuitBreaker {
  private config: CircuitBreakerConfig;
  private state: CircuitState;
  private successCount: number = 0;

  constructor(config: Partial<CircuitBreakerConfig> = {}) {
    this.config = {
      failureThreshold: 5,
      recoveryTimeout: 60000, // 1 minute
      monitoringPeriod: 300000, // 5 minutes
      ...config
    };
    
    this.state = {
      failures: 0,
      lastFailureTime: 0,
      state: 'CLOSED'
    };
  }

  async execute<T>(operation: () => Promise<T>): Promise<T> {
    if (this.state.state === 'OPEN') {
      if (Date.now() - this.state.lastFailureTime > this.config.recoveryTimeout) {
        this.state.state = 'HALF_OPEN';
        this.successCount = 0;
      } else {
        throw new Error('Circuit breaker is OPEN - operation blocked');
      }
    }

    try {
      const result = await operation();
      this.onSuccess();
      return result;
    } catch (error) {
      this.onFailure();
      throw error;
    }
  }

  private onSuccess(): void {
    this.state.failures = 0;
    if (this.state.state === 'HALF_OPEN') {
      this.successCount++;
      if (this.successCount >= 3) {
        this.state.state = 'CLOSED';
        this.successCount = 0;
      }
    }
  }

  private onFailure(): void {
    this.state.failures++;
    this.state.lastFailureTime = Date.now();
    
    if (this.state.failures >= this.config.failureThreshold) {
      this.state.state = 'OPEN';
    }
  }

  getState(): CircuitState {
    return { ...this.state };
  }

  reset(): void {
    this.state = {
      failures: 0,
      lastFailureTime: 0,
      state: 'CLOSED'
    };
    this.successCount = 0;
  }
}

/**
 * Enhanced retry with circuit breaker and exponential backoff
 */
export class RetryWithCircuitBreaker {
  private circuitBreaker: CircuitBreaker;
  private maxRetries: number;
  private baseDelay: number;
  private maxDelay: number;

  constructor(options: {
    circuitBreaker?: Partial<CircuitBreakerConfig>;
    maxRetries?: number;
    baseDelay?: number;
    maxDelay?: number;
  } = {}) {
    this.circuitBreaker = new CircuitBreaker(options.circuitBreaker);
    this.maxRetries = options.maxRetries ?? 3;
    this.baseDelay = options.baseDelay ?? 1000;
    this.maxDelay = options.maxDelay ?? 10000;
  }

  async execute<T>(operation: () => Promise<T>): Promise<T> {
    return this.circuitBreaker.execute(async () => {
      let lastError: any;
      
      for (let attempt = 0; attempt < this.maxRetries; attempt++) {
        try {
          return await operation();
        } catch (error: any) {
          lastError = error;
          
          // Don't retry on client errors (4xx)
          if (error.response?.status >= 400 && error.response?.status < 500) {
            throw error;
          }
          
          // Don't retry on last attempt
          if (attempt === this.maxRetries - 1) {
            throw error;
          }
          
          // Calculate delay with exponential backoff and jitter
          const delay = Math.min(
            this.baseDelay * Math.pow(2, attempt) + Math.random() * 1000,
            this.maxDelay
          );
          
          console.warn(`⚠️ Retry attempt ${attempt + 1}/${this.maxRetries} after ${Math.round(delay)}ms`);
          await new Promise(resolve => setTimeout(resolve, delay));
        }
      }
      
      throw lastError;
    });
  }

  getCircuitState(): CircuitState {
    return this.circuitBreaker.getState();
  }
}

/**
 * Enhanced axios wrapper with circuit breaker
 */
export class ResilientAxios {
  private retryWithCircuitBreaker: RetryWithCircuitBreaker;

  constructor(options: {
    circuitBreaker?: Partial<CircuitBreakerConfig>;
    maxRetries?: number;
    baseDelay?: number;
    maxDelay?: number;
  } = {}) {
    this.retryWithCircuitBreaker = new RetryWithCircuitBreaker(options);
  }

  async get<T = any>(url: string, config: AxiosRequestConfig = {}): Promise<AxiosResponse<T>> {
    return this.retryWithCircuitBreaker.execute(async () => {
      return axios.get<T>(url, {
        timeout: 10000,
        headers: { 'User-Agent': 'EarningsTable/1.0' },
        ...config
      });
    });
  }

  async post<T = any>(url: string, data?: any, config: AxiosRequestConfig = {}): Promise<AxiosResponse<T>> {
    return this.retryWithCircuitBreaker.execute(async () => {
      return axios.post<T>(url, data, {
        timeout: 10000,
        headers: { 'User-Agent': 'EarningsTable/1.0' },
        ...config
      });
    });
  }

  getCircuitState(): CircuitState {
    return this.retryWithCircuitBreaker.getCircuitState();
  }
}

// Global instances for different services
export const finnhubAxios = new ResilientAxios({
  circuitBreaker: { failureThreshold: 3, recoveryTimeout: 30000 },
  maxRetries: 2,
  baseDelay: 500,
  maxDelay: 5000
});

export const polygonAxios = new ResilientAxios({
  circuitBreaker: { failureThreshold: 5, recoveryTimeout: 60000 },
  maxRetries: 3,
  baseDelay: 1000,
  maxDelay: 10000
});

export const logoAxios = new ResilientAxios({
  circuitBreaker: { failureThreshold: 10, recoveryTimeout: 120000 },
  maxRetries: 2,
  baseDelay: 2000,
  maxDelay: 8000
});
