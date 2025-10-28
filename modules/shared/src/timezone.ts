// modules/shared/src/timezone.ts
import { DateTime } from 'luxon';

const TZ = 'America/New_York';

/**
 * Centralized timezone handling to prevent DST drift issues
 * All date/time operations should use these functions
 */
export class TimezoneManager {
  /**
   * Get current time in NY timezone (handles DST automatically)
   */
  static nowNY(): Date {
    return DateTime.now().setZone(TZ).toJSDate();
  }

  /**
   * Get NY midnight for a given date (handles DST transitions)
   */
  static getNYMidnight(date: Date = new Date()): Date {
    const nyDate = DateTime.fromJSDate(date).setZone(TZ);
    return nyDate.startOf('day').toJSDate();
  }

  /**
   * Get NY date string (YYYY-MM-DD) for a given date
   */
  static getNYDateString(date: Date = new Date()): string {
    const nyDate = DateTime.fromJSDate(date).setZone(TZ);
    return nyDate.toISODate()!;
  }

  /**
   * Convert UTC date to NY timezone
   */
  static utcToNY(utcDate: Date): Date {
    return DateTime.fromJSDate(utcDate).setZone(TZ).toJSDate();
  }

  /**
   * Get previous trading day in NY timezone (handles weekends)
   */
  static getPreviousTradingDayNY(date: Date = new Date()): string {
    const nyDate = DateTime.fromJSDate(date).setZone(TZ);
    
    // If weekend, jump to Friday
    if (nyDate.weekday === 7) { // Sunday
      return nyDate.minus({ days: 2 }).toISODate()!;
    }
    if (nyDate.weekday === 6) { // Saturday
      return nyDate.minus({ days: 1 }).toISODate()!;
    }
    
    // Weekday - check if before market open (9:30 AM)
    const marketOpen = nyDate.startOf('day').plus({ hours: 9, minutes: 30 });
    if (nyDate < marketOpen) {
      // Before market open, use previous trading day
      const prevDay = nyDate.minus({ days: 1 });
      if (prevDay.weekday === 7) { // Previous day is Sunday
        return prevDay.minus({ days: 2 }).toISODate()!;
      }
      if (prevDay.weekday === 6) { // Previous day is Saturday
        return prevDay.minus({ days: 1 }).toISODate()!;
      }
      return prevDay.toISODate()!;
    }
    
    return nyDate.toISODate()!;
  }

  /**
   * Check if we're in DST transition week (EU vs US)
   */
  static isDSTTransitionWeek(): boolean {
    const now = DateTime.now().setZone(TZ);
    const weekOfYear = now.weekNumber;
    
    // DST transitions typically happen in weeks 10-11 (March) and 43-44 (November)
    return (weekOfYear >= 10 && weekOfYear <= 11) || (weekOfYear >= 43 && weekOfYear <= 44);
  }

  /**
   * Get report date for earnings (always NY timezone)
   */
  static getReportDate(): Date {
    return this.getNYMidnight();
  }

  /**
   * Get snapshot date for market data (always NY timezone)
   */
  static getSnapshotDate(): Date {
    return this.nowNY();
  }
}

// Export convenience functions
export const nowNY = TimezoneManager.nowNY.bind(TimezoneManager);
export const getNYMidnight = TimezoneManager.getNYMidnight.bind(TimezoneManager);
export const getNYDateString = TimezoneManager.getNYDateString.bind(TimezoneManager);
export const getPreviousTradingDayNY = TimezoneManager.getPreviousTradingDayNY.bind(TimezoneManager);
export const getReportDate = TimezoneManager.getReportDate.bind(TimezoneManager);
export const getSnapshotDate = TimezoneManager.getSnapshotDate.bind(TimezoneManager);
