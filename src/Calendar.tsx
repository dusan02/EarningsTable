import React, { useMemo } from 'react';
import { DateInfo } from './types';

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

function toISODate(d: Date): string {
  return d.toISOString().split('T')[0];
}

function getWeekDays(anchorDate: string): Date[] {
  const date = new Date(`${anchorDate}T00:00:00.000Z`);
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

function isToday(dateStr: string): boolean {
  return toISODate(new Date()) === dateStr;
}

function isWeekend(dateStr: string): boolean {
  const day = new Date(`${dateStr}T00:00:00.000Z`).getUTCDay();
  return day === 0 || day === 6;
}

const Calendar: React.FC<CalendarProps> = ({ selectedDate, onDateSelect, availableDates }) => {
  const weekDays = useMemo(() => getWeekDays(selectedDate), [selectedDate]);
  const dateCountMap = useMemo(() => {
    const map: Record<string, number> = {};
    for (const d of availableDates) {
      map[d.date] = d.count;
    }
    return map;
  }, [availableDates]);

  const today = new Date();
  const todayStr = toISODate(today);
  const currentMonth = MONTH_NAMES[new Date(`${selectedDate}T00:00:00.000Z`).getUTCMonth()];
  const currentYear = new Date(`${selectedDate}T00:00:00.000Z`).getUTCFullYear();

  const navigateWeek = (direction: -1 | 1) => {
    const current = new Date(`${selectedDate}T00:00:00.000Z`);
    current.setUTCDate(current.getUTCDate() + direction * 7);
    onDateSelect(toISODate(current));
  };

  const goToToday = () => onDateSelect(todayStr);

  return (
    <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-neutral-200 dark:border-slate-800 p-4 sm:p-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <div>
          <h2 className="text-lg sm:text-xl font-bold text-neutral-900 dark:text-white">
            {currentMonth} {currentYear}
          </h2>
          <p className="text-xs sm:text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">
            Earnings Calendar
          </p>
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={goToToday}
            className="px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors"
          >
            Today
          </button>
          <button
            onClick={() => navigateWeek(-1)}
            className="p-2 rounded-lg hover:bg-neutral-100 dark:hover:bg-slate-800 transition-colors text-neutral-600 dark:text-neutral-400"
            aria-label="Previous week"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            onClick={() => navigateWeek(1)}
            className="p-2 rounded-lg hover:bg-neutral-100 dark:hover:bg-slate-800 transition-colors text-neutral-600 dark:text-neutral-400"
            aria-label="Next week"
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

      {/* Week days */}
      <div className="grid grid-cols-7 gap-1 sm:gap-2">
        {weekDays.map((day) => {
          const dateStr = toISODate(day);
          const isSelected = dateStr === selectedDate;
          const isTodayCell = isToday(dateStr);
          const isWeekendCell = isWeekend(dateStr);
          const count = dateCountMap[dateStr] || 0;
          const isPast = dateStr < todayStr;

          return (
            <button
              key={dateStr}
              onClick={() => onDateSelect(dateStr)}
              className={`
                relative flex flex-col items-center justify-center
                rounded-xl py-2 sm:py-3 px-1 sm:px-2
                transition-all duration-200
                min-h-[56px] sm:min-h-[72px]
                ${isSelected
                  ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 scale-105'
                  : isTodayCell
                  ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 ring-2 ring-blue-500/40'
                  : isWeekendCell
                  ? 'bg-neutral-50 dark:bg-slate-800/50 text-neutral-400 dark:text-neutral-500 hover:bg-neutral-100 dark:hover:bg-slate-800'
                  : 'bg-neutral-50 dark:bg-slate-800/30 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-slate-800'
                }
              `}
            >
              <span className={`text-xs sm:text-sm font-semibold ${isSelected ? 'text-white' : ''}`}>
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
              {isPast && count === 0 && !isSelected && (
                <span className="mt-1 w-1 h-1 rounded-full bg-neutral-300 dark:bg-neutral-600" />
              )}
            </button>
          );
        })}
      </div>

      {/* Legend */}
      <div className="mt-4 flex items-center gap-4 text-[10px] sm:text-xs text-neutral-400 dark:text-neutral-500">
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
      </div>
    </div>
  );
};

export default Calendar;
