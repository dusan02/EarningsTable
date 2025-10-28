export function getNYMidnight(now = new Date()): Date {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'America/New_York',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  }).formatToParts(now);
  const y = parts.find(p => p.type === 'year')!.value;
  const m = parts.find(p => p.type === 'month')!.value;
  const d = parts.find(p => p.type === 'day')!.value;
  // store as UTC midnight equivalent of NY date for consistency
  return new Date(`${y}-${m}-${d}T00:00:00.000Z`);
}

export function getRunTimestamps(now = new Date()): { reportDate: Date; snapshotDate: Date } {
  const t = getNYMidnight(now);
  return { reportDate: t, snapshotDate: t };
}

export function validateNotFuture(date: Date, maxFutureHours = 24): boolean {
  const nowNY = getNYMidnight(new Date());
  const maxFuture = new Date(nowNY.getTime() + (maxFutureHours * 60 * 60 * 1000));
  return date.getTime() <= maxFuture.getTime();
}
