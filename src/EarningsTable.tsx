import React, { useState, useMemo } from 'react';
import { FinalReportData, SortField, SortDirection } from './types';

interface EarningsTableProps {
  data: FinalReportData[];
  selectedDate: string;
}

const EarningsTable: React.FC<EarningsTableProps> = ({ data, selectedDate }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [sortField, setSortField] = useState<SortField>('marketCap');
  const [sortDirection, setSortDirection] = useState<SortDirection>('desc');

  const filteredAndSortedData = useMemo(() => {
    let filtered = data.filter(item =>
      item.symbol?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      item.name?.toLowerCase().includes(searchTerm.toLowerCase())
    );

    filtered.sort((a, b) => {
      const aVal = a[sortField];
      const bVal = b[sortField];

      if (aVal === null || aVal === undefined) return 1;
      if (bVal === null || bVal === undefined) return -1;

      if (typeof aVal === 'string' && typeof bVal === 'string') {
        return sortDirection === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
      }

      const aNum = Number(aVal);
      const bNum = Number(bVal);
      if (!isNaN(aNum) && !isNaN(bNum)) {
        return sortDirection === 'asc' ? aNum - bNum : bNum - aNum;
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

  const formatMarketCap = (value: number | string | null): string => {
    if (!value) return '-';
    const num = Number(value);
    if (!isFinite(num)) return '-';
    const abs = Math.abs(num);
    const sign = num < 0 ? '-' : '';
    if (abs >= 1e12) return `${sign}$${(abs / 1e12).toFixed(2)}T`;
    if (abs >= 1e9) return `${sign}$${(abs / 1e9).toFixed(2)}B`;
    if (abs >= 1e6) return `${sign}$${(abs / 1e6).toFixed(2)}M`;
    if (abs >= 1e3) return `${sign}$${(abs / 1e3).toFixed(0)}K`;
    return `${sign}$${abs.toFixed(0)}`;
  };

  const formatRevenue = (value: number | string | null): string => {
    if (!value) return '-';
    const num = Number(value);
    if (!isFinite(num) || num <= 0) return '-';
    const abs = Math.abs(num);
    if (abs >= 1e12) return `$${(abs / 1e12).toFixed(2)}T`;
    if (abs >= 1e9) return `$${(abs / 1e9).toFixed(2)}B`;
    if (abs >= 1e6) return `$${(abs / 1e6).toFixed(2)}M`;
    return `$${abs.toFixed(0)}`;
  };

  const getChangeColor = (value: number | null): string => {
    if (value === null || value === undefined) return 'text-neutral-400 dark:text-neutral-500';
    if (value > 0) return 'text-emerald-600 dark:text-emerald-400';
    if (value < 0) return 'text-red-600 dark:text-red-400';
    return 'text-neutral-500 dark:text-neutral-400';
  };

  const getSizeBadge = (size: string | null): { label: string; classes: string } => {
    switch (size?.toLowerCase()) {
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
  };

  const SortIcon: React.FC<{ field: SortField }> = ({ field }) => {
    if (sortField !== field) {
      return <span className="text-neutral-300 dark:text-neutral-600 ml-1 text-xs">↕</span>;
    }
    return <span className="text-blue-600 dark:text-blue-400 ml-1 text-xs">{sortDirection === 'asc' ? '↑' : '↓'}</span>;
  };

  const CompanyLogo: React.FC<{ symbol: string; logoUrl: string | null; name: string }> = ({ symbol, logoUrl, name }) => {
    const [imgError, setImgError] = useState(false);

    if (logoUrl && !imgError) {
      return (
        <img
          src={logoUrl}
          alt={`${symbol} logo`}
          className="w-9 h-9 sm:w-10 sm:h-10 rounded-lg object-contain bg-white dark:bg-slate-100 border border-neutral-200 dark:border-slate-700"
          onError={() => setImgError(true)}
          loading="lazy"
        />
      );
    }

    return (
      <div className="w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs sm:text-sm shadow-sm">
        {symbol?.slice(0, 3)}
      </div>
    );
  };

  const formatDate = (dateStr: string): string => {
    const d = new Date(`${dateStr}T00:00:00.000Z`);
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', timeZone: 'UTC' });
  };

  const MobileCard: React.FC<{ item: FinalReportData }> = ({ item }) => {
    const sizeBadge = getSizeBadge(item.size);
    return (
      <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-md border border-neutral-200 dark:border-slate-800 p-4 mb-3">
        {/* Header: logo + symbol + name + size */}
        <div className="flex items-center gap-3 mb-3">
          <CompanyLogo symbol={item.symbol} logoUrl={item.logoUrl} name={item.name} />
          <div className="min-w-0 flex-1">
            <div className="flex items-center gap-2">
              <span className="text-sm font-bold text-neutral-900 dark:text-white">{item.symbol}</span>
              <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-md ${sizeBadge.classes}`}>
                {sizeBadge.label}
              </span>
            </div>
            <div className="text-xs text-neutral-500 dark:text-neutral-400 truncate">{item.name}</div>
          </div>
        </div>

        {/* Data grid: 2x2 */}
        <div className="grid grid-cols-2 gap-2">
          {/* Price */}
          <div className="bg-neutral-50 dark:bg-slate-800/50 rounded-xl p-3">
            <div className="text-[10px] font-semibold text-neutral-400 dark:text-neutral-500 uppercase tracking-wider mb-1">Price</div>
            <div className="text-base font-bold text-neutral-900 dark:text-white">
              {item.price != null ? `$${item.price.toFixed(2)}` : '-'}
            </div>
            <div className={`text-xs font-medium ${getChangeColor(item.change)}`}>
              {item.change != null && item.change !== 0 ? `${item.change > 0 ? '+' : ''}${item.change.toFixed(2)}%` : ''}
            </div>
          </div>

          {/* Mkt Cap */}
          <div className="bg-neutral-50 dark:bg-slate-800/50 rounded-xl p-3">
            <div className="text-[10px] font-semibold text-neutral-400 dark:text-neutral-500 uppercase tracking-wider mb-1">Mkt Cap</div>
            <div className="text-base font-bold text-neutral-900 dark:text-white">{formatMarketCap(item.marketCap)}</div>
            <div className={`text-xs font-medium ${getChangeColor(item.marketCapDiff ? Number(item.marketCapDiff) : null)}`}>
              {item.marketCapDiff && Number(item.marketCapDiff) !== 0 ? `${Number(item.marketCapDiff) > 0 ? '+' : ''}${formatMarketCap(item.marketCapDiff)}` : ''}
            </div>
          </div>

          {/* EPS */}
          <div className="bg-neutral-50 dark:bg-slate-800/50 rounded-xl p-3">
            <div className="text-[10px] font-semibold text-neutral-400 dark:text-neutral-500 uppercase tracking-wider mb-1">EPS</div>
            <div className="flex items-baseline gap-1.5">
              <span className="text-sm font-bold text-neutral-900 dark:text-white">
                {item.epsActual != null ? `$${item.epsActual.toFixed(2)}` : '-'}
              </span>
              <span className="text-[10px] text-neutral-400 dark:text-neutral-500">
                Est {item.epsEst != null ? `$${item.epsEst.toFixed(2)}` : '-'}
              </span>
            </div>
            <div className={`text-xs font-semibold ${getChangeColor(item.epsSurp)}`}>
              {item.epsSurp != null ? `${item.epsSurp > 0 ? '+' : ''}${item.epsSurp.toFixed(1)}%` : ''}
            </div>
          </div>

          {/* Revenue */}
          <div className="bg-neutral-50 dark:bg-slate-800/50 rounded-xl p-3">
            <div className="text-[10px] font-semibold text-neutral-400 dark:text-neutral-500 uppercase tracking-wider mb-1">Revenue</div>
            <div className="flex items-baseline gap-1.5">
              <span className="text-sm font-bold text-neutral-900 dark:text-white">{formatRevenue(item.revActual)}</span>
              <span className="text-[10px] text-neutral-400 dark:text-neutral-500">
                Est {formatRevenue(item.revEst)}
              </span>
            </div>
            <div className={`text-xs font-semibold ${getChangeColor(item.revSurp)}`}>
              {item.revSurp != null ? `${item.revSurp > 0 ? '+' : ''}${item.revSurp.toFixed(1)}%` : ''}
            </div>
          </div>
        </div>
      </div>
    );
  };

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
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-10 pr-4 py-2.5 text-sm rounded-xl border border-neutral-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-neutral-900 dark:text-white placeholder-neutral-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
          />
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
          <div className="text-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-neutral-200 dark:border-slate-800">
            <div className="text-neutral-300 dark:text-neutral-600 text-4xl mb-3">📊</div>
            <div className="text-neutral-500 dark:text-neutral-400 text-sm font-medium">
              {data.length === 0 ? `No earnings for ${formatDate(selectedDate)}` : 'No companies match your search'}
            </div>
          </div>
        )}
      </div>

      {/* Desktop: Table layout */}
      <div className="hidden md:block bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-neutral-200 dark:border-slate-800 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full" style={{ minWidth: '900px' }}>
            <thead>
              <tr className="border-b border-neutral-200 dark:border-slate-800">
                <th
                  className="px-4 py-3 text-left text-xs font-bold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider cursor-pointer hover:bg-neutral-50 dark:hover:bg-slate-800/50 transition-colors"
                  onClick={() => handleSort('symbol')}
                >
                  <span className="flex items-center">Company <SortIcon field="symbol" /></span>
                </th>
                <th
                  className="px-4 py-3 text-right text-xs font-bold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider cursor-pointer hover:bg-neutral-50 dark:hover:bg-slate-800/50 transition-colors"
                  onClick={() => handleSort('marketCap')}
                >
                  <span className="flex items-center justify-end">Mkt Cap <SortIcon field="marketCap" /></span>
                </th>
                <th
                  className="px-4 py-3 text-right text-xs font-bold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider cursor-pointer hover:bg-neutral-50 dark:hover:bg-slate-800/50 transition-colors"
                  onClick={() => handleSort('price')}
                >
                  <span className="flex items-center justify-end">Price <SortIcon field="price" /></span>
                </th>
                <th
                  className="px-4 py-3 text-right text-xs font-bold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider cursor-pointer hover:bg-neutral-50 dark:hover:bg-slate-800/50 transition-colors"
                  onClick={() => handleSort('epsSurp')}
                >
                  <span className="flex items-center justify-end">EPS <SortIcon field="epsSurp" /></span>
                </th>
                <th
                  className="px-4 py-3 text-right text-xs font-bold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider cursor-pointer hover:bg-neutral-50 dark:hover:bg-slate-800/50 transition-colors"
                  onClick={() => handleSort('revSurp')}
                >
                  <span className="flex items-center justify-end">Revenue <SortIcon field="revSurp" /></span>
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-neutral-100 dark:divide-slate-800">
              {filteredAndSortedData.map((item) => {
            const sizeBadge = getSizeBadge(item.size);
            return (
              <tr key={item.symbol} className="hover:bg-neutral-50 dark:hover:bg-slate-800/40 transition-colors">
                {/* Company */}
                <td className="px-4 py-3">
                  <div className="flex items-center gap-3">
                    <CompanyLogo symbol={item.symbol} logoUrl={item.logoUrl} name={item.name} />
                    <div className="min-w-0">
                      <div className="flex items-center gap-2">
                        <span className="text-sm font-bold text-neutral-900 dark:text-white">{item.symbol}</span>
                        <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-md ${sizeBadge.classes}`}>
                          {sizeBadge.label}
                        </span>
                      </div>
                      <div className="text-xs text-neutral-500 dark:text-neutral-400 truncate max-w-[200px]">{item.name}</div>
                    </div>
                  </div>
                </td>

                {/* Market Cap */}
                <td className="px-4 py-3 text-right">
                  <div className="text-sm font-bold text-neutral-900 dark:text-white">{formatMarketCap(item.marketCap)}</div>
                  <div className={`text-xs font-medium ${getChangeColor(item.marketCapDiff ? Number(item.marketCapDiff) : null)}`}>
                    {item.marketCapDiff && Number(item.marketCapDiff) !== 0
                      ? `${Number(item.marketCapDiff) > 0 ? '+' : ''}${formatMarketCap(item.marketCapDiff)}`
                      : ''}
                  </div>
                </td>

                {/* Price */}
                <td className="px-4 py-3 text-right">
                  <div className="text-sm font-bold text-neutral-900 dark:text-white">
                    {item.price != null ? `$${item.price.toFixed(2)}` : '-'}
                  </div>
                  <div className={`text-xs font-medium ${getChangeColor(item.change)}`}>
                    {item.change != null && item.change !== 0
                      ? `${item.change > 0 ? '+' : ''}${item.change.toFixed(2)}%`
                      : ''}
                  </div>
                </td>

                {/* EPS */}
                <td className="px-4 py-3 text-right">
                  <div className="text-sm font-bold text-neutral-900 dark:text-white">
                    {item.epsActual != null ? `$${item.epsActual.toFixed(2)}` : '-'}
                  </div>
                  <div className="text-xs text-neutral-500 dark:text-neutral-400">
                    Est: {item.epsEst != null ? `$${item.epsEst.toFixed(2)}` : '-'}
                  </div>
                  <div className={`text-xs font-semibold ${getChangeColor(item.epsSurp)}`}>
                    {item.epsSurp != null ? `${item.epsSurp > 0 ? '+' : ''}${item.epsSurp.toFixed(1)}%` : ''}
                  </div>
                </td>

                {/* Revenue */}
                <td className="px-4 py-3 text-right">
                  <div className="text-sm font-bold text-neutral-900 dark:text-white">
                    {formatRevenue(item.revActual)}
                  </div>
                  <div className="text-xs text-neutral-500 dark:text-neutral-400">
                    Est: {formatRevenue(item.revEst)}
                  </div>
                  <div className={`text-xs font-semibold ${getChangeColor(item.revSurp)}`}>
                    {item.revSurp != null ? `${item.revSurp > 0 ? '+' : ''}${item.revSurp.toFixed(1)}%` : ''}
                  </div>
                </td>
              </tr>
            );
          })}
            </tbody>
          </table>
        </div>

        {filteredAndSortedData.length === 0 && (
          <div className="text-center py-16">
            <div className="text-neutral-300 dark:text-neutral-600 text-4xl mb-3">📊</div>
            <div className="text-neutral-500 dark:text-neutral-400 text-sm font-medium">
              {data.length === 0
                ? `No earnings reports for ${formatDate(selectedDate)}`
                : 'No companies match your search'}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default EarningsTable;
