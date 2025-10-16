// Spoločné typy pre celú aplikáciu

export type FinnhubEarning = {
  symbol: string;
  date: string;           // "2025-10-13"
  hour?: string | null;   // "bmo"/"amc"/"dmh"
  epsActual?: number | null;
  epsEstimate?: number | null;
  revenueActual?: number | null;
  revenueEstimate?: number | null;
  quarter?: number | null;
  year?: number | null;
};

export type FinhubData = {
  id: number;
  reportDate: Date;
  symbol: string;
  epsActual?: number | null;
  epsEstimate?: number | null;
  revenueActual?: bigint | null;
  revenueEstimate?: bigint | null;
  hour?: string | null;
  quarter?: number | null;
  year?: number | null;
  createdAt: Date;
  updatedAt: Date;
};

export type CreateFinhubData = {
  reportDate: Date;
  symbol: string;
  epsActual?: number | null;
  epsEstimate?: number | null;
  revenueActual?: number | null;
  revenueEstimate?: number | null;
  hour?: string | null;
  quarter?: number | null;
  year?: number | null;
};

export type PolygonData = {
  id: number;
  symbol: string;
  symbolBoolean?: boolean | null;
  marketCap?: bigint | null;
  previousMarketCap?: bigint | null;
  marketCapDiff?: bigint | null;
  marketCapBoolean?: boolean | null;
  price?: number | null;
  previousClose?: number | null;
  change?: number | null;
  size?: string | null;  // Mega, Large, Mid, Small, null
  name?: string | null;  // Company name from Polygon API
  priceBoolean?: boolean | null;
  Boolean?: boolean | null;
  createdAt: Date;
  updatedAt: Date;
};

export type CreatePolygonData = {
  symbol: string;
  symbolBoolean?: boolean | null;
  marketCap?: bigint | null;
  previousMarketCap?: bigint | null;
  marketCapDiff?: bigint | null;
  marketCapBoolean?: boolean | null;
  price?: number | null;
  previousClose?: number | null;
  change?: number | null;
  size?: string | null;  // Mega, Large, Mid, Small, null
  name?: string | null;  // Company name from Polygon API
  priceBoolean?: boolean | null;
  Boolean?: boolean | null;
};

export type FinalReport = {
  id: number;
  symbol: string;
  name?: string | null;  // From PolygonData
  size?: string | null;  // From PolygonData
  marketCap?: bigint | null;  // From PolygonData
  marketCapDiff?: bigint | null;  // From PolygonData
  price?: number | null;  // From PolygonData
  change?: number | null;  // From PolygonData
  epsActual?: number | null;  // From FinhubData
  epsEst?: number | null;  // From FinhubData (epsEstimate)
  epsSurp?: number | null;  // Calculated: ((epsActual/epsEstimate) * 100) - 100
  revActual?: bigint | null;  // From FinhubData (revenueActual)
  revEst?: bigint | null;  // From FinhubData (revenueEstimate)
  revSurp?: number | null;  // Calculated: ((revActual/revEstimate) * 100) - 100
  createdAt: Date;
  updatedAt: Date;
};

export type CreateFinalReport = {
  symbol: string;
  name?: string | null;  // From PolygonData
  size?: string | null;  // From PolygonData
  marketCap?: bigint | null;  // From PolygonData
  marketCapDiff?: bigint | null;  // From PolygonData
  price?: number | null;  // From PolygonData
  change?: number | null;  // From PolygonData
  epsActual?: number | null;  // From FinhubData
  epsEst?: number | null;  // From FinhubData (epsEstimate)
  epsSurp?: number | null;  // Calculated: ((epsActual/epsEstimate) * 100) - 100
  revActual?: bigint | null;  // From FinhubData (revenueActual)
  revEst?: bigint | null;  // From FinhubData (revenueEstimate)
  revSurp?: number | null;  // Calculated: ((revActual/revEstimate) * 100) - 100
};

export type ApiResponse<T> = {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
};

export type CronJobConfig = {
  name: string;
  schedule: string;
  timezone: string;
  enabled: boolean;
};