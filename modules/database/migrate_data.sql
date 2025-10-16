-- Presunie dáta z earnings_reports do finnhub_data
INSERT INTO finnhub_data (id, reportDate, symbol, epsActual, epsEstimate, revenueActual, revenueEstimate, hour, quarter, year, createdAt, updatedAt)
SELECT id, reportDate, symbol, epsActual, epsEstimate, revenueActual, revenueEstimate, hour, quarter, year, createdAt, updatedAt
FROM earnings_reports;

-- Vymaže pôvodnú tabuľku
DROP TABLE earnings_reports;
