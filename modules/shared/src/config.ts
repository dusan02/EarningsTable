import 'dotenv/config';

// Helper: require a string env var (returns undefined if missing, validated later).
function reqEnv(key: string): string | undefined {
  return process.env[key] || undefined;
}

// Helper: parse a positive integer env var with a fallback and NaN guard.
function intEnv(key: string, fallback: number): number {
  const raw = process.env[key];
  if (raw == null || raw === '') return fallback;
  const n = Number(raw);
  return Number.isFinite(n) && Number.isInteger(n) ? n : fallback;
}

// Spoločná konfigurácia pre celú aplikáciu
export const CONFIG = {
  // API konfigurácia
  FINNHUB_TOKEN: reqEnv('FINNHUB_TOKEN'),
  POLYGON_API_KEY: reqEnv('POLYGON_API_KEY'),

  // Databáza
  DATABASE_URL: reqEnv('DATABASE_URL'),

  // Cron konfigurácia
  CRON_TZ: process.env.CRON_TZ || 'America/New_York',

  // Web server
  PORT: intEnv('PORT', 3000),

  // Environment
  NODE_ENV: process.env.NODE_ENV || 'development',

  // Concurrency and batching (tunable via env)
  SNAPSHOT_BATCH_SIZE: intEnv('SNAPSHOT_BATCH_SIZE', 75),
  SNAPSHOT_BATCH_DELAY_MS: intEnv('SNAPSHOT_BATCH_DELAY_MS', 50),
  LOGO_BATCH_SIZE: intEnv('LOGO_BATCH_SIZE', 12),
  LOGO_CONCURRENCY: intEnv('LOGO_CONCURRENCY', 6),
  LOGO_BATCH_DELAY_MS: intEnv('LOGO_BATCH_DELAY_MS', 150),
  SNAPSHOT_TICKER_CONCURRENCY: intEnv('SNAPSHOT_TICKER_CONCURRENCY', 12),
};

// Validácia povinných environment premenných.
// validateConfig covers everything the unified pipeline needs (Finnhub + Polygon + DB),
// so callers only need to call this one function.
export function validateConfig() {
  const required = ['FINNHUB_TOKEN', 'POLYGON_API_KEY', 'DATABASE_URL'];
  const missing = required.filter(key => !process.env[key]);

  if (missing.length > 0) {
    throw new Error(`Missing required environment variables: ${missing.join(', ')}`);
  }
}

// Validácia pre Polygon API (kept for backward compatibility; validateConfig now covers this too)
export function validatePolygonConfig() {
  const required = ['POLYGON_API_KEY', 'DATABASE_URL'];
  const missing = required.filter(key => !process.env[key]);

  if (missing.length > 0) {
    throw new Error(`Missing required environment variables: ${missing.join(', ')}`);
  }
}

// Utility funkcie
export function isDevelopment(): boolean {
  return CONFIG.NODE_ENV === 'development';
}

export function isProduction(): boolean {
  return CONFIG.NODE_ENV === 'production';
}

export function todayIsoNY(): string {
  const now = new Date();
  const ny = new Intl.DateTimeFormat('en-CA', { 
    timeZone: 'America/New_York', 
    year: 'numeric', 
    month: '2-digit', 
    day: '2-digit' 
  }).format(now);
  return ny;
}
