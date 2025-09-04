<?php
/**
 * Cron Status API Endpoint
 * Returns information about the last successful cron run
 */

require_once __DIR__ . '/../../config.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Check if cron status file exists
    $statusFile = __DIR__ . '/../../storage/cron_status.json';
    
    $response = [
        'success' => true,
        'last_run' => null,
        'last_run_timestamp' => null,
        'last_run_human' => null,
        'status' => 'unknown',
        'next_check' => time() + 120 // Next check in 2 minutes
    ];
    
    if (file_exists($statusFile)) {
        $statusData = json_decode(file_get_contents($statusFile), true);
        
        if ($statusData && isset($statusData['last_successful_run'])) {
            $lastRun = $statusData['last_successful_run'];
            $lastRunTime = strtotime($lastRun);
            $currentTime = time();
            $timeDiff = $currentTime - $lastRunTime;
            
            $response['last_run'] = $lastRun;
            $response['last_run_timestamp'] = $lastRunTime;
            $response['last_run_human'] = $lastRun;
            $response['time_diff_seconds'] = $timeDiff;
            $response['time_diff_minutes'] = round($timeDiff / 60, 1);
            
            // Determine status based on time since last run
            if ($timeDiff < 300) { // Less than 5 minutes
                $response['status'] = 'fresh';
            } elseif ($timeDiff < 600) { // Less than 10 minutes
                $response['status'] = 'recent';
            } elseif ($timeDiff < 1800) { // Less than 30 minutes
                $response['status'] = 'stale';
            } else {
                $response['status'] = 'old';
            }
            
            // Add additional status info if available
            if (isset($statusData['total_records'])) {
                $response['total_records'] = $statusData['total_records'];
            }
            if (isset($statusData['execution_time'])) {
                $response['execution_time'] = $statusData['execution_time'];
            }
        }
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'status' => 'error'
    ], JSON_PRETTY_PRINT);
}
?>
