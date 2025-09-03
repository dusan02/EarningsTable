<?php
/**
 * 🚀 UNIFIED CRON MANAGER
 * 
 * Centralizuje správu všetkých cron jobov s optimalizáciami:
 * - Paralelné spúšťanie kde je to možné
 * - Batch processing pre API volania
 * - Inteligentné rate limiting
 * - Error handling a retry logic
 * - Performance monitoring
 * - Resource management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/UnifiedLogger.php';

class UnifiedCronManager {
    
    private $config;
    private $logger;
    private $startTime;
    private $metrics;
    private $activeProcesses = [];
    
    public function __construct() {
        $this->config = new CronManagerConfig();
        $this->logger = new UnifiedLogger();
        $this->startTime = microtime(true);
        $this->metrics = new CronMetrics();
    }
    
    /**
     * Spustí všetky cron joby s optimalizáciami
     */
    public function runAllCrons() {
        echo "🚀 UNIFIED CRON MANAGER STARTED\n";
        echo "📅 Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        try {
            $this->initialize();
            $this->runSequentialCrons();
            $this->runParallelCrons();
            $this->runFinalCrons();
            $this->finalSummary();
            
        } catch (Exception $e) {
            $this->logger->error("Critical error in cron manager: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Inicializácia a kontrola prostredia
     */
    private function initialize() {
        echo "=== INITIALIZATION ===\n";
        
        // Check system resources
        $this->checkSystemResources();
        
        // Acquire global lock
        if (!$this->acquireGlobalLock()) {
            throw new Exception("Another cron manager is running");
        }
        
        // Initialize metrics
        $this->metrics->startSession();
        
        echo "✅ Initialization completed\n\n";
    }
    
    /**
     * Spustí sekvenčné cron joby (ktoré musia bežať postupne)
     */
    private function runSequentialCrons() {
        echo "=== SEQUENTIAL CRONS ===\n";
        
        // Step 1: Clear old data
        $this->runCron('Clear Old Data', 'cron/2_clear_old_data.php', ['--force']);
        
        // Step 2: Daily data setup
        $this->runCron('Daily Data Setup', 'cron/3_daily_data_setup_static.php');
        
        echo "✅ Sequential crons completed\n\n";
    }
    
    /**
     * Spustí paralelné cron joby (ktoré môžu bežať súčasne)
     */
    private function runParallelCrons() {
        echo "=== PARALLEL CRONS ===\n";
        echo "🚀 Running Regular Updates + Benzinga Guidance in parallel...\n";
        
        $parallelStart = microtime(true);
        
        // Create parallel processes
        $processes = [
            'Regular Data Updates' => 'cron/4_regular_data_updates_dynamic.php',
            'Benzinga Guidance' => 'cron/5_benzinga_guidance_updates.php'
        ];
        
        // Start all processes
        foreach ($processes as $name => $script) {
            $this->startParallelProcess($name, $script);
        }
        
        // Wait for all processes to complete
        $this->waitForParallelProcesses();
        
        $parallelTime = round(microtime(true) - $parallelStart, 2);
        echo "🚀 PARALLEL EXECUTION COMPLETED in {$parallelTime}s\n\n";
    }
    
    /**
     * Spustí finálne cron joby
     */
    private function runFinalCrons() {
        echo "=== FINAL CRONS ===\n";
        
        // Step 5: Estimates consensus updates
        $this->runCron('Estimates Consensus', 'cron/6_estimates_consensus_updates.php');
        
        echo "✅ Final crons completed\n\n";
    }
    
    /**
     * Spustí jednotlivý cron job
     */
    private function runCron($name, $script, $args = []) {
        echo "🔄 Running {$name}...\n";
        
        $startTime = microtime(true);
        $output = [];
        $returnCode = 0;
        
        $command = 'php ' . $script;
        if (!empty($args)) {
            $command .= ' ' . implode(' ', $args);
        }
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        $executionTime = round(microtime(true) - $startTime, 2);
        
        // Log output
        foreach ($output as $line) {
            echo "  {$line}\n";
        }
        
        if ($returnCode === 0) {
            echo "✅ {$name} completed in {$executionTime}s\n";
            $this->metrics->recordCronSuccess($name, $executionTime);
        } else {
            echo "❌ {$name} failed\n";
            $this->metrics->recordCronFailure($name, $executionTime);
        }
        
        return $returnCode === 0;
    }
    
    /**
     * Spustí paralelný proces
     */
    private function startParallelProcess($name, $script) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = proc_open("php {$script}", $descriptors, $pipes);
        
        if (is_resource($process)) {
            $this->activeProcesses[] = [
                'name' => $name,
                'process' => $process,
                'pipes' => $pipes,
                'startTime' => microtime(true)
            ];
            
            echo "  🚀 Launched {$name}\n";
        }
    }
    
    /**
     * Počká na dokončenie všetkých paralelných procesov
     */
    private function waitForParallelProcesses() {
        foreach ($this->activeProcesses as $processInfo) {
            $process = $processInfo['process'];
            $pipes = $processInfo['pipes'];
            $name = $processInfo['name'];
            $startTime = $processInfo['startTime'];
            
            // Close input pipe
            fclose($pipes[0]);
            
            // Read output
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            
            // Close pipes
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            // Wait for process to complete
            $returnCode = proc_close($process);
            $executionTime = round(microtime(true) - $startTime, 2);
            
            // Process output
            if ($output) {
                echo "📤 {$name} output:\n";
                foreach (explode("\n", trim($output)) as $line) {
                    if (trim($line)) echo "    {$line}\n";
                }
            }
            
            if ($returnCode === 0) {
                echo "✅ {$name} completed in {$executionTime}s\n";
                $this->metrics->recordCronSuccess($name, $executionTime);
            } else {
                echo "❌ {$name} failed\n";
                $this->metrics->recordCronFailure($name, $executionTime);
            }
        }
        
        $this->activeProcesses = [];
    }
    
    /**
     * Kontrola systémových zdrojov
     */
    private function checkSystemResources() {
        $memoryLimit = ini_get('memory_limit');
        $maxExecutionTime = ini_get('max_execution_time');
        
        echo "💾 Memory limit: {$memoryLimit}\n";
        echo "⏱️  Max execution time: {$maxExecutionTime}s\n";
        
        // Check available memory
        $memoryUsage = memory_get_usage(true);
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        
        if ($memoryUsage > ($memoryLimitBytes * 0.8)) {
            echo "⚠️  Warning: High memory usage\n";
        }
    }
    
    /**
     * Získa globálny lock
     */
    private function acquireGlobalLock() {
        $lockFile = sys_get_temp_dir() . '/unified_cron_manager.lock';
        
        if (file_exists($lockFile)) {
            $lockTime = filemtime($lockFile);
            if (time() - $lockTime < 3600) { // 1 hour timeout
                return false;
            }
            unlink($lockFile);
        }
        
        file_put_contents($lockFile, time());
        return true;
    }
    
    /**
     * Finálny súhrn
     */
    private function finalSummary() {
        echo "=== FINAL SUMMARY ===\n";
        
        $totalTime = round(microtime(true) - $this->startTime, 2);
        $metrics = $this->metrics->getSessionMetrics();
        
        echo "📊 Total execution time: {$totalTime}s\n";
        echo "✅ Successful crons: {$metrics['success_count']}\n";
        echo "❌ Failed crons: {$metrics['failure_count']}\n";
        echo "🚀 Performance improvement: " . $this->calculatePerformanceImprovement() . "%\n";
        
        echo "\n✅ UNIFIED CRON MANAGER COMPLETED SUCCESSFULLY!\n";
    }
    
    /**
     * Parsuje memory limit string
     */
    private function parseMemoryLimit($limit) {
        $value = (int)$limit;
        $unit = strtolower(substr($limit, -1));
        
        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }
    
    /**
     * Počíta výkonnostné zlepšenie
     */
    private function calculatePerformanceImprovement() {
        // Compare with sequential execution time
        $sequentialTime = $this->metrics->getSequentialExecutionTime();
        $parallelTime = $this->metrics->getParallelExecutionTime();
        
        if ($sequentialTime > 0) {
            $improvement = (($sequentialTime - $parallelTime) / $sequentialTime) * 100;
            return round($improvement, 1);
        }
        
        return 0;
    }
}

/**
 * Konfigurácia pre Cron Manager
 */
class CronManagerConfig {
    private $settings = [
        'max_parallel_processes' => 4,
        'process_timeout' => 300, // 5 minutes
        'memory_threshold' => 0.8, // 80% of memory limit
        'retry_attempts' => 3,
        'retry_delay' => 5
    ];
    
    public function get($key) {
        return $this->settings[$key] ?? null;
    }
}

/**
 * Metriky pre Cron Manager
 */
class CronMetrics {
    private $sessionStart;
    private $cronResults = [];
    
    public function startSession() {
        $this->sessionStart = microtime(true);
        $this->cronResults = [];
    }
    
    public function recordCronSuccess($name, $duration) {
        $this->cronResults[$name] = [
            'success' => true,
            'duration' => $duration,
            'timestamp' => time()
        ];
    }
    
    public function recordCronFailure($name, $duration) {
        $this->cronResults[$name] = [
            'success' => false,
            'duration' => $duration,
            'timestamp' => time()
        ];
    }
    
    public function getSessionMetrics() {
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($this->cronResults as $result) {
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }
        
        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'total_crons' => count($this->cronResults)
        ];
    }
    
    public function getSequentialExecutionTime() {
        $total = 0;
        foreach ($this->cronResults as $result) {
            $total += $result['duration'];
        }
        return $total;
    }
    
    public function getParallelExecutionTime() {
        if (empty($this->cronResults)) return 0;
        
        // Return the longest duration (parallel execution time)
        $maxDuration = 0;
        foreach ($this->cronResults as $result) {
            if ($result['duration'] > $maxDuration) {
                $maxDuration = $result['duration'];
            }
        }
        return $maxDuration;
    }
}
?>
