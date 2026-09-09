import { useEffect, useState } from 'react';

export interface CronStatus {
  lastUpdate: string | null; // ISO string
  isFresh: boolean;
  diffMin: number | null;
  recordsProcessed: number | null;
  status: string;
}

interface CronStatusState {
  cron: CronStatus | null;
  loading: boolean;
}

/**
 * Poll /api/cron-status to drive a real freshness indicator in the header
 * (replaces the always-pulsing "Live" dot). Refreshes every 30s.
 */
export function useCronStatus(): CronStatusState {
  const [cron, setCron] = useState<CronStatus | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    const fetchStatus = async () => {
      try {
        const res = await fetch('/api/cron-status');
        if (!res.ok) return;
        const j = await res.json();
        if (cancelled) return;
        setCron({
          lastUpdate: j.lastUpdate ?? null,
          isFresh: !!j.isFresh,
          diffMin: j.diffMin ?? null,
          recordsProcessed: j.recordsProcessed ?? null,
          status: j.status ?? 'unknown',
        });
      } catch {
        /* non-critical */
      } finally {
        if (!cancelled) setLoading(false);
      }
    };
    fetchStatus();
    const interval = setInterval(fetchStatus, 30000);
    return () => { cancelled = true; clearInterval(interval); };
  }, []);

  return { cron, loading };
}
