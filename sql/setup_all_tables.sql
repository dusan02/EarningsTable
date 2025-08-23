-- =====================================================
-- COMPLETE DATABASE SETUP FOR EARNINGS TABLE MODULE
-- =====================================================

-- 1. EARNINGS TICKERS TODAY TABLE (Finnhub API)
-- ==============================================

CREATE TABLE IF NOT EXISTS EarningsTickersToday (
    report_date DATE NOT NULL,
    ticker CHAR(10) NOT NULL,
    report_time ENUM('BMO','AMC','TNS') NOT NULL,
    eps_actual DECIMAL(10,2) NULL,
    eps_estimate DECIMAL(10,2) NULL,
    revenue_actual BIGINT NULL,
    revenue_estimate BIGINT NULL,
    sector VARCHAR(100) NULL,
    UNIQUE KEY uniq_date_ticker (report_date, ticker),
    INDEX idx_date_time (report_date, report_time),
    INDEX idx_eps_actual (eps_actual),
    INDEX idx_revenue_actual (revenue_actual)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TODAY EARNINGS MOVEMENTS TABLE (Polygon API)
-- ================================================

CREATE TABLE IF NOT EXISTS TodayEarningsMovements (
    ticker CHAR(10) NOT NULL PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    current_price DECIMAL(10,2) NOT NULL,
    previous_close DECIMAL(10,2) NOT NULL,
    market_cap BIGINT NOT NULL,
    size ENUM('Large','Mid','Small') NOT NULL,
    market_cap_diff BIGINT NOT NULL,
    market_cap_diff_billions DECIMAL(10,2) NOT NULL,
    price_change_percent DECIMAL(8,4) NOT NULL,
    shares_outstanding BIGINT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_updated_at (updated_at),
    INDEX idx_market_cap_diff (market_cap_diff),
    INDEX idx_price_change_percent (price_change_percent),
    INDEX idx_size (size),
    INDEX idx_market_cap_diff_billions (market_cap_diff_billions)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SETUP COMPLETE
-- ===================================================== 