import React, { useMemo, useState, useCallback } from 'react';
import { DateInfo } from './types';
import { toISODate, nyTodayISO } from './utils';

interface CalendarProps {
  selectedDate: string;
  onDateSelect: (date: string) => void;
  availableDates: DateInfo[];
}

const DAY_NAMES = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const MONTH_NAMES = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];

function parseISO(dateStr: string): Date {
  return new Date(`${dateStr}T00:00:00.000Z`);
}

/** Monday-anchored week containing anchorDate. */
function getWeekDays(anchorDate: string): Date[] {
  const date = parseISO(anchorDate);
  const dayOfWeek = date.getUTCDay();
  const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
  const monday = new Date(date);
  monday.setUTCDate(date.getUTCDate() + mondayOffset);
  const days: Date[] = [];
  for (let i = 0; i < 7; i++) {
    const d = new Date(monday);
    d.setUTCDate(monday.getUTCDate() + i);
    days.push(d);
  }
  return days;
}

/** 42-day grid (6 weeks, Mon-anchored) covering the month of anchorDate. */
function getMonthDays(anchorDate: string): Date[] {
  const first = new Date(Date.UTC(parseISO(anchorDate).getUTCFullYear(), parseISO(anchorDate).getUTCMonth(), 1));
  const dayOfWeek = first.getUTCDay();
  const mondayOffset = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
  const start = new Date(first);
  start.setUTCDate(first.getUTCDate() + mondayOffset);
  const days: Date[] = [];
  for (let i = 0; i < 42; i++) {
    const d = new Date(start);
    d.setUTCDate(start.getUTCDate() + i);
    days.push(d);
  }
  return days;
}

function isWeekend(dateStr: string): boolean {
  const day = parseISO(dateStr).getUTCDay();
  return day === 0 || day === 6;
}

type ViewMode = 'month' | 'week';

const Calendar: React.FC<CalendarProps> = ({ selectedDate, onDateSelect, availableDates }) => {
  const [view, setView] = useState<ViewMode>('month');
  const todayStr = nyTodayISO();

  const dateCountMap = useMemo(() => {
    const map: Record<string, number> = {};
    for (const d of availableDates) map[d.date] = d.count;
    return map;
  }, [availableDates]);

  // The grid is anchored on the selected date so navigation stays in sync.
  const days = useMemo(
    () => (view === 'month' ? getMonthDays(selectedDate) : getWeekDays(selectedDate)),
    [view, selectedDate]
  );

  const anchor = parseISO(selectedDate);
  const currentMonth = MONTH_NAMES[anchor.getUTCMonth()];
  const currentYear = anchor.getUTCFullYear();

  const step = useCallback(
    (direction: -1 | 1) => {
      const cur = parseISO(selectedDate);
      if (view === 'month') {
        cur.setUTCMonth(cur.getUTCMonth() + direction);
      } else {
        cur.setUTCDate(cur.getUTCDate() + direction * 7);
      }
      onDateSelect(toISODate(cur));
    },
    [view, selectedDate, onDateSelect]
  );

  const goToToday = useCallback(() => onDateSelect(todayStr), [todayStr, onDateSelect]);

  // Keyboard navigation: arrows move the selected date within the current grid.
  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent) => {
      const cur = parseISO(selectedDate);
      let handled = true;
      switch (e.key) {
        case 'ArrowLeft': cur.setUTCDate(cur.getUTCDate() - 1); break;
        case 'ArrowRight': cur.setUTCDate(cur.getUTCDate() + 1); break;
        case 'ArrowUp': cur.setUTCDate(cur.getUTCDate() - 7); break;
        case 'ArrowDown': cur.setUTCDate(cur.getUTCDate() + 7); break;
        case 'PageDown': step(1); break;
        case 'PageUp': step(-1); break;
        default: handled = false;
      }
      if (handled) {
        e.preventDefault();
        if (e.key !== 'PageDown' && e.key !== 'PageUp') onDateSelect(toISODate(cur));
      }
    },
    [selectedDate, onDateSelect, step]
  );

  const isOtherMonth = (d: Date) => view === 'month' && d.getUTCMonth() !== anchor.getUTCMonth();

  return (
    <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-neutral-200 dark:border-slate-800 p-4 sm:p-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-4 gap-2">
        <div className="min-w-0">
          <h2 className="text-lg sm:text-xl font-bold text-neutral-900 dark:text-white truncate">
            {currentMonth} {currentYear}
          </h2>
          <p className="text-xs sm:text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">
            Earnings Calendar
          </p>
        </div>
        <div className="flex items-center gap-1.5 sm:gap-2 shrink-0">
          {/* View toggle */}
          <div className="flex rounded-lg bg-neutral-100 dark:bg-slate-800 p-0.5">
            <button
              onClick={() => setView('month')}
              className={`px-2 py-1 text-[11px] font-semibold rounded-md transition-colors ${
                view === 'month'
                  ? 'bg-white dark:bg-slate-700 text-neutral-900 dark:text-white shadow-sm'
                  : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200'
              }`}
            >
              Month
            </button>
            <button
              onClick={() => setView('week')}
              className={`px-2 py-1 text-[11px] font-semibold rounded-md transition-colors ${
                view === 'week'
                  ? 'bg-white dark:bg-slate-700 text-neutral-900 dark:text-white shadow-sm'
                  : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200'
              }`}
            >
              Week
            </button>
          </div>
          <button
            onClick={goToToday}
            className="px-2.5 py-1.5 text-xs font-semibold rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors"
          >
            Today
          </button>
          <button
            onClick={() => step(-1)}
            className="p-2 rounded-lg hover:bg-neutral-100 dark:hover:bg-slate-800 transition-colors text-neutral-600 dark:text-neutral-400"
            aria-label={view === 'month' ? 'Previous month' : 'Previous week'}
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            onClick={() => step(1)}
            className="p-2 rounded-lg hover:bg-neutral-100 dark:hover:bg-slate-800 transition-colors text-neutral-600 dark:text-neutral-400"
            aria-label={view === 'month' ? 'Next month' : 'Next week'}
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

      {/* Day names */}
      <div className="grid grid-cols-7 gap-1 sm:gap-2 mb-2">
        {DAY_NAMES.map(day => (
          <div key={day} className="text-center text-[10px] sm:text-xs font-semibold text-neutral-400 dark:text-neutral-500 uppercase tracking-wider">
            {day}
          </div>
        ))}
      </div>

      {/* Grid (month or week) */}
      <div
        className="grid grid-cols-7 gap-1 sm:gap-2"
        role="grid"
        aria-label="Earnings calendar"
        tabIndex={0}
        onKeyDown={handleKeyDown}
      >
        {days.map((day) => {
          const dateStr = toISODate(day);
          const isSelected = dateStr === selectedDate;
          const isTodayCell = dateStr === todayStr;
          const isWeekendCell = isWeekend(dateStr);
          const count = dateCountMap[dateStr] || 0;
          const isPast = dateStr < todayStr;
          const other = isOtherMonth(day);

          return (
            <button
              key={dateStr}
              onClick={() => onDateSelect(dateStr)}
              aria-label={`${day.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', timeZone: 'UTC' })}${count > 0 ? `, ${count} earnings` : ''}`}
              aria-current={isSelected ? 'date' : undefined}
              aria-pressed={isSelected}
              className={`
                relative flex flex-col items-center justify-center
                rounded-xl py-2 sm:py-3 px-1 sm:px-2
                transition-all duration-200
                min-h-[56px] sm:min-h-[72px]
                ${isSelected
                  ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 scale-105'
                  : isTodayCell
                  ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-2 ring-blue-500/40'
                  : other
                  ? 'bg-transparent text-neutral-300 dark:text-neutral-700 hover:bg-neutral-50 dark:hover:bg-slate-800/40'
                  : isWeekendCell
                  ? 'bg-neutral-50 dark:bg-slate-800/50 text-neutral-400 dark:text-neutral-500 hover:bg-neutral-100 dark:hover:bg-slate-800'
                  : 'bg-neutral-50 dark:bg-slate-800/30 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-slate-800'
                }
              `}
            >
              <span className={`text-xs sm:text-sm font-semibold ${isSelected ? 'text-white' : ''} ${other ? 'opacity-60' : ''}`}>
                {day.getUTCDate()}
              </span>
              {count > 0 && (
                <span className={`
                  mt-1 text-[9px] sm:text-[10px] font-bold px-1.5 py-0.5 rounded-full
                  ${isSelected
                    ? 'bg-white/25 text-white'
                    : 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300'
                  }
                `}>
                  {count}
                </span>
              )}
              {isPast && count === 0 && !isSelected && !other && (
                <span className="mt-1 w-1 h-1 rounded-full bg-neutral-300 dark:bg-neutral-600" />
              )}
            </button>
          );
        })}
      </div>

      {/* Legend + hint */}
      <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] sm:text-xs text-neutral-400 dark:text-neutral-500">
        <div className="flex items-center gap-1.5">
          <div className="w-3 h-3 rounded-full bg-blue-600" />
          <span>Selected</span>
        </div>
        <div className="flex items-center gap-1.5">
          <div className="w-3 h-3 rounded-full bg-blue-50 dark:bg-blue-900/30 ring-2 ring-blue-500/40" />
          <span>Today</span>
        </div>
        <div className="flex items-center gap-1.5">
          <div className="w-3 h-3 rounded-full bg-blue-100 dark:bg-blue-900/50" />
          <span>Earnings</span>
        </div>
        <div className="hidden sm:block ml-auto opacity-70">Arrow keys to move • PgUp/PgDn to navigate</div>
      </div>
    </div>
  );
};

export default Calendar;
