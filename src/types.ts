export interface FinalReportData {
  symbol: string;
  name: string | null;
  size: string | null;
  marketCap: number | string | null;
  marketCapDiff: number | string | null;
  price: number | null;
  change: number | null;
  epsActual: number | null;
  epsEst: number | null;
  epsSurp: number | null;
  revActual: number | string | null;
  revEst: number | string | null;
  revSurp: number | null;
  logoUrl: string | null;
  logoSource: string | null;
  logoFetchedAt: string | null;
  reportDate: string | null;
  snapshotDate: string | null;
  createdAt: string | null;
  updatedAt: string | null;
}

export interface DateInfo {
  date: string;
  count: number;
}

export type SortField = keyof FinalReportData;
export type SortDirection = 'asc' | 'desc';
export type Theme = 'light' | 'dark';
