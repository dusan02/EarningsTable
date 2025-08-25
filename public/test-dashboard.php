<?php
require_once __DIR__ . '/../config.php';

// Set headers
header('Content-Type: text/html; charset=utf-8');

try {
    // Use US Eastern Time to match the cron jobs
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    // Get today's tickers from EarningsTickersToday
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $todayTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($todayTickers)) {
        $earnings = [];
    } else {
        // Get market cap data for today's tickers from TodayEarningsMovements
        $placeholders = str_repeat('?,', count($todayTickers) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT 
                t.ticker,
                t.company_name,
                t.current_price,
                t.previous_close,
                t.market_cap,
                t.size,
                t.market_cap_diff,
                t.market_cap_diff_billions,
                t.price_change_percent,
                t.shares_outstanding,
                t.eps_estimate,
                t.eps_actual,
                t.revenue_estimate,
                t.revenue_actual,
                t.eps_surprise_percent,
                t.revenue_surprise_percent,
                t.updated_at,
                e.report_time
            FROM TodayEarningsMovements t
            LEFT JOIN EarningsTickersToday e ON t.ticker = e.ticker AND e.report_date = ?
            WHERE t.ticker IN ($placeholders)
            ORDER BY t.market_cap_diff_billions DESC, t.ticker
        ");
        $stmt->execute(array_merge([$date], $todayTickers));
        $earnings = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings Table - Test Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Earnings Table - Test Dashboard</h1>
    <p><strong>Date:</strong> <?php echo $date; ?></p>
    <p><strong>Total Records:</strong> <?php echo count($earnings); ?></p>
    
    <?php if (isset($error)): ?>
        <p class="error">Error: <?php echo $error; ?></p>
    <?php else: ?>
        <h2>Earnings Data</h2>
        <?php if (empty($earnings)): ?>
            <p>No earnings data found for today.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Ticker</th>
                        <th>Company</th>
                        <th>Current Price</th>
                        <th>Previous Close</th>
                        <th>Price Change %</th>
                        <th>Market Cap</th>
                        <th>Size</th>
                        <th>Report Time</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($earnings as $earning): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($earning['ticker']); ?></td>
                            <td><?php echo htmlspecialchars($earning['company_name']); ?></td>
                            <td>$<?php echo number_format($earning['current_price'], 2); ?></td>
                            <td>$<?php echo number_format($earning['previous_close'], 2); ?></td>
                            <td><?php echo number_format($earning['price_change_percent'], 2); ?>%</td>
                            <td><?php echo $earning['market_cap'] ? '$' . number_format($earning['market_cap'] / 1000000000, 1) . 'B' : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($earning['size']); ?></td>
                            <td><?php echo htmlspecialchars($earning['report_time']); ?></td>
                            <td><?php echo htmlspecialchars($earning['updated_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
    
    <h2>API Test</h2>
    <p><a href="api/earnings-tickers-today.php" target="_blank">View API Response</a></p>
    
    <h2>Original Dashboard</h2>
    <p><a href="dashboard-fixed.html" target="_blank">Open Original Dashboard</a></p>
</body>
</html>
