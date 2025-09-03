<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';
require_once __DIR__ . '/../common/error_handler.php';

// Usage: php cron/clear_old_data.php [--force]

$lock = new Lock('daily_cleanup');
if (!$lock->acquire()) {
	displayError("Another cleanup process is running");
	exit(1);
}
register_shutdown_function(fn() => $lock->release());

$force = in_array('--force', $argv ?? [], true);

try {
	$tzNy = new DateTimeZone('America/New_York');
	$nowNy = new DateTime('now', $tzNy);
	$nyDate = $nowNy->format('Y-m-d');
	$nyHour = (int)$nowNy->format('G'); // 0-23

	displayInfo("DAILY CLEANUP INIT");
	displayInfo("NY Date: {$nyDate}, Hour: {$nyHour}");

	// Last-run guard so we run once per NY date
	$stateDir = __DIR__ . '/../storage';
	$stateFile = $stateDir . '/daily_cleanup_last_run.txt';
	if (!is_dir($stateDir)) {
		mkdir($stateDir, 0777, true);
	}
	$lastRun = file_exists($stateFile) ? trim((string)file_get_contents($stateFile)) : '';

	if (!$force) {
		if ($nyHour !== 2) {
			displayInfo("Skipping: not 02:00 NY time (run hour={$nyHour}). Use --force to override.");
			exit(0);
		}
		if ($lastRun === $nyDate) {
			displayInfo("Skipping: already ran today ({$lastRun}). Use --force to override.");
			exit(0);
		}
	}

	displayInfo("START CLEANUP");

	// Counts before
	$beforeMov = (int)$pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
	$beforeEtt = (int)$pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();
	$beforeBg = (int)$pdo->query("SELECT COUNT(*) FROM benzinga_guidance")->fetchColumn();
	$beforeEc = (int)$pdo->query("SELECT COUNT(*) FROM estimates_consensus")->fetchColumn();
	echo "Before → TodayEarningsMovements: {$beforeMov}, EarningsTickersToday: {$beforeEtt}, benzinga_guidance: {$beforeBg}, estimates_consensus: {$beforeEc}\n";

	// 1) Clear movements table (always per-day)
	$pdo->exec("DELETE FROM TodayEarningsMovements");
	$afterMov = (int)$pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
	echo "🗑️  Cleared TodayEarningsMovements → remaining: {$afterMov}\n";

	// 2) Remove old earnings tickers: keep only <today removed>, leave today as is
	$delOld = $pdo->prepare("DELETE FROM EarningsTickersToday WHERE report_date < ? OR report_date > ?");
	$delOld->execute([$nyDate, $nyDate]);
	$afterEtt = (int)$pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();
	echo "🗂️  Pruned EarningsTickersToday → remaining (all dates): {$afterEtt}\n";

	// 3) Clean benzinga_guidance table: completely clear all guidance data (daily system)
	$pdo->exec("TRUNCATE TABLE benzinga_guidance");
	$afterBg = (int)$pdo->query("SELECT COUNT(*) FROM benzinga_guidance")->fetchColumn();
	echo "🧹 Completely cleared benzinga_guidance table → remaining: {$afterBg}\n";

	// 4) Clean old estimates_consensus data: keep only current fiscal year (daily system)
	$currentYear = (int)$nowNy->format('Y');
	$delOldEc = $pdo->prepare("DELETE FROM estimates_consensus WHERE fiscal_year < ?");
	$delOldEc->execute([$currentYear]);
	$afterEc = (int)$pdo->query("SELECT COUNT(*) FROM estimates_consensus")->fetchColumn();
	echo "📊 Cleaned estimates_consensus (older than current year) → remaining: {$afterEc}\n";

	// Persist last-run state
	file_put_contents($stateFile, $nyDate);
	displaySuccess("Cleanup complete for NY date {$nyDate}");

} catch (Throwable $e) {
	logCronError('clear_old_data', $e->getMessage(), [
		'file' => $e->getFile(),
		'line' => $e->getLine()
	]);
	displayError($e->getMessage());
	exit(1);
}
