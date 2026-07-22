import React, { useState, useEffect, useCallback } from 'react';
import Calendar from './Calendar';
import EarningsTable from './EarningsTable';
import { FinalReportData, DateInfo, Theme } from './types';

function toISODate(d: Date): string {
  return d.toISOString().split('T')[0];
}

function formatDateLong(dateStr: string): string {
  const d = new Date(`${dateStr}T00:00:00.000Z`);
  return d.toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    timeZone: 'UTC',
  });
}

const App: React.FC = () => {
  const todayStr = toISODate(new Date());
  const [selectedDate, setSelectedDate] = useState(todayStr);
  const [data, setData] = useState<FinalReportData[]>([]);
  const [availableDates, setAvailableDates] = useState<DateInfo[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [theme, setTheme] = useState<Theme>('light');

  // Theme initialization
  useEffect(() => {
    const saved = localStorage.getItem('theme') as Theme | null;
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initial = saved || (prefersDark ? 'dark' : 'light');
    setTheme(initial);
  }, []);

  useEffect(() => {
    const root = document.documentElement;
    if (theme === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
    localStorage.setItem('theme', theme);
  }, [theme]);

  // Fetch available dates once
  useEffect(() => {
    const fetchDates = async () => {
      try {
        const res = await fetch('/api/final-report/dates');
        if (res.ok) {
          const result = await res.json();
          setAvailableDates(result.data ?? []);
        }
      } catch {
        // Non-critical — calendar works without it
      }
    };
    fetchDates();
  }, []);

  // Fetch data when selected date changes
  const fetchData = useCallback(async (date: string) => {
    try {
      setLoading(true);
      setError(null);
      const res = await fetch(`/api/final-report/date/${date}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const result = await res.json();
      setData(result.data ?? []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load data');
      setData([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData(selectedDate);
  }, [selectedDate, fetchData]);

  // Auto-refresh every 60s
  useEffect(() => {
    const interval = setInterval(() => fetchData(selectedDate), 60000);
    return () => clearInterval(interval);
  }, [selectedDate, fetchData]);

  const toggleTheme = () => setTheme(t => t === 'light' ? 'dark' : 'light');

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
                  Daily earnings calendar & financial data
                </p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              {/* Live indicator */}
              <div className="hidden sm:flex items-center gap-1.5">
                <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                <span className="text-xs font-medium text-emerald-600 dark:text-emerald-400">Live</span>
              </div>

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
              <div className="flex items-center justify-center py-24">
                <div className="text-center">
                  <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/20 mb-4">
                    <div className="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"></div>
                  </div>
                  <p className="text-sm text-neutral-500 dark:text-neutral-400">Loading earnings data...</p>
                </div>
              </div>
            ) : error && data.length === 0 ? (
              <div className="flex items-center justify-center py-24">
                <div className="text-center">
                  <div className="text-3xl text-neutral-300 dark:text-neutral-600 mb-3">⚠️</div>
                  <div className="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Failed to load data</div>
                  <div className="text-xs text-neutral-500 dark:text-neutral-400">{error}</div>
                </div>
              </div>
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
