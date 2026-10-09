import {
  toISODate,
  nyTodayISO,
  computeSizeFromMarketCap,
  formatMarketCap,
  formatRevenue,
  formatDateLong,
  formatDate,
  getChangeColor,
  formatSignedPct,
  formatSignedMarketCap,
} from './utils';

describe('toISODate', () => {
  it('formats as YYYY-MM-DD in UTC', () => {
    expect(toISODate(new Date('2026-10-09T12:34:56Z'))).toBe('2026-10-09');
  });
});

describe('nyTodayISO', () => {
  it('returns a YYYY-MM-DD string', () => {
    expect(nyTodayISO()).toMatch(/^\d{4}-\d{2}-\d{2}$/);
  });
});

describe('computeSizeFromMarketCap', () => {
  it.each([
    [150e9, 'mega'],
    [100e9, 'mega'],
    [50e9, 'large'],
    [10e9, 'large'],
    [5e9, 'mid'],
    [2e9, 'mid'],
    [1e9, 'small'],
  ])('maps %d → %s', (cap, expected) => {
    expect(computeSizeFromMarketCap(cap)).toBe(expected);
  });

  it.each([null, undefined, '', 'abc', NaN])('returns unknown for %p', (v) => {
    expect(computeSizeFromMarketCap(v)).toBe('unknown');
  });
});

describe('formatMarketCap', () => {
  it.each([
    [1.5e12, '$1.50T'],
    [36.48e9, '$36.48B'],
    [2.5e6, '$2.50M'],
    [12345, '$12K'],
    [500, '$500'],
    [-36.48e9, '-$36.48B'],
  ])('formats %d → %s', (v, expected) => {
    expect(formatMarketCap(v)).toBe(expected);
  });

  it.each([null, undefined, '', 'abc'])('returns "-" for %p', (v) => {
    expect(formatMarketCap(v)).toBe('-');
  });
});

describe('formatRevenue', () => {
  it('formats billions', () => {
    expect(formatRevenue(89.5e9)).toBe('$89.50B');
  });
  it.each([0, -5, null, undefined])('returns "-" for non-positive/missing %p', (v) => {
    expect(formatRevenue(v)).toBe('-');
  });
});

describe('formatSignedMarketCap', () => {
  it('prefixes positives with +', () => {
    expect(formatSignedMarketCap(36.48e6)).toBe('+$36.48M');
  });
  it('negatives get -', () => {
    expect(formatSignedMarketCap(-1.07e9)).toBe('-$1.07B');
  });
  it.each([0, null, undefined, ''])('returns "" for %p', (v) => {
    expect(formatSignedMarketCap(v)).toBe('');
  });
});

describe('formatSignedPct', () => {
  it('adds + to positives', () => {
    expect(formatSignedPct(4.256)).toBe('+4.3%');
  });
  it('keeps - on negatives', () => {
    expect(formatSignedPct(-2.1)).toBe('-2.1%');
  });
  it.each([null, undefined])('returns "" for %p', (v) => {
    expect(formatSignedPct(v)).toBe('');
  });
});

describe('date formatting (UTC-safe)', () => {
  it('formatDateLong renders the calendar date', () => {
    expect(formatDateLong('2026-10-09')).toBe('Friday, October 9, 2026');
  });
  it('formatDate renders short form', () => {
    expect(formatDate('2026-10-09')).toBe('Fri, Oct 9');
  });
});

describe('getChangeColor', () => {
  it('is green for positive, red for negative, neutral otherwise', () => {
    expect(getChangeColor(1)).toContain('emerald');
    expect(getChangeColor(-1)).toContain('red');
    expect(getChangeColor(0)).toContain('neutral');
    expect(getChangeColor(null)).toContain('neutral');
  });
});
