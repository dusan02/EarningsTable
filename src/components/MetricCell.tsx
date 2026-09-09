import React from 'react';
import { getChangeColor, formatSignedPct } from '../utils';

interface MetricCellProps {
  label: string;
  /** Primary value (already-formatted string). */
  value: string;
  /** Optional secondary line (e.g. "Est $1.23"). */
  sub?: string;
  /** Signed delta used for the green/red color (and as a percentage if no deltaText). */
  delta?: number | null;
  /** Pre-formatted delta text. If omitted, delta is rendered as a signed percentage. */
  deltaText?: string;
  /** Decimal digits for the percentage fallback (default 1). */
  deltaDigits?: number;
  /** Compact card style (mobile) vs dense table cell (desktop). */
  variant?: 'card' | 'cell';
}

const Delta: React.FC<{ delta: number | null; deltaText?: string; deltaDigits?: number }> = ({ delta, deltaText, deltaDigits }) => {
  if (delta == null && !deltaText) return null;
  const text = deltaText ?? (delta != null ? formatSignedPct(delta, deltaDigits ?? 1) : '');
  if (!text) return null;
  return <div className={`text-xs font-semibold ${getChangeColor(delta ?? 0)}`}>{text}</div>;
};

/**
 * A single metric (Price / Mkt Cap / EPS / Revenue) rendered consistently in
 * both the mobile card grid and the desktop table. Eliminates the previous
 * duplication of value/sub/delta formatting between MobileCard and the row.
 *
 * `delta` drives the color and, by default, is rendered as a signed percentage
 * (for change/epsSurp/revSurp). For absolute deltas like marketCapDiff pass an
 * explicit `deltaText` (e.g. "+$36.48M") so it isn't mis-rendered as a percent.
 */
const MetricCell: React.FC<MetricCellProps> = ({ label, value, sub, delta, deltaText, deltaDigits, variant = 'cell' }) => {
  if (variant === 'card') {
    return (
      <div className="bg-neutral-50 dark:bg-slate-800/50 rounded-xl p-3">
        <div className="text-[10px] font-semibold text-neutral-400 dark:text-neutral-500 uppercase tracking-wider mb-1">
          {label}
        </div>
        <div className="text-base font-bold text-neutral-900 dark:text-white">{value}</div>
        {sub && <div className="text-[10px] text-neutral-400 dark:text-neutral-500 mt-0.5">{sub}</div>}
        <div className="mt-0.5"><Delta delta={delta ?? null} deltaText={deltaText} deltaDigits={deltaDigits} /></div>
      </div>
    );
  }

  return (
    <td className="px-4 py-3 text-right">
      <div className="text-sm font-bold text-neutral-900 dark:text-white">{value}</div>
      {sub && <div className="text-xs text-neutral-500 dark:text-neutral-400">{sub}</div>}
      <Delta delta={delta ?? null} deltaText={deltaText} deltaDigits={deltaDigits} />
    </td>
  );
};

export default MetricCell;
