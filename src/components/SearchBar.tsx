import React from 'react';

interface SearchBarProps {
  value: string;
  onChange: (v: string) => void;
  resultCount: number;
}

/**
 * Search bar with clear button and result count.
 * Sticky on mobile so it stays visible while scrolling through cards.
 */
const SearchBar: React.FC<SearchBarProps> = ({ value, onChange, resultCount }) => (
  <div className="mb-3 flex items-center gap-2 sm:gap-3 sticky top-16 sm:top-20 z-30 bg-neutral-50 dark:bg-slate-950 py-2 -mx-1 px-1">
    <div className="relative flex-1 max-w-md">
      <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <input
        type="text"
        placeholder="Search..."
        aria-label="Search companies by symbol or name"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full pl-9 pr-8 py-2 text-sm rounded-xl border border-neutral-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-neutral-900 dark:text-white placeholder-neutral-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
      />
      {value && (
        <button
          onClick={() => onChange('')}
          aria-label="Clear search"
          className="absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-slate-800 transition-colors"
        >
          <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      )}
    </div>
    <div className="text-xs sm:text-sm text-neutral-500 dark:text-neutral-400 whitespace-nowrap shrink-0">
      {resultCount} {resultCount === 1 ? 'co.' : 'cos.'}
    </div>
  </div>
);

export default SearchBar;
