import React, { useState, useMemo } from 'react';
import { FinalReportData, SortField, SortDirection } from './types';
import { formatMarketCap, formatRevenue, formatDate, formatSignedMarketCap } from './utils';
import CompanyCell from './components/CompanyCell';
import MetricCell from './components/MetricCell';
import MobileCard from './components/MobileCard';
import SearchBar from './components/SearchBar';
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

const EarningsTable: React.FC<EarningsTableProps> = ({ data, selectedDate }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [sortField, setSortField] = useState<SortField>('marketCap');
  const [sortDirection, setSortDirection] = useState<SortDirection>('desc');

  const filteredAndSortedData = useMemo(() => {
    const term = searchTerm.toLowerCase();
    let filtered = data.filter(item => {
      const sym = (item.symbol || '').toLowerCase();
      const name = (item.name || '').toLowerCase();
      return sym.includes(term) || name.includes(term);
    });

    filtered.sort((a, b) => {
      const aVal = a[sortField];
      const bVal = b[sortField];
      if (aVal === null || aVal === undefined || aVal === '') return 1;
      if (bVal === null || bVal === undefined || bVal === '') return -1;
      const aNum = Number(aVal);
      const bNum = Number(bVal);
      if (!isNaN(aNum) && !isNaN(bNum) && isFinite(aNum) && isFinite(bNum)) {
        return sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
      }
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
      <SearchBar value={searchTerm} onChange={setSearchTerm} resultCount={filteredAndSortedData.length} />

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
            <caption className="sr-only">
              Earnings reports for {formatDate(selectedDate)} — {filteredAndSortedData.length} companies
            </caption>
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
                <tr key={item.symbol} className="hover:bg-neutral-50 dark:hover:bg-slate-800/40 transition-colors" aria-label={`${item.symbol} ${item.name || ''}`}>
                  <th scope="row" className="px-4 py-3 text-left font-normal">
                    <CompanyCell item={item} nameMaxWidth="200px" />
                  </th>
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
        </div>
      </div>
    </div>
  );
};

export default EarningsTable;
