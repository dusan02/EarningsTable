// Shared frontend helpers (date + market-size + formatting).
// Extracted to eliminate duplication between App.tsx, Calendar.tsx and
// EarningsTable.tsx. Size thresholds MUST stay in sync with the dev proxy
// servers (lib/devProxyUtils.js computeSize).

/** Format a Date as a UTC YYYY-MM-DD calendar key. */
export function toISODate(d: Date): string {
  return d.toISOString().split('T')[0];
}

/**
 * NY-calendar "today" as YYYY-MM-DD. The earnings calendar is keyed by NY
 * trading days, so the default selection / "today" highlight should use the NY
 * date, not the browser-local date.
 */
export function nyTodayISO(): string {
  try {
    return new Intl.DateTimeFormat('en-CA', {
      timeZone: 'America/New_York',
      year: 'numeric', month: '2-digit', day: '2-digit',
    }).format(new Date());
  } catch {
    return toISODate(new Date());
  }
}

/**
 * Compute a market-size bucket from a market cap value.
 *   mega  >= 100B
 *   large >= 10B
 *   mid   >= 2B
 *   small <  2B
 */
export function computeSizeFromMarketCap(marketCap: number | string | null | undefined): string {
  if (marketCap === null || marketCap === undefined || marketCap === '') return 'unknown';
  const num = Number(marketCap);
  if (!isFinite(num)) return 'unknown';
  const abs = Math.abs(num);
  if (abs >= 100e9) return 'mega';
  if (abs >= 10e9) return 'large';
  if (abs >= 2e9) return 'mid';
  return 'small';
}

/** Format a market-cap / market-cap-diff value with sign and scale suffix. */
export function formatMarketCap(value: number | string | null | undefined): string {
  if (value === null || value === undefined || value === '') return '-';
  const num = Number(value);
  if (!isFinite(num)) return '-';
  const abs = Math.abs(num);
  const sign = num < 0 ? '-' : '';
  if (abs >= 1e12) return `${sign}$${(abs / 1e12).toFixed(2)}T`;
  if (abs >= 1e9) return `${sign}$${(abs / 1e9).toFixed(2)}B`;
  if (abs >= 1e6) return `${sign}$${(abs / 1e6).toFixed(2)}M`;
  if (abs >= 1e3) return `${sign}$${(abs / 1e3).toFixed(0)}K`;
  return `${sign}$${abs.toFixed(0)}`;
}

/** Format a revenue value with scale suffix (no sign — revenue is non-negative). */
export function formatRevenue(value: number | string | null | undefined): string {
  if (value === null || value === undefined || value === '') return '-';
  const num = Number(value);
  if (!isFinite(num) || num <= 0) return '-';
  const abs = Math.abs(num);
  if (abs >= 1e12) return `$${(abs / 1e12).toFixed(2)}T`;
  if (abs >= 1e9) return `$${(abs / 1e9).toFixed(2)}B`;
  if (abs >= 1e6) return `$${(abs / 1e6).toFixed(2)}M`;
  return `$${abs.toFixed(0)}`;
}

/**
 * Format a YYYY-MM-DD date string as a long human date.
 * Dates are stored as UTC midnight, so format in UTC to avoid off-by-one.
 */
export function formatDateLong(dateStr: string): string {
  const d = new Date(`${dateStr}T00:00:00.000Z`);
  return d.toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'UTC',
  });
}

/** Format a YYYY-MM-DD date string as a short human date (e.g. "Tue, Sep 9"). */
export function formatDate(dateStr: string): string {
  const d = new Date(`${dateStr}T00:00:00.000Z`);
  return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', timeZone: 'UTC' });
}

/** Color class for a change/surprise percentage (green/red/neutral). */
export function getChangeColor(value: number | null | undefined): string {
  if (value === null || value === undefined) return 'text-neutral-400 dark:text-neutral-500';
  if (value > 0) return 'text-emerald-600 dark:text-emerald-400';
  if (value < 0) return 'text-red-600 dark:text-red-400';
  return 'text-neutral-500 dark:text-neutral-400';
}

/** Format a signed percentage with a + prefix for positives. */
export function formatSignedPct(value: number | null | undefined, digits = 1): string {
  if (value === null || value === undefined) return '';
  return `${value > 0 ? '+' : ''}${value.toFixed(digits)}%`;
}

/** Format a signed market-cap diff with a + prefix for positives (e.g. "+$36.48M", "-$1.07B"). */
export function formatSignedMarketCap(value: number | string | null | undefined): string {
  if (value === null || value === undefined || value === '') return '';
  const num = Number(value);
  if (!isFinite(num) || num === 0) return '';
  const sign = num > 0 ? '+' : '-';
  return `${sign}${formatMarketCap(Math.abs(num))}`;
}


