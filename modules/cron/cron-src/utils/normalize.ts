type WithDates = { 
  reportDate?: any; 
  snapshotDate?: any; 
  createdAt?: any; 
  updatedAt?: any;
  [key: string]: any;
};

export function normalizeFinalReportDates<T extends WithDates>(obj: T): T {
  const coerce = (v: any): Date => {
    if (v instanceof Date) return v;
    if (typeof v === "number") return new Date(v);
    if (typeof v === "string") return new Date(v);
    return new Date();
  };

  const out: any = { ...obj };
  
  if (out.reportDate != null) out.reportDate = coerce(out.reportDate);
  if (out.snapshotDate != null) out.snapshotDate = coerce(out.snapshotDate);
  if (out.createdAt != null) out.createdAt = coerce(out.createdAt);
  if (out.updatedAt != null) out.updatedAt = coerce(out.updatedAt);
  
  return out as T;
}
