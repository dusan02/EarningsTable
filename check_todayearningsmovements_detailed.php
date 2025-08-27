<?php
require_once 'config.php';

echo "=== DETAILED TODAYEARNINGSMOVEMENTS CHECK ===\n";

// Check current time
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');
$time = $usDate->format('H:i:s');

echo "Current NY time: {$date} {$time}\n\n";

// Check today's records with details
$stmt = $pdo->query("
    SELECT 
        ticker, 
        company_name, 
        current_price, 
        market_cap, 
        size,
        updated_at,
        DATE(updated_at) as update_date,
        TIME(updated_at) as update_time
    FROM todayearningsmovements 
    WHERE DATE(updated_at) = CURDATE()
    ORDER BY updated_at DESC
    LIMIT 10
");

echo "=== TOP 10 RECORDS FROM TODAY ===\n";
$count = 0;
while ($row = $stmt->fetch()) {
    $count++;
    $marketCapB = $row['market_cap'] ? round($row['market_cap'] / 1000000000, 2) : 'N/A';
    echo "{$count}. {$row['ticker']}: \${$marketCapB}B ({$row['size']}) - {$row['company_name']}\n";
    echo "    Price: {$row['current_price']}, Updated: {$row['update_date']} {$row['update_time']}\n";
}

echo "\n=== TOTAL RECORDS ANALYSIS ===\n";

// Count total records
$stmt = $pdo->query("SELECT COUNT(*) as total FROM todayearningsmovements WHERE DATE(updated_at) = CURDATE()");
$total = $stmt->fetch()['total'];

// Count by size
$stmt = $pdo->query("
    SELECT size, COUNT(*) as count 
    FROM todayearningsmovements 
    WHERE DATE(updated_at) = CURDATE() 
    GROUP BY size 
    ORDER BY count DESC
");

echo "Total records today: {$total}\n";
echo "Size breakdown:\n";
while ($row = $stmt->fetch()) {
    echo "  {$row['size']}: {$row['count']} tickers\n";
}

// Check if we have market cap data
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as with_mc,
        COUNT(CASE WHEN market_cap > 0 THEN 1 END) as valid_mc
    FROM todayearningsmovements 
    WHERE DATE(updated_at) = CURDATE()
");
$mcData = $stmt->fetch();

echo "\n=== MARKET CAP ANALYSIS ===\n";
echo "Records with market cap field: {$mcData['with_mc']}\n";
echo "Records with valid market cap (>0): {$mcData['valid_mc']}\n";

// Check most recent update
$stmt = $pdo->query("
    SELECT MAX(updated_at) as latest_update 
    FROM todayearningsmovements 
    WHERE DATE(updated_at) = CURDATE()
");
$latest = $stmt->fetch()['latest_update'];

echo "\n=== TIMING ANALYSIS ===\n";
echo "Latest update: {$latest}\n";

if ($latest) {
    $latestTime = new DateTime($latest);
    $currentTime = new DateTime('now', $timezone);
    $diff = $currentTime->diff($latestTime);
    
    echo "Time since last update: {$diff->format('%H:%I:%S')}\n";
    
    if ($diff->i < 5) {
        echo "✅ Data is fresh (updated within 5 minutes)\n";
    } else {
        echo "⚠️  Data might be stale (updated more than 5 minutes ago)\n";
    }
}

echo "\n=== DATABASE CONNECTION INFO ===\n";
echo "Database: " . DB_NAME . "\n";
echo "Host: " . DB_HOST . "\n";
echo "User: " . DB_USER . "\n";
?>
