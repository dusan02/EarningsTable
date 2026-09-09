import React from 'react';

/** Full-area loading spinner. */
export const LoadingState: React.FC<{ label?: string }> = ({ label = 'Loading earnings data...' }) => (
  <div className="flex items-center justify-center py-24">
    <div className="text-center">
      <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/20 mb-4">
        <div className="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"></div>
      </div>
      <p className="text-sm text-neutral-500 dark:text-neutral-400">{label}</p>
    </div>
  </div>
);

/** Full-area error state. */
export const ErrorState: React.FC<{ message: string }> = ({ message }) => (
  <div className="flex items-center justify-center py-24">
    <div className="text-center">
      <div className="text-3xl text-neutral-300 dark:text-neutral-600 mb-3">⚠️</div>
      <div className="text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Failed to load data</div>
      <div className="text-xs text-neutral-500 dark:text-neutral-400">{message}</div>
    </div>
  </div>
);

interface EmptyStateProps {
  /** True when the whole date has no data; false when the search filter is empty. */
  dateEmpty: boolean;
  dateLabel: string;
}

/** Empty state for "no earnings on this date" or "no search matches". */
export const EmptyState: React.FC<EmptyStateProps> = ({ dateEmpty, dateLabel }) => (
  <div className="text-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-neutral-200 dark:border-slate-800">
    <div className="text-neutral-300 dark:text-neutral-600 text-4xl mb-3">📊</div>
    <div className="text-neutral-500 dark:text-neutral-400 text-sm font-medium">
      {dateEmpty ? `No earnings reports for ${dateLabel}` : 'No companies match your search'}
    </div>
  </div>
);
