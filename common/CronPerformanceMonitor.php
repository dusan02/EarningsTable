<?php
/**
 * 📊 CRON PERFORMANCE MONITOR
 * 
 * Monitoruje výkon všetkých cron jobov:
 * - Execution time tracking
 * - Memory usage monitoring
 * - API call performance
 * - Database operation metrics
 * - Resource utilization
 * - Performance alerts
 */

class CronPerformanceMonitor {
    
    private $startTime;
    private $memoryStart;
    private $metrics = [];
    private $alerts = [];
    private $thresholds;
    
    public function __construct() {
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage(true);
        $this->thresholds = $this->getDefaultThresholds();
    }
    
    /**
     * Začne monitoring pre konkrétny cron job
     */
    public function startMonitoring($cronName) {
        $this->metrics[$cronName] = [
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage(true),
            'api_calls' => 0,
            'db_operations' => 0,
            'errors' => 0,
            'warnings' => 0
        ];
        
        echo "📊 Performance monitoring started for: {$cronName}\n";
    }
    
    /**
     * Zaznamená API volanie
     */
    public function recordApiCall($cronName, $endpoint, $duration, $success = true) {
        if (!isset($this->metrics[$cronName])) {
            $this->startMonitoring($cronName);
        }
        
        $this->metrics[$cronName]['api_calls']++;
        
        if (!$success) {
            $this->metrics[$cronName]['errors']++;
        }
        
        // Check if API call is too slow
        if ($duration > $this->thresholds['api_call_slow']) {
            $this->addAlert($cronName, "API call to {$endpoint} took {$duration}s (threshold: {$this->thresholds['api_call_slow']}s)");
        }
    }
    
    /**
     * Zaznamená databázovú operáciu
     */
    public function recordDbOperation($cronName, $operation, $duration, $success = true) {
        if (!isset($this->metrics[$cronName])) {
            $this->startMonitoring($cronName);
        }
        
        $this->metrics[$cronName]['db_operations']++;
        
        if (!$success) {
            $this->metrics[$cronName]['errors']++;
        }
        
        // Check if DB operation is too slow
        if ($duration > $this->thresholds['db_operation_slow']) {
            $this->addAlert($cronName, "DB operation {$operation} took {$duration}s (threshold: {$this->thresholds['db_operation_slow']}s)");
        }
    }
    
    /**
     * Zaznamená warning
     */
    public function recordWarning($cronName, $message) {
        if (!isset($this->metrics[$cronName])) {
            $this->startMonitoring($cronName);
        }
        
        $this->metrics[$cronName]['warnings']++;
        $this->addAlert($cronName, "WARNING: {$message}");
    }
    
    /**
     * Zaznamená error
     */
    public function recordError($cronName, $message) {
        if (!isset($this->metrics[$cronName])) {
            $this->startMonitoring($cronName);
        }
        
        $this->metrics[$cronName]['errors']++;
        $this->addAlert($cronName, "ERROR: {$message}");
    }
    
    /**
     * Ukončí monitoring pre konkrétny cron job
     */
    public function stopMonitoring($cronName) {
        if (!isset($this->metrics[$cronName])) {
            return null;
        }
        
        $endTime = microtime(true);
        $memoryEnd = memory_get_usage(true);
        
        $executionTime = round($endTime - $this->metrics[$cronName]['start_time'], 2);
        $memoryUsed = $memoryEnd - $this->metrics[$cronName]['memory_start'];
        $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);
        
        $this->metrics[$cronName]['end_time'] = $endTime;
        $this->metrics[$cronName]['execution_time'] = $executionTime;
        $this->metrics[$cronName]['memory_used'] = $memoryUsed;
        $this->metrics[$cronName]['memory_used_mb'] = $memoryUsedMB;
        
        // Performance analysis
        $this->analyzePerformance($cronName, $executionTime, $memoryUsedMB);
        
        return $this->metrics[$cronName];
    }
    
    /**
     * Analýza výkonu
     */
    private function analyzePerformance($cronName, $executionTime, $memoryUsedMB) {
        $thresholds = $this->thresholds;
        
        echo "📊 Performance Summary for {$cronName}:\n";
        echo "  ⏱️  Execution time: {$executionTime}s";
        
        if ($executionTime > $thresholds['execution_slow']) {
            echo " ⚠️  SLOW (threshold: {$thresholds['execution_slow']}s)";
        } elseif ($executionTime < $thresholds['execution_fast']) {
            echo " 🚀 FAST (threshold: {$thresholds['execution_fast']}s)";
        } else {
            echo " ✅ NORMAL";
        }
        echo "\n";
        
        echo "  💾 Memory used: {$memoryUsedMB}MB";
        if ($memoryUsedMB > $thresholds['memory_high']) {
            echo " ⚠️  HIGH (threshold: {$thresholds['memory_high']}MB)";
        } else {
            echo " ✅ NORMAL";
        }
        echo "\n";
        
        $metrics = $this->metrics[$cronName];
        echo "  📡 API calls: {$metrics['api_calls']}\n";
        echo "  🗄️  DB operations: {$metrics['db_operations']}\n";
        echo "  ❌ Errors: {$metrics['errors']}\n";
        echo "  ⚠️  Warnings: {$metrics['warnings']}\n";
    }
    
    /**
     * Pridá alert
     */
    private function addAlert($cronName, $message) {
        $this->alerts[] = [
            'cron' => $cronName,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Získa všetky metriky
     */
    public function getAllMetrics() {
        return $this->metrics;
    }
    
    /**
     * Získa všetky alerty
     */
    public function getAllAlerts() {
        return $this->alerts;
    }
    
    /**
     * Generuje performance report
     */
    public function generateReport() {
        if (empty($this->metrics)) {
            return "No performance data available";
        }
        
        $report = "📊 CRON PERFORMANCE REPORT\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $totalExecutionTime = 0;
        $totalMemoryUsed = 0;
        $totalApiCalls = 0;
        $totalDbOperations = 0;
        $totalErrors = 0;
        
        foreach ($this->metrics as $cronName => $metric) {
            if (isset($metric['execution_time'])) {
                $totalExecutionTime += $metric['execution_time'];
                $totalMemoryUsed += $metric['memory_used_mb'];
                $totalApiCalls += $metric['api_calls'];
                $totalDbOperations += $metric['db_operations'];
                $totalErrors += $metric['errors'];
            }
        }
        
        $report .= "=== SUMMARY ===\n";
        $report .= "Total execution time: " . round($totalExecutionTime, 2) . "s\n";
        $report .= "Total memory used: " . round($totalMemoryUsed, 2) . "MB\n";
        $report .= "Total API calls: {$totalApiCalls}\n";
        $report .= "Total DB operations: {$totalDbOperations}\n";
        $report .= "Total errors: {$totalErrors}\n\n";
        
        if (!empty($this->alerts)) {
            $report .= "=== ALERTS ===\n";
            foreach ($this->alerts as $alert) {
                $report .= "[{$alert['timestamp']}] {$alert['cron']}: {$alert['message']}\n";
            }
        }
        
        return $report;
    }
    
    /**
     * Získa defaultné thresholdy
     */
    private function getDefaultThresholds() {
        return [
            'execution_slow' => 60,      // 60s
            'execution_fast' => 10,      // 10s
            'memory_high' => 512,        // 512MB
            'api_call_slow' => 5,        // 5s
            'db_operation_slow' => 2     // 2s
        ];
    }
}
?>
