import React, { useState, useEffect, useRef, useCallback } from 'react';
import Calendar from './Calendar';
import EarningsTable from './EarningsTable';
import { FinalReportData, DateInfo } from './types';
import { nyTodayISO, formatDateLong } from './utils';
import { useTheme } from './hooks/useTheme';
import { useEarningsData } from './hooks/useEarningsData';
import { useCronStatus } from './hooks/useCronStatus';
import { LoadingState, ErrorState } from './components/States';

// Simple error boundary so a render error in the table/calendar doesn't blank
// the whole page (M3).
export class ErrorBoundary extends React.Component<
  { children: React.ReactNode },
  { hasError: boolean; message?: string }
> {
  constructor(props: { children: React.ReactNode }) {
    super(props);
    this.state = { hasError: false };
  }
  static getDerivedStateFromError(error: Error) {
    return { hasError: true, message: error.message };
  }
  componentDidCatch(error: Error, info: React.ErrorInfo) {
    console.error('[ErrorBoundary] render error:', error, info);
  }
  render() {
    if (this.state.hasError) {
      return (
        <div className="flex items-center justify-center min-h-screen bg-neutral-50 dark:bg-slate-950 p-6">
          <div className="text-center max-w-md">
            <div className="text-3xl mb-3">⚠️</div>
            <div className="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
              Something went wrong while rendering the page.
            </div>
            <div className="text-xs text-neutral-500 dark:text-neutral-400 mb-4">
              {this.state.message}
            </div>
            <button
              onClick={() => window.location.reload()}
              className="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700"
            >
              Reload
            </button>
          </div>
        </div>
      );
    }
    return this.props.children;
  }
}

/** Format a ms epoch as a relative "X min ago" / "just now" label. */
function relativeTime(ms: number | null): string | null {
  if (ms == null) return null;
  const diffSec = Math.max(0, Math.round((Date.now() - ms) / 1000));
  if (diffSec < 5) return 'just now';
  if (diffSec < 60) return `${diffSec}s ago`;
  const min = Math.floor(diffSec / 60);
  if (min < 60) return `${min} min ago`;
  const hr = Math.floor(min / 60);
  return `${hr}h ago`;
}

const App: React.FC = () => {
  const todayStr = nyTodayISO();
  const [selectedDate, setSelectedDate] = useState(todayStr);
  const [availableDates, setAvailableDates] = useState<DateInfo[]>([]);
  const [theme, toggleTheme] = useTheme();
  const { data, loading, error, lastUpdated, refresh } = useEarningsData(selectedDate);
  const { cron } = useCronStatus();

  // Fetch available dates once. If today has no data but other dates do,
  // auto-select the latest available date so the page isn't empty on load.
  // Guarded by a ref so a user's manual selection is never overridden.
  const initialPickDone = useRef(false);
  useEffect(() => {
    const fetchDates = async () => {
      try {
        const res = await fetch('/api/final-report/dates');
        if (res.ok) {
          const result = await res.json();
          if (result && Array.isArray(result.data)) {
            setAvailableDates(result.data);
            if (!initialPickDone.current && Array.isArray(result.data) && result.data.length > 0) {
              initialPickDone.current = true;
              const dates = result.data.map((d: DateInfo) => d.date).sort();
              const today = nyTodayISO();
              if (!dates.includes(today)) {
                // Dates are sorted ascending; pick the latest available.
                setSelectedDate(dates[dates.length - 1]);
              }
            }
          }
        }
      } catch {
        // Non-critical — calendar works without it
      }
    };
    fetchDates();
  }, []);

  const freshnessLabel = relativeTime(lastUpdated);
  const isFresh = cron?.isFresh ?? true; // assume fresh until cron status loads

  return (
    <div className="min-h-screen bg-neutral-50 dark:bg-slate-950 transition-colors duration-300">
      {/* Header */}
      <header className="sticky top-0 z-50 bg-white/90 dark:bg-slate-900/90 backdrop-blur-lg border-b border-neutral-200 dark:border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              {/* Logo icon */}
              <div className="flex items-end gap-0.5">
                <div className="w-1.5 h-3 bg-emerald-500 rounded-sm"></div>
                <div className="w-1.5 h-5 bg-amber-500 rounded-sm"></div>
                <div className="w-1.5 h-2.5 bg-blue-500 rounded-sm"></div>
                <div className="w-1.5 h-4 bg-purple-500 rounded-sm"></div>
              </div>
              <div>
                <h1 className="text-lg sm:text-xl font-bold text-neutral-900 dark:text-white tracking-tight">
                  Earnings Table
                </h1>
                <p className="hidden sm:block text-xs text-neutral-500 dark:text-neutral-400">
                  Daily earnings calendar &amp; financial data
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2 sm:gap-3">
              {/* Freshness indicator (real, from cron status + last fetch) */}
              <div
                className="hidden sm:flex items-center gap-1.5 px-2 py-1 rounded-lg bg-neutral-100 dark:bg-slate-800"
                title={cron?.lastUpdate ? `Cron last update: ${cron.lastUpdate}` : 'No cron status yet'}
              >
                <div className={`w-2 h-2 rounded-full ${isFresh ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'}`} />
                <span className={`text-xs font-medium ${isFresh ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'}`}>
                  {freshnessLabel ? `Updated ${freshnessLabel}` : 'Live'}
                </span>
              </div>

              {/* Manual refresh */}
              <button
                onClick={refresh}
                disabled={loading}
                aria-label="Refresh data"
                className="p-2 rounded-lg bg-neutral-100 dark:bg-slate-800 hover:bg-neutral-200 dark:hover:bg-slate-700 transition-colors text-neutral-600 dark:text-neutral-300 disabled:opacity-50"
              >
                <svg className={`w-5 h-5 ${loading ? 'animate-spin' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>

              {/* Theme toggle */}
              <button
                onClick={toggleTheme}
                className="p-2 rounded-lg bg-neutral-100 dark:bg-slate-800 hover:bg-neutral-200 dark:hover:bg-slate-700 transition-colors text-neutral-600 dark:text-neutral-300"
                aria-label="Toggle theme"
              >
                {theme === 'light' ? (
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                  </svg>
                ) : (
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                )}
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Main content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div className="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-4 sm:gap-6">
          {/* Left sidebar: Calendar */}
          <div className="lg:sticky lg:top-20 lg:self-start">
            <Calendar
              selectedDate={selectedDate}
              onDateSelect={setSelectedDate}
              availableDates={availableDates}
            />

            {/* Selected date info */}
            <div className="mt-4 bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-neutral-200 dark:border-slate-800 p-4">
              <div className="text-xs text-neutral-400 dark:text-neutral-500 uppercase tracking-wider font-semibold mb-1">
                Selected Date
              </div>
              <div className="text-sm font-bold text-neutral-900 dark:text-white">
                {formatDateLong(selectedDate)}
              </div>
              <div className="mt-2 text-xs text-neutral-500 dark:text-neutral-400">
                {loading ? 'Loading...' : `${data.length} ${data.length === 1 ? 'company reporting' : 'companies reporting'}`}
              </div>
            </div>
          </div>

          {/* Right content: Table */}
          <div>
            {loading && data.length === 0 ? (
              <LoadingState />
            ) : error && data.length === 0 ? (
              <ErrorState message={error} />
            ) : (
              <EarningsTable data={data} selectedDate={selectedDate} />
            )}
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="mt-12 border-t border-neutral-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <p className="text-xs text-neutral-500 dark:text-neutral-400 text-center leading-relaxed max-w-3xl mx-auto">
            This earnings table is provided for informational purposes only. I am independent and am not affiliated with
            any financial institutions or data providers. Financial data is sourced from Polygon and other third-party
            providers. I do not guarantee the accuracy, completeness, or timeliness of the information presented. This
            data should not be considered as financial advice.
          </p>
        </div>
      </footer>
    </div>
  );
};

export default App;
