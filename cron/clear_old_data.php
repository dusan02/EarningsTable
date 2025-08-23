<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Usage: php cron/clear_old_data.php [--force]

$lock = new Lock('daily_cleanup');
if (!$lock->acquire()) {
	echo "❌ Another cleanup process is running\n";
	exit(1);
}
register_shutdown_function(fn() => $lock->release());

$force = in_array('--force', $argv ?? [], true);

try {
	$tzNy = new DateTimeZone('America/New_York');
	$nowNy = new DateTime('now', $tzNy);
	$nyDate = $nowNy->format('Y-m-d');
	$nyHour = (int)$nowNy->format('G'); // 0-23

	echo "🧹 DAILY CLEANUP INIT\n";
	echo "📅 NY Date: {$nyDate}, Hour: {$nyHour}\n";

	// Last-run guard so we run once per NY date
	$stateDir = __DIR__ . '/../storage';
	$stateFile = $stateDir . '/daily_cleanup_last_run.txt';
	if (!is_dir($stateDir)) {
		mkdir($stateDir, 0777, true);
	}
	$lastRun = file_exists($stateFile) ? trim((string)file_get_contents($stateFile)) : '';

	if (!$force) {
		if ($nyHour !== 2) {
			echo "⏭️  Skipping: not 02:00 NY time (run hour={$nyHour}). Use --force to override.\n";
			exit(0);
		}
		if ($lastRun === $nyDate) {
			echo "⏭️  Skipping: already ran today ({$lastRun}). Use --force to override.\n";
			exit(0);
		}
	}

	echo "🚀 START CLEANUP\n";

	// Counts before
	$beforeMov = (int)$pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
	$beforeEtt = (int)$pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();
	echo "Before → TodayEarningsMovements: {$beforeMov}, EarningsTickersToday: {$beforeEtt}\n";

	// 1) Clear movements table (always per-day)
	$pdo->exec("DELETE FROM TodayEarningsMovements");
	$afterMov = (int)$pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
	echo "🗑️  Cleared TodayEarningsMovements → remaining: {$afterMov}\n";

	// 2) Remove old earnings tickers: keep only <today removed>, leave today as is
	$delOld = $pdo->prepare("DELETE FROM EarningsTickersToday WHERE report_date < ? OR report_date > ?");
	$delOld->execute([$nyDate, $nyDate]);
	$afterEtt = (int)$pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();
	echo "🗂️  Pruned EarningsTickersToday → remaining (all dates): {$afterEtt}\n";

	// Persist last-run state
	file_put_contents($stateFile, $nyDate);
	echo "✅ Cleanup complete for NY date {$nyDate}\n";

} catch (Throwable $e) {
	echo "❌ ERROR: {$e->getMessage()}\n";
	exit(1);
}
