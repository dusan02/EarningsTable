import React, { useState, useMemo } from 'react';
import { FinalReportData, SortField, SortDirection } from './types';
import { formatMarketCap, formatRevenue, formatDate, formatSignedMarketCap } from './utils';
import CompanyCell from './components/CompanyCell';
import MetricCell from './components/MetricCell';
import { EmptyState } from './components/States';

interface EarningsTableProps {
  data: FinalReportData[];
  selectedDate: string;
}

const SortIcon: React.FC<{ field: SortField; sortField: SortField; sortDirection: SortDirection }> = ({ field, sortField, sortDirection }) => {
  if (sortField !== field) {
    return <span className="text-neutral-300 dark:text-neutral-600 ml-1 text-xs">↕</span>;
  }
  return <span className="text-blue-600 dark:text-blue-400 ml-1 text-xs">{sortDirection === 'asc' ? '↑' : '↓'}</span>;
};

const MobileCard: React.FC<{ item: FinalReportData }> = ({ item }) => (
  <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-md border border-neutral-200 dark:border-slate-800 p-4 mb-3">
    <div className="flex items-center gap-3 mb-3">
      <CompanyCell item={item} />
    </div>
    <div className="grid grid-cols-2 gap-2">
      <MetricCell variant="card" label="Price" value={item.price != null ? `$${item.price.toFixed(2)}` : '-'} delta={item.change} deltaDigits={2} />
      <MetricCell
        variant="card"
        label="Mkt Cap"
        value={formatMarketCap(item.marketCap)}
        delta={item.marketCapDiff ? Number(item.marketCapDiff) : null}
        deltaText={item.marketCapDiff ? formatSignedMarketCap(item.marketCapDiff) : undefined}
      />
      <MetricCell
        variant="card"
        label="EPS"
        value={item.epsActual != null ? `$${item.epsActual.toFixed(2)}` : '-'}
        sub={`Est ${item.epsEst != null ? `$${item.epsEst.toFixed(2)}` : '-'}`}
        delta={item.epsSurp}
      />
      <MetricCell
        variant="card"
        label="Revenue"
        value={formatRevenue(item.revActual)}
        sub={`Est ${formatRevenue(item.revEst)}`}
        delta={item.revSurp}
      />
    </div>
  </div>
);

const EarningsTable: React.FC<EarningsTableProps> = ({ data, selectedDate }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [sortField, setSortField] = useState<SortField>('marketCap');
  const [sortDirection, setSortDirection] = useState<SortDirection>('desc');

  const filteredAndSortedData = useMemo(() => {
    const term = searchTerm.toLowerCase();
    let filtered = data.filter(item => {
      // Null-safe search (H4): coerce missing fields to empty string.
      const sym = (item.symbol || '').toLowerCase();
      const name = (item.name || '').toLowerCase();
      return sym.includes(term) || name.includes(term);
    });

    filtered.sort((a, b) => {
      const aVal = a[sortField];
      const bVal = b[sortField];

      // Treat null/undefined as "smallest" so they sort to the end on desc.
      if (aVal === null || aVal === undefined || aVal === '') return 1;
      if (bVal === null || bVal === undefined || bVal === '') return -1;

      // Try numeric comparison FIRST (H5): numeric strings like BigInt-serialized
      // marketCap ("1234567890123") must compare numerically, not lexically.
      const aNum = Number(aVal);
      const bNum = Number(bVal);
      if (!isNaN(aNum) && !isNaN(bNum) && isFinite(aNum) && isFinite(bNum)) {
        return sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
      }

      // Fall back to string comparison for non-numeric values (e.g. symbol, name).
      if (typeof aVal === 'string' && typeof bVal === 'string') {
        return sortDirection === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
      }

      return 0;
    });

    return filtered;
  }, [data, searchTerm, sortField, sortDirection]);

  const handleSort = (field: SortField) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('desc');
    }
  };

  const sortableTh = (
    field: SortField,
    label: string,
    align: 'left' | 'right' = 'right'
  ): React.ReactElement => (
    <th
      scope="col"
      aria-sort={sortField === field ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'}
      className={`px-4 py-3 ${align === 'left' ? 'text-left' : 'text-right'} text-xs font-bold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider cursor-pointer hover:bg-neutral-50 dark:hover:bg-slate-800/50 transition-colors sticky top-0 bg-white dark:bg-slate-900 z-10`}
      onClick={() => handleSort(field)}
    >
      <span className={`flex items-center ${align === 'left' ? '' : 'justify-end'}`}>
        {label} <SortIcon field={field} sortField={sortField} sortDirection={sortDirection} />
      </span>
    </th>
  );

  return (
    <div className="fade-in">
      {/* Search bar */}
      <div className="mb-4 flex items-center gap-3">
        <div className="relative flex-1 max-w-md">
          <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search companies..."
            aria-label="Search companies by symbol or name"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-10 pr-9 py-2.5 text-sm rounded-xl border border-neutral-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-neutral-900 dark:text-white placeholder-neutral-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
          />
          {searchTerm && (
            <button
              onClick={() => setSearchTerm('')}
              aria-label="Clear search"
              className="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 flex items-center justify-center rounded-full text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-slate-800 transition-colors"
            >
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          )}
        </div>
        <div className="text-sm text-neutral-500 dark:text-neutral-400 whitespace-nowrap">
          {filteredAndSortedData.length} {filteredAndSortedData.length === 1 ? 'company' : 'companies'}
        </div>
      </div>

      {/* Mobile: Card layout */}
      <div className="md:hidden space-y-0">
        {filteredAndSortedData.map((item) => (
          <MobileCard key={item.symbol} item={item} />
        ))}
        {filteredAndSortedData.length === 0 && (
          <EmptyState dateEmpty={data.length === 0} dateLabel={formatDate(selectedDate)} />
        )}
      </div>

      {/* Desktop: Table layout */}
      <div className="hidden md:block bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-neutral-200 dark:border-slate-800 overflow-hidden">
        <div className="overflow-auto max-h-[calc(100vh-220px)]">
          <table className="w-full" style={{ minWidth: '900px' }}>
            <thead>
              <tr className="border-b border-neutral-200 dark:border-slate-800">
                {sortableTh('symbol', 'Company', 'left')}
                {sortableTh('marketCap', 'Mkt Cap')}
                {sortableTh('price', 'Price')}
                {sortableTh('epsSurp', 'EPS')}
                {sortableTh('revSurp', 'Revenue')}
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-slate-800">
              {filteredAndSortedData.map((item) => (
                <tr key={item.symbol} className="hover:bg-neutral-50 dark:hover:bg-slate-800/40 transition-colors">
                  <td className="px-4 py-3">
                    <CompanyCell item={item} nameMaxWidth="200px" />
                  </td>
                  <MetricCell
                    variant="cell"
                    label="Mkt Cap"
                    value={formatMarketCap(item.marketCap)}
                    delta={item.marketCapDiff ? Number(item.marketCapDiff) : null}
                    deltaText={item.marketCapDiff ? formatSignedMarketCap(item.marketCapDiff) : undefined}
                  />
                  <MetricCell
                    variant="cell"
                    label="Price"
                    value={item.price != null ? `$${item.price.toFixed(2)}` : '-'}
                    delta={item.change}
                    deltaDigits={2}
                  />
                  <MetricCell
                    variant="cell"
                    label="EPS"
                    value={item.epsActual != null ? `$${item.epsActual.toFixed(2)}` : '-'}
                    sub={`Est: ${item.epsEst != null ? `$${item.epsEst.toFixed(2)}` : '-'}`}
                    delta={item.epsSurp}
                  />
                  <MetricCell
                    variant="cell"
                    label="Revenue"
                    value={formatRevenue(item.revActual)}
                    sub={`Est: ${formatRevenue(item.revEst)}`}
                    delta={item.revSurp}
                  />
                </tr>
              ))}
            </tbody>
          </table>
          {filteredAndSortedData.length === 0 && (
            <EmptyState dateEmpty={data.length === 0} dateLabel={formatDate(selectedDate)} />
          )}
        </div>
      </div>
    </div>
  );
};

export default EarningsTable;
