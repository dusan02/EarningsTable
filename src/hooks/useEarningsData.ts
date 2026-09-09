import { useCallback, useEffect, useRef, useState } from 'react';
import { FinalReportData } from '../types';

interface EarningsState {
  data: FinalReportData[];
  loading: boolean;
  error: string | null;
  /** Wall-clock time of the last successful fetch (ms epoch), or null. */
  lastUpdated: number | null;
  refresh: () => void;
}

// Validate that an API response has the expected envelope shape before using it.
function isFinalReportEnvelope(v: any): v is { data?: FinalReportData[] } {
  return v && typeof v === 'object' && Array.isArray(v.data);
}

/**
 * Fetch earnings for a given YYYY-MM-DD date, with:
 *  - stale-response protection (an older date's slow response can't overwrite
 *    the current date's data — guarded by an incrementing request id);
 *  - auto-refresh every 60s while mounted;
 *  - a `lastUpdated` timestamp and manual `refresh()`.
 */
export function useEarningsData(date: string): EarningsState {
  const [data, setData] = useState<FinalReportData[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [lastUpdated, setLastUpdated] = useState<number | null>(null);

  // Incrementing request id so a slow/stale response for an older date cannot
  // overwrite data for the currently selected date.
  const reqIdRef = useRef(0);
  const [refreshTick, setRefreshTick] = useState(0);

  const fetchData = useCallback(async (d: string) => {
    const reqId = ++reqIdRef.current;
    try {
      setLoading(true);
      setError(null);
      const res = await fetch(`/api/final-report/date/${d}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const result = await res.json();
      if (reqId !== reqIdRef.current) return; // a newer request is in flight
      if (!isFinalReportEnvelope(result)) throw new Error('Unexpected API response shape');
      setData(result.data ?? []);
      setLastUpdated(Date.now());
    } catch (err) {
      if (reqId !== reqIdRef.current) return;
      setError(err instanceof Error ? err.message : 'Failed to load data');
      setData([]);
    } finally {
      if (reqId === reqIdRef.current) setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData(date);
  }, [date, fetchData, refreshTick]);

  // Auto-refresh every 60s.
  useEffect(() => {
    const interval = setInterval(() => fetchData(date), 60000);
    return () => clearInterval(interval);
  }, [date, fetchData]);

  const refresh = useCallback(() => setRefreshTick(t => t + 1), []);
  return { data, loading, error, lastUpdated, refresh };
}
