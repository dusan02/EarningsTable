 import React, { useState, useMemo } from 'react';

interface FinalReportData {
  symbol: string;
  name: string;
  size: string;
  marketCap: number;
  marketCapDiff: number;
  price: number;
  change: number;
  epsActual: number;
  epsEst: number;
  epsSurp: number;
  revActual: number;
  revEst: number;
  revSurp: number;
  // Logo fields
  logoUrl: string | null;
  logoSource: string | null;
  logoFetchedAt: string | null;
}

interface EarningsTableProps {
  data: FinalReportData[];
}

const EarningsTable: React.FC<EarningsTableProps> = ({ data }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [sortField, setSortField] = useState<keyof FinalReportData>('symbol');
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
  const [theme, setTheme] = useState<'light' | 'dark'>('light');

  const filteredAndSortedData = useMemo(() => {
    let filtered = data.filter(item =>
      item.symbol.toLowerCase().includes(searchTerm.toLowerCase()) ||
      item.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    filtered.sort((a, b) => {
      const aVal = a[sortField];
      const bVal = b[sortField];
      
      if (aVal === null || aVal === undefined) return 1;
      if (bVal === null || bVal === undefined) return -1;
      
      if (typeof aVal === 'string' && typeof bVal === 'string') {
        return sortDirection === 'asc' 
          ? aVal.localeCompare(bVal)
          : bVal.localeCompare(aVal);
      }
      
      if (typeof aVal === 'number' && typeof bVal === 'number') {
        return sortDirection === 'asc' ? aVal - bVal : bVal - aVal;
      }
      
      return 0;
    });

    return filtered;
  }, [data, searchTerm, sortField, sortDirection]);

  const handleSort = (field: keyof FinalReportData) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('asc');
    }
  };

  const formatMarketCap = (value: number) => {
    if (value >= 1e12) return `$${(value / 1e12).toFixed(1)}T`;
    if (value >= 1e9) return `$${(value / 1e9).toFixed(1)}B`;
    if (value >= 1e6) return `$${(value / 1e6).toFixed(1)}M`;
    return `$${value.toFixed(0)}`;
  };

  const formatRevenue = (value: number) => {
    if (value >= 1e9) return `$${(value / 1e9).toFixed(1)}B`;
    if (value >= 1e6) return `$${(value / 1e6).toFixed(1)}M`;
    return `$${value.toFixed(0)}`;
  };

  const getSizeColor = (size: string) => {
    switch (size?.toLowerCase()) {
      case 'mega': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
      case 'large': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
      case 'mid': return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
      case 'small': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
  };

  const getChangeColor = (value: number) => {
    if (value > 0) return 'text-green-600 dark:text-green-400';
    if (value < 0) return 'text-red-600 dark:text-red-400';
    return 'text-gray-600 dark:text-gray-400';
  };

  const SortIcon: React.FC<{ field: keyof FinalReportData }> = ({ field }) => {
    if (sortField !== field) {
      return <span className="text-gray-400">↕</span>;
    }
    return <span className="text-blue-600">{sortDirection === 'asc' ? '↑' : '↓'}</span>;
  };

  // Company logo component with fallback
  const CompanyLogo: React.FC<{ symbol: string; logoUrl: string | null; name: string }> = ({ symbol, logoUrl, name }) => {
    if (logoUrl) {
      return (
        <div className="flex-shrink-0 relative">
          <img 
            src={logoUrl} 
            alt={`${symbol} logo`} 
            className="w-10 h-10 sm:w-12 md:w-14 lg:w-16 sm:h-12 md:h-14 lg:h-16 object-contain"
            onError={(e) => {
              // Hide image on error and show fallback
              e.currentTarget.style.display = 'none';
              e.currentTarget.nextElementSibling?.classList.remove('hidden');
            }}
          />
          <div className="w-10 h-10 sm:w-12 md:w-14 lg:w-16 sm:h-12 md:h-14 lg:h-16 rounded-lg sm:rounded-xl bg-white dark:bg-slate-400 border border-gray-400 dark:border-white flex items-center justify-center text-blue-600 dark:text-blue-600 font-bold text-xs sm:text-sm shadow-sm hidden">
            {symbol}
          </div>
        </div>
      );
    }
    
    // Fallback: show initials in a square
    return (
      <div className="flex-shrink-0 relative">
        <div className="w-10 h-10 sm:w-12 md:w-14 lg:w-16 sm:h-12 md:h-14 lg:h-16 rounded-lg sm:rounded-xl bg-white dark:bg-slate-400 border border-gray-400 dark:border-white flex items-center justify-center text-blue-600 dark:text-blue-600 font-bold text-xs sm:text-sm shadow-sm">
          {symbol}
        </div>
      </div>
    );
  };

  return (
    <div className="min-h-screen bg-neutral-50 dark:bg-slate-900 transition-colors duration-300">
      {/* Header */}
      <div className="bg-white dark:bg-slate-800 shadow-lg border-b border-neutral-200 dark:border-transparent">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div className="flex items-center space-x-3">
                {/* Bar chart icon */}
                <div className="flex items-center space-x-1">
                  <div className="w-2 h-4 bg-green-500 rounded-sm"></div>
                  <div className="w-2 h-6 bg-orange-500 rounded-sm"></div>
                  <div className="w-2 h-3 bg-blue-500 rounded-sm"></div>
                  <div className="w-2 h-5 bg-purple-500 rounded-sm"></div>
                </div>
                <h1 className="text-3xl font-bold text-gray-900 dark:text-white font-sans">
                  Earnings Table
                </h1>
              </div>
              <p className="mt-2 text-base text-gray-600 dark:text-gray-400 font-normal font-sans">
                Company earnings and financial data
              </p>
            </div>
            
            <div className="mt-4 sm:mt-0 flex items-center space-x-4">
              {/* Theme Toggle */}
              <button
                onClick={() => setTheme(theme === 'light' ? 'dark' : 'light')}
                className="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              >
                {theme === 'light' ? '🌙' : '☀️'}
              </button>
              
              {/* Search */}
              <div className="relative">
                <input
                  type="text"
                  placeholder="Search companies..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white font-normal font-sans focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span className="text-gray-400">🔍</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white dark:bg-slate-800 rounded-xl shadow-xl overflow-hidden border-2 border-neutral-300 dark:border-transparent">
          {/* Mobile scroll indicator */}
          <div className="md:hidden bg-blue-50 dark:bg-blue-900/20 px-4 py-2 text-center">
            <div className="text-xs text-blue-600 dark:text-blue-400 font-medium">
              ← Swipe left/right to see more columns →
            </div>
          </div>
          
          <div className="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-gray-100 dark:scrollbar-track-gray-800 touch-pan-x">
            <table className="min-w-[1200px] divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 border-b-2 border-blue-200 dark:border-transparent">
                <tr>
                  <th 
                    className="px-4 py-4 text-center text-xs font-bold text-blue-900 dark:text-white uppercase tracking-wider cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-colors font-sans w-[200px] min-w-[200px]"
                    onClick={() => handleSort('symbol')}
                  >
                    <div className="flex items-center justify-center space-x-1">
                      <span>Company</span>
                      <SortIcon field="symbol" />
                    </div>
                  </th>
                  
                  <th 
                    className="px-4 py-4 text-center text-xs font-bold text-blue-900 dark:text-white uppercase tracking-wider cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-colors font-sans bg-blue-100 dark:bg-blue-800/50 w-[150px] min-w-[150px]"
                    onClick={() => handleSort('marketCap')}
                  >
                    <div className="flex items-center justify-center space-x-1">
                      <span>Market Cap</span>
                      <SortIcon field="marketCap" />
                    </div>
                  </th>
                  
                  <th 
                    className="px-4 py-4 text-center text-xs font-bold text-blue-900 dark:text-white uppercase tracking-wider cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-colors font-sans w-[120px] min-w-[120px]"
                    onClick={() => handleSort('price')}
                  >
                    <div className="flex items-center justify-center space-x-1">
                      <span>Price</span>
                      <SortIcon field="price" />
                    </div>
                  </th>
                  
                  <th 
                    className="px-4 py-4 text-center text-xs font-bold text-blue-900 dark:text-white uppercase tracking-wider cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-colors font-sans w-[180px] min-w-[180px]"
                    onClick={() => handleSort('epsSurp')}
                  >
                    <div className="flex items-center justify-center space-x-1">
                      <span>EPS</span>
                      <SortIcon field="epsSurp" />
                    </div>
                  </th>
                  
                  <th 
                    className="px-4 py-4 text-center text-xs font-bold text-blue-900 dark:text-white uppercase tracking-wider cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-colors font-sans w-[180px] min-w-[180px]"
                    onClick={() => handleSort('revSurp')}
                  >
                    <div className="flex items-center justify-center space-x-1">
                      <span>Revenue</span>
                      <SortIcon field="revSurp" />
                    </div>
                  </th>
                </tr>
              </thead>
              
              <tbody className="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                {filteredAndSortedData.map((item, index) => (
                  <tr 
                    key={item.symbol}
                    className="hover:bg-neutral-50 dark:hover:bg-slate-700/50 transition-colors duration-150"
                  >
                    {/* Company */}
                    <td className="px-4 py-4 whitespace-nowrap w-[200px] min-w-[200px]">
                      <div className="flex items-center space-x-3">
                        <CompanyLogo 
                          symbol={item.symbol} 
                          logoUrl={item.logoUrl} 
                          name={item.name} 
                        />
                        <div className="min-w-0 flex-1">
                          <div className="text-sm font-bold text-neutral-900 dark:text-white font-sans truncate">
                            {item.symbol}
                          </div>
                          <div className="text-xs text-neutral-600 dark:text-neutral-400 font-medium font-sans truncate">
                            {item.name}
                          </div>
                        </div>
                      </div>
                    </td>
                    
                    {/* Market Cap */}
                    <td className="px-4 py-4 whitespace-nowrap text-center w-[150px] min-w-[150px]">
                      <div>
                        <div className="text-sm font-bold text-neutral-900 dark:text-white font-sans">
                          {formatMarketCap(item.marketCap)}
                        </div>
                        <div className={`text-sm font-medium font-sans ${getChangeColor(item.marketCapDiff)}`}>
                          {item.marketCapDiff ? `${item.marketCapDiff > 0 ? '+' : ''}${formatMarketCap(item.marketCapDiff)}` : '-'}
                        </div>
                      </div>
                    </td>
                    
                    {/* Price */}
                    <td className="px-4 py-4 whitespace-nowrap text-center w-[120px] min-w-[120px]">
                      <div>
                        <div className="text-sm font-bold text-neutral-900 dark:text-white font-sans">
                          {item.price ? `$${item.price.toFixed(2)}` : '-'}
                        </div>
                        <div className={`text-sm font-medium font-sans ${getChangeColor(item.change)}`}>
                          {item.change ? `${item.change > 0 ? '+' : ''}${item.change.toFixed(2)}%` : '-'}
                        </div>
                      </div>
                    </td>
                    
                    {/* EPS */}
                    <td className="px-4 py-4 whitespace-nowrap text-center w-[180px] min-w-[180px]">
                      <div className="space-y-0.5 text-xs font-sans">
                        <div className="text-sm font-bold text-neutral-900 dark:text-white">
                          <span className="text-xs text-neutral-500 dark:text-neutral-400 mr-1">Act.</span>{item.epsActual ? `$${item.epsActual.toFixed(2)}` : '-'}
                        </div>
                        <div className="text-xs text-neutral-600 dark:text-neutral-400 font-medium">
                          <span className="text-xs text-neutral-500 dark:text-neutral-400 mr-1">Est.</span>{item.epsEst ? `$${item.epsEst.toFixed(2)}` : '-'}
                        </div>
                        <div className={`text-sm font-semibold ${getChangeColor(item.epsSurp)}`}>
                          <span className="text-xs text-neutral-500 dark:text-neutral-400 mr-1">Surp.</span>{item.epsSurp ? `${item.epsSurp > 0 ? '+' : ''}${item.epsSurp.toFixed(2)}%` : '-'}
                        </div>
                      </div>
                    </td>
                    
                    {/* Revenue */}
                    <td className="px-4 py-4 whitespace-nowrap text-center w-[180px] min-w-[180px]">
                      <div className="space-y-0.5 text-xs font-sans">
                        <div className="text-sm font-bold text-neutral-900 dark:text-white">
                          <span className="text-xs text-neutral-500 dark:text-neutral-400 mr-1">Act.</span>{item.revActual ? formatRevenue(item.revActual) : '-'}
                        </div>
                        <div className="text-xs text-neutral-600 dark:text-neutral-400 font-medium">
                          <span className="text-xs text-neutral-500 dark:text-neutral-400 mr-1">Est.</span>{item.revEst ? formatRevenue(item.revEst) : '-'}
                        </div>
                        <div className={`text-sm font-semibold ${getChangeColor(item.revSurp)}`}>
                          <span className="text-xs text-neutral-500 dark:text-neutral-400 mr-1">Surp.</span>{item.revSurp ? `${item.revSurp > 0 ? '+' : ''}${item.revSurp.toFixed(2)}%` : '-'}
                        </div>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          
          {filteredAndSortedData.length === 0 && (
            <div className="text-center py-12">
              <div className="text-neutral-400 text-lg font-medium font-sans">No companies found</div>
              <div className="text-neutral-500 text-sm font-normal font-sans mt-2">
                Try adjusting your search terms
              </div>
            </div>
          )}
        </div>
        
        {/* Footer */}
        <div className="mt-8 text-center text-sm font-normal text-neutral-500 dark:text-neutral-400 font-sans">
          Showing {filteredAndSortedData.length} of {data.length} companies
        </div>
      </div>
    </div>
  );
};

export default EarningsTable;
