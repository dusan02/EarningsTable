import React from 'react';
import CompanyLogo from './CompanyLogo';
import { FinalReportData } from '../types';
import { computeSizeFromMarketCap } from '../utils';

/** Size badge config, computing from marketCap when the API size is missing/wrong. */
function getSizeBadge(size: string | null, marketCap?: number | string | null): { label: string; classes: string } {
  let effectiveSize = size?.toLowerCase() || '';
  if (marketCap != null) {
    const computed = computeSizeFromMarketCap(marketCap);
    if (!effectiveSize || effectiveSize === 'unknown' || computed !== effectiveSize) {
      effectiveSize = computed;
    }
  }
  switch (effectiveSize) {
    case 'mega':
      return { label: 'Mega', classes: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' };
    case 'large':
      return { label: 'Large', classes: 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300' };
    case 'mid':
      return { label: 'Mid', classes: 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' };
    case 'small':
      return { label: 'Small', classes: 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400' };
    default:
      return { label: size || '-', classes: 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400' };
  }
}

interface CompanyCellProps {
  item: FinalReportData;
  /** Truncate the company name to this max width (desktop only). */
  nameMaxWidth?: string;
}

/** Logo + symbol + size badge + company name. Shared by mobile card and desktop row. */
const CompanyCell: React.FC<CompanyCellProps> = ({ item, nameMaxWidth }) => {
  const sizeBadge = getSizeBadge(item.size, item.marketCap);
  return (
    <div className="flex items-center gap-3">
      <CompanyLogo symbol={item.symbol} logoUrl={item.logoUrl} name={item.name} />
      <div className="min-w-0">
        <div className="flex items-center gap-2">
          <span className="text-sm font-bold text-neutral-900 dark:text-white">{item.symbol}</span>
          <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-md ${sizeBadge.classes}`}>
            {sizeBadge.label}
          </span>
        </div>
        <div
          className="text-xs text-neutral-500 dark:text-neutral-400 truncate"
          style={nameMaxWidth ? { maxWidth: nameMaxWidth } : undefined}
        >
          {item.name}
        </div>
      </div>
    </div>
  );
};

export default CompanyCell;
