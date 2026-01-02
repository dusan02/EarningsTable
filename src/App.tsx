import React from 'react';
import EarningsTable from './EarningsTable';

// Sample data matching your FinalReport structure
const sampleData = [
  {
    symbol: 'AAPL',
    name: 'Apple Inc.',
    size: 'Mega',
    marketCap: 2500000000000,
    marketCapDiff: 50000000000,
    price: 150.25,
    change: 2.15,
    epsActual: 1.52,
    epsEst: 1.43,
    epsSurp: 6.29,
    revActual: 89498000000,
    revEst: 88500000000,
    revSurp: 1.13,
    logoUrl: null,
    logoSource: null,
    logoFetchedAt: null
  },
  {
    symbol: 'MSFT',
    name: 'Microsoft Corporation',
    size: 'Mega',
    marketCap: 2200000000000,
    marketCapDiff: -30000000000,
    price: 295.80,
    change: -1.02,
    epsActual: 2.93,
    epsEst: 2.85,
    epsSurp: 2.81,
    revActual: 52857000000,
    revEst: 52500000000,
    revSurp: 0.68,
    logoUrl: null,
    logoSource: null,
    logoFetchedAt: null
  },
  {
    symbol: 'GOOGL',
    name: 'Alphabet Inc.',
    size: 'Mega',
    marketCap: 1800000000000,
    marketCapDiff: 25000000000,
    price: 135.45,
    change: 1.85,
    epsActual: 1.55,
    epsEst: 1.45,
    epsSurp: 6.90,
    revActual: 76093000000,
    revEst: 75000000000,
    revSurp: 1.46,
    logoUrl: null,
    logoSource: null,
    logoFetchedAt: null
  },
  {
    symbol: 'TSLA',
    name: 'Tesla, Inc.',
    size: 'Large',
    marketCap: 850000000000,
    marketCapDiff: 15000000000,
    price: 265.30,
    change: 1.80,
    epsActual: 0.85,
    epsEst: 0.73,
    epsSurp: 16.44,
    revActual: 23350000000,
    revEst: 22500000000,
    revSurp: 3.78,
    logoUrl: null,
    logoSource: null,
    logoFetchedAt: null
  },
  {
    symbol: 'NVDA',
    name: 'NVIDIA Corporation',
    size: 'Large',
    marketCap: 1200000000000,
    marketCapDiff: 80000000000,
    price: 485.20,
    change: 7.05,
    epsActual: 4.44,
    epsEst: 3.71,
    epsSurp: 19.68,
    revActual: 22103000000,
    revEst: 20000000000,
    revSurp: 10.52,
    logoUrl: null,
    logoSource: null,
    logoFetchedAt: null
  }
];

const App: React.FC = () => {
  return (
    <div className="App">
      <EarningsTable data={sampleData} />
    </div>
  );
};

export default App;
