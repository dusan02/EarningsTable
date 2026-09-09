import { TimezoneManager } from '../../../shared/src/timezone.js';

/**
 * Storage convention used across the pipeline and the API:
 * a NY calendar date (e.g. "2025-10-13") is stored as the UTC midnight
 * instant of that date string ("2025-10-13T00:00:00.000Z").
 *
 * This is NOT the NY-midnight instant (which would be 04:00Z in EDT), but it
 * is internally consistent: simple-server.js filters FinalReport by
 * `gte: ${date}T00:00:00.000Z` / `lt: next day T00:00:00.000Z`, and
 * getFinhubDataByDate queries by UTC components. Do NOT mix this with
 * TimezoneManager.getNYMidnight() (which returns the 04:00Z instant) for
 * stored reportDate/snapshotDate values, or rows will not match queries.
 */
export function getNYMidnight(now = new Date()): Date {
  // Use the NY date string (handles DST) then store as UTC midnight of that string.
  const nyDateStr = TimezoneManager.getNYDateString(now);
  return new Date(`${nyDateStr}T00:00:00.000Z`);
}

export function getRunTimestamps(now = new Date()): { reportDate: Date; snapshotDate: Date } {
  const reportDate = getNYMidnight(now);
  // snapshotDate is the actual run instant (when the snapshot was taken),
  // distinct from reportDate (the NY calendar day being reported on).
  const snapshotDate = new Date(now);
  return { reportDate, snapshotDate };
}

export function validateNotFuture(date: Date, maxFutureHours = 24): boolean {
  const nowNY = getNYMidnight(new Date());
  const maxFuture = new Date(nowNY.getTime() + (maxFutureHours * 60 * 60 * 1000));
  return date.getTime() <= maxFuture.getTime();
}
