-- =====================================================
-- SHARES OUTSTANDING CACHE TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS SharesOutstanding (
    ticker CHAR(10) NOT NULL PRIMARY KEY,
    shares_outstanding BIGINT NOT NULL,
    fetched_on DATE NOT NULL,
    INDEX idx_fetched_on (fetched_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- CACHE TABLE SETUP COMPLETE
-- =====================================================

-- Add comment
ALTER TABLE SharesOutstanding COMMENT = 'Daily cache of shares outstanding data from Finnhub API';

-- =====================================================
-- Sample data (for testing)
-- =====================================================

-- INSERT INTO SharesOutstanding (ticker, shares_out, fetched_on) VALUES
-- ('PH', 127780000, CURDATE()),
-- ('LLY', 947740000, CURDATE()),
-- ('MSFT', 7432540000, CURDATE()),
-- ('AAPL', 14935830000, CURDATE()),
-- ('GILD', 1243930000, CURDATE()); 