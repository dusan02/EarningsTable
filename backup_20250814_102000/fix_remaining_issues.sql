-- Fix remaining 0.00 prices and -100% changes
-- Use previous close as current price when current_price is 0
UPDATE TodayEarningsMovements 
SET current_price = previous_close, 
    price_change_percent = NULL 
WHERE current_price = 0 AND previous_close > 0;

-- Set price_change_percent to NULL when either price is 0
UPDATE TodayEarningsMovements 
SET price_change_percent = NULL 
WHERE previous_close = 0 OR current_price = 0;

-- Set market_cap to NULL when we don't have valid data
UPDATE TodayEarningsMovements 
SET market_cap = NULL 
WHERE (shares_outstanding IS NULL OR shares_outstanding <= 0) OR current_price = 0;

-- Show results after fix
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN current_price > 0 THEN 1 ELSE 0 END) as with_price,
    SUM(CASE WHEN market_cap > 0 THEN 1 ELSE 0 END) as with_mc,
    SUM(CASE WHEN price_change_percent IS NOT NULL THEN 1 ELSE 0 END) as with_change_pct
FROM TodayEarningsMovements;
