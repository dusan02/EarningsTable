import React from 'react';
import { FinalReportData } from '../types';
import { formatMarketCap, formatRevenue, formatSignedMarketCap } from '../utils';
import CompanyCell from './CompanyCell';
import MetricCell from './MetricCell';

/**
 * Compact earnings card for mobile view.
 * Uses a 2-column grid for metrics with reduced padding for small screens.
 */
const MobileCard: React.FC<{ item: FinalReportData }> = ({ item }) => (
  <div className="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-neutral-200 dark:border-slate-800 p-3 mb-2">
    {/* Company header row */}
    <div className="flex items-center gap-2.5 mb-2.5">
      <CompanyCell item={item} nameMaxWidth="160px" />
    </div>
    {/* Metrics grid — 2 columns, compact */}
    <div className="grid grid-cols-2 gap-1.5">
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

export default MobileCard;
