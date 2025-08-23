-- Database Index Optimization
-- Remove redundant indexes and add composite indexes for better performance

-- Remove redundant indexes from TodayEarningsMovements
DROP INDEX IF EXISTS idx_price_change_percent ON TodayEarningsMovements;
DROP INDEX IF EXISTS idx_market_cap_diff ON TodayEarningsMovements;
DROP INDEX IF EXISTS idx_market_cap_diff_billions ON TodayEarningsMovements;

-- Add optimized composite indexes
CREATE INDEX idx_market_cap_size ON TodayEarningsMovements (market_cap DESC, size);
CREATE INDEX idx_price_change_size ON TodayEarningsMovements (price_change_percent DESC, size);
CREATE INDEX idx_updated_at_size ON TodayEarningsMovements (updated_at DESC, size);

-- Keep essential indexes
-- idx_updated_at - for cron monitoring
-- idx_size - for filtering by size
-- Primary key on ticker - for upserts

-- Optimize EarningsTickersToday indexes
-- Keep existing indexes as they are well designed
-- idx_date_time - for date/time filtering
-- idx_eps_actual - for EPS analysis
-- idx_revenue_actual - for revenue analysis 