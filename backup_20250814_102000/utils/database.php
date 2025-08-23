<?php
/**
 * Database Utilities - Refactored
 * Centralized database operations for earnings table
 */

require_once __DIR__ . '/../config.php';

/**
 * Create earnings table with optimized structure
 */
function createEarningsTable($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS EarningsTickersToday (
        report_date DATE NOT NULL,
        ticker CHAR(10) NOT NULL,
        report_time ENUM('BMO','AMC','TNS') NOT NULL,
        eps_actual DECIMAL(10,2) NULL,
        eps_estimate DECIMAL(10,2) NULL,
        revenue_actual BIGINT NULL,
        revenue_estimate BIGINT NULL,
        PRIMARY KEY (report_date, ticker),
        INDEX idx_date_time (report_date, report_time),
        INDEX idx_eps_actual (eps_actual),
        INDEX idx_revenue_actual (revenue_actual)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    return $pdo->exec($sql);
}

/**
 * Get earnings count for date
 */
function getEarningsCount($pdo, $date) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    return $stmt->fetch()['total'];
}

/**
 * Get earnings data for date
 */
function getEarningsData($pdo, $date) {
    $stmt = $pdo->prepare("
        SELECT * FROM EarningsTickersToday 
        WHERE report_date = ? 
        ORDER BY report_time, ticker
    ");
    $stmt->execute([$date]);
    return $stmt->fetchAll();
}

/**
 * Get earnings data with market cap information
 */
function getEarningsDataWithMarketCap($pdo, $date) {
    $stmt = $pdo->prepare("
        SELECT 
            e.report_date,
            e.ticker,
            e.report_time,
            e.eps_actual,
            e.eps_estimate,
            e.revenue_actual,
            e.revenue_estimate,
            m.market_cap,
            m.size,
            m.current_price,
            m.price_change_percent,
            m.market_cap_diff,
            m.market_cap_diff_billions
        FROM EarningsTickersToday e
        LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
        WHERE e.report_date = ?
        ORDER BY CASE WHEN m.market_cap IS NULL THEN 0 ELSE 1 END DESC, m.market_cap_diff_billions DESC, e.report_time, e.ticker
    ");
    $stmt->execute([$date]);
    return $stmt->fetchAll();
}

/**
 * Check if ticker exists for date
 */
function tickerExists($pdo, $date, $ticker) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM EarningsTickersToday 
        WHERE report_date = ? AND ticker = ?
    ");
    $stmt->execute([$date, $ticker]);
    return $stmt->fetch()['count'] > 0;
}

/**
 * Get table structure
 */
function getTableStructure($pdo) {
    $stmt = $pdo->query("DESCRIBE EarningsTickersToday");
    return $stmt->fetchAll();
}
?> 