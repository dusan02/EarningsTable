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
        <img 
          src={logoUrl} 
          alt={`${symbol} logo`} 
          className="w-8 h-8 rounded-full object-contain bg-gray-100 dark:bg-gray-700"
          onError={(e) => {
            // Hide image on error and show fallback
            e.currentTarget.style.display = 'none';
            e.currentTarget.nextElementSibling?.classList.remove('hidden');
          }}
        />
      );
    }
    
    // Fallback: show initials in a circle
    const initials = symbol.substring(0, 2).toUpperCase();
    return (
      <div className="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 text-xs font-semibold font-sans">
        {initials}
      </div>
    );
  };

  return (
    <div className={`min-h-screen transition-colors duration-300 ${
      theme === 'dark' ? 'bg-gray-900 text-white' : 'bg-gray-50 text-gray-900'
    }`}>
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white font-sans">
                Earnings Report
              </h1>
              <p className="mt-2 text-base text-gray-600 dark:text-gray-400 font-normal font-sans">
                Real-time financial data and earnings surprises
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
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead className="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th 
                    className="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors font-sans"
                    onClick={() => handleSort('symbol')}
                  >
                    <div className="flex items-center space-x-1">
                      <span>Company</span>
                      <SortIcon field="symbol" />
                    </div>
                  </th>
                  
                  <th 
                    className="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors font-sans"
                    onClick={() => handleSort('size')}
                  >
                    <div className="flex items-center space-x-1">
                      <span>Size</span>
                      <SortIcon field="size" />
                    </div>
                  </th>
                  
                  <th 
                    className="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors font-sans"
                    onClick={() => handleSort('marketCap')}
                  >
                    <div className="flex items-center space-x-1">
                      <span>Market Cap</span>
                      <SortIcon field="marketCap" />
                    </div>
                  </th>
                  
                  <th 
                    className="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors font-sans"
                    onClick={() => handleSort('price')}
                  >
                    <div className="flex items-center space-x-1">
                      <span>Price</span>
                      <SortIcon field="price" />
                    </div>
                  </th>
                  
                  <th className="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider font-sans">
                    EPS
                  </th>
                  
                  <th className="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider font-sans">
                    Revenue
                  </th>
                </tr>
                
                  {/* Sub-header for EPS and Revenue */}
                <tr className="bg-gray-50 dark:bg-gray-700">
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th className="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 font-sans">
                    <div className="space-y-1">
                      <div>Actual</div>
                      <div>Estimate</div>
                      <div>Surprise</div>
                    </div>
                  </th>
                  <th className="px-6 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 font-sans">
                    <div className="space-y-1">
                      <div>Actual</div>
                      <div>Estimate</div>
                      <div>Surprise</div>
                    </div>
                  </th>
                </tr>
              </thead>
              
              <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                {filteredAndSortedData.map((item, index) => (
                  <tr 
                    key={item.symbol}
                    className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                  >
                    {/* Company */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center space-x-3">
                        <CompanyLogo 
                          symbol={item.symbol} 
                          logoUrl={item.logoUrl} 
                          name={item.name} 
                        />
                        <div>
                          <div className="text-sm font-semibold text-gray-900 dark:text-white font-sans">
                            {item.symbol}
                          </div>
                          <div className="text-sm font-normal text-gray-500 dark:text-gray-400 font-sans">
                            {item.name}
                          </div>
                        </div>
                      </div>
                    </td>
                    
                    {/* Size */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full font-sans ${getSizeColor(item.size)}`}>
                        {item.size}
                      </span>
                    </td>
                    
                    {/* Market Cap */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900 dark:text-white font-sans">
                          {formatMarketCap(item.marketCap)}
                        </div>
                        <div className={`text-sm font-normal font-sans ${getChangeColor(item.marketCapDiff)}`}>
                          {item.marketCapDiff > 0 ? '+' : ''}{formatMarketCap(item.marketCapDiff)}
                        </div>
                      </div>
                    </td>
                    
                    {/* Price */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900 dark:text-white font-sans">
                          ${item.price?.toFixed(2)}
                        </div>
                        <div className={`text-sm font-normal font-sans ${getChangeColor(item.change)}`}>
                          {item.change > 0 ? '+' : ''}{item.change?.toFixed(2)}%
                        </div>
                      </div>
                    </td>
                    
                    {/* EPS */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="space-y-1 text-sm font-sans">
                        <div className="text-gray-900 dark:text-white font-medium">
                          {item.epsActual?.toFixed(2) || 'N/A'}
                        </div>
                        <div className="text-gray-600 dark:text-gray-400 font-normal">
                          {item.epsEst?.toFixed(2) || 'N/A'}
                        </div>
                        <div className={`${getChangeColor(item.epsSurp)} font-medium`}>
                          {item.epsSurp ? `${item.epsSurp > 0 ? '+' : ''}${item.epsSurp.toFixed(2)}%` : 'N/A'}
                        </div>
                      </div>
                    </td>
                    
                    {/* Revenue */}
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="space-y-1 text-sm font-sans">
                        <div className="text-gray-900 dark:text-white font-medium">
                          {item.revActual ? formatRevenue(item.revActual) : 'N/A'}
                        </div>
                        <div className="text-gray-600 dark:text-gray-400 font-normal">
                          {item.revEst ? formatRevenue(item.revEst) : 'N/A'}
                        </div>
                        <div className={`${getChangeColor(item.revSurp)} font-medium`}>
                          {item.revSurp ? `${item.revSurp > 0 ? '+' : ''}${item.revSurp.toFixed(2)}%` : 'N/A'}
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
              <div className="text-gray-400 text-lg font-medium font-sans">No companies found</div>
              <div className="text-gray-500 text-sm font-normal font-sans mt-2">
                Try adjusting your search terms
              </div>
            </div>
          )}
        </div>
        
        {/* Footer */}
        <div className="mt-8 text-center text-sm font-normal text-gray-500 dark:text-gray-400 font-sans">
          Showing {filteredAndSortedData.length} of {data.length} companies
        </div>
      </div>
    </div>
  );
};

export default EarningsTable;
