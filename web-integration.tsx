import React, { useState, useEffect } from 'react';
import EarningsTable from './EarningsTable';

// Type definitions matching your FinalReport structure
interface FinalReportData {
  symbol: string;
  name: string | null;
  size: string | null;
  marketCap: bigint | null;
  marketCapDiff: bigint | null;
  price: number | null;
  change: number | null;
  epsActual: number | null;
  epsEst: number | null;
  epsSurp: number | null;
  revActual: bigint | null;
  revEst: bigint | null;
  revSurp: number | null;
}

// API service to fetch data from your backend
class EarningsApiService {
  private baseUrl: string;

  constructor(baseUrl: string = 'http://localhost:3000') {
    this.baseUrl = baseUrl;
  }

  async getFinalReport(): Promise<FinalReportData[]> {
    try {
      const response = await fetch(`${this.baseUrl}/api/final-report`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const data = await response.json();
      return this.transformData(data);
    } catch (error) {
      console.error('Error fetching final report:', error);
      throw error;
    }
  }

  // Transform database data to component format
  private transformData(dbData: any[]): FinalReportData[] {
    return dbData.map(item => ({
      symbol: item.symbol,
      name: item.name || 'N/A',
      size: item.size || 'Unknown',
      marketCap: item.marketCap ? Number(item.marketCap) : 0,
      marketCapDiff: item.marketCapDiff ? Number(item.marketCapDiff) : 0,
      price: item.price || 0,
      change: item.change || 0,
      epsActual: item.epsActual || 0,
      epsEst: item.epsEst || 0,
      epsSurp: item.epsSurp || 0,
      revActual: item.revActual ? Number(item.revActual) : 0,
      revEst: item.revEst ? Number(item.revEst) : 0,
      revSurp: item.revSurp || 0,
    }));
  }
}

// Main App component with data fetching
const EarningsApp: React.FC = () => {
  const [data, setData] = useState<FinalReportData[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

  const apiService = new EarningsApiService();

  const fetchData = async () => {
    try {
      setLoading(true);
      setError(null);
      const result = await apiService.getFinalReport();
      setData(result);
      setLastUpdated(new Date());
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
    
    // Auto-refresh every 5 minutes
    const interval = setInterval(fetchData, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  if (loading && data.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Loading earnings data...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
        <div className="text-center">
          <div className="text-red-500 text-6xl mb-4">⚠️</div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
            Error Loading Data
          </h2>
          <p className="text-gray-600 dark:text-gray-400 mb-4">{error}</p>
          <button
            onClick={fetchData}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="relative">
      {/* Refresh indicator */}
      {loading && (
        <div className="fixed top-4 right-4 z-50">
          <div className="bg-white dark:bg-gray-800 shadow-lg rounded-lg px-4 py-2 flex items-center space-x-2">
            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
            <span className="text-sm text-gray-600 dark:text-gray-400">Updating...</span>
          </div>
        </div>
      )}

      {/* Last updated info */}
      {lastUpdated && (
        <div className="fixed bottom-4 right-4 z-50">
          <div className="bg-white dark:bg-gray-800 shadow-lg rounded-lg px-3 py-2">
            <p className="text-xs text-gray-500 dark:text-gray-400">
              Last updated: {lastUpdated.toLocaleTimeString()}
            </p>
          </div>
        </div>
      )}

      <EarningsTable data={data} />
    </div>
  );
};

export default EarningsApp;
