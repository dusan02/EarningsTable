import 'dotenv/config';

// Spoločná konfigurácia pre celú aplikáciu
export const CONFIG = {
  // API konfigurácia
  FINNHUB_TOKEN: process.env.FINNHUB_TOKEN!,
  POLYGON_API_KEY: process.env.POLYGON_API_KEY!,
  
  // Databáza
  DATABASE_URL: process.env.DATABASE_URL!,
  
  // Cron konfigurácia
  CRON_TZ: process.env.CRON_TZ || 'America/New_York',
  CRON_EXPR: process.env.CRON_EXPR || '0 7 * * *', // Každý deň o 07:00 NY time
  POLYGON_CRON_EXPR: process.env.POLYGON_CRON_EXPR || '0 */4 * * *', // Každé 4 hodiny
  
  // Web server
  PORT: parseInt(process.env.PORT || '3000'),
  
  // Environment
  NODE_ENV: process.env.NODE_ENV || 'development',
};

// Validácia povinných environment premenných
export function validateConfig() {
  const required = ['FINNHUB_TOKEN', 'DATABASE_URL'];
  const missing = required.filter(key => !process.env[key]);
  
  if (missing.length > 0) {
    throw new Error(`Missing required environment variables: ${missing.join(', ')}`);
  }
}

// Validácia pre Polygon API
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
