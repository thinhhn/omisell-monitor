<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Cron Controller for background jobs and data updates
 */
class Cron extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        
        // Bypass login check for cron jobs
        $this->config->set_item('enable_login', false);
        
        // Set memory limit and execution time for background jobs
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300); // 5 minutes
    }

    /**
     * Update supervisor data in background
     * Usage: php index.php cron updateSupervisorData
     */
    public function updateSupervisorData()
    {
        if (!$this->isCliRequest()) {
            show_error('This method can only be called from CLI');
        }
        
        echo "Starting supervisor data update...\n";
        $start_time = microtime(true);
        
        $servers = $this->config->item('supervisor_servers');
        $success_count = 0;
        $error_count = 0;
        
        // Prepare parallel requests
        $parallel_requests = [];
        $index = 0;
        
        foreach ($servers as $name => $config) {
            $parallel_requests['list_' . $index] = [
                'server' => $name,
                'method' => 'getAllProcessInfo'
            ];
            $parallel_requests['version_' . $index] = [
                'server' => $name,
                'method' => 'getSupervisorVersion'
            ];
            $parallel_requests['state_' . $index] = [
                'server' => $name,
                'method' => 'getState'
            ];
            $index++;
        }
        
        echo "Executing " . count($parallel_requests) . " requests in parallel...\n";
        
        // Execute parallel requests
        $responses = $this->_parallel_requests($parallel_requests);
        
        // Process and store results
        $index = 0;
        foreach ($servers as $name => $config) {
            $list_key = 'list_' . $index;
            $version_key = 'version_' . $index;
            $state_key = 'state_' . $index;
            
            if (isset($responses[$list_key]) && !isset($responses[$list_key]['error'])) {
                $this->storePersistentData($name, 'process_list', $responses[$list_key], 300);
                $success_count++;
                echo "✓ $name: Process list updated\n";
            } else {
                $error_count++;
                echo "✗ $name: Failed to get process list\n";
            }
            
            if (isset($responses[$version_key]) && !isset($responses[$version_key]['error'])) {
                $this->storePersistentData($name, 'version', $responses[$version_key], 3600);
                echo "✓ $name: Version updated\n";
            }
            
            if (isset($responses[$state_key]) && !isset($responses[$state_key]['error'])) {
                $this->storePersistentData($name, 'state', $responses[$state_key], 60);
                echo "✓ $name: State updated\n";
            }
            
            $index++;
        }
        
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        echo "\nCompleted in {$execution_time}ms\n";
        echo "Success: $success_count, Errors: $error_count\n";
        
        // Clean old cache files
        $this->cleanupOldCache();
        
        // Log performance metrics
        $this->logPerformanceMetrics($execution_time, $success_count, $error_count);
    }
    
    /**
     * Health check for all servers
     */
    public function healthCheck()
    {
        if (!$this->isCliRequest()) {
            header('Content-Type: application/json');
        }
        
        $servers = $this->config->item('supervisor_servers');
        $health_data = [];
        
        foreach ($servers as $name => $config) {
            $start_time = microtime(true);
            $response = $this->_request($name, 'getState', [], false);
            $response_time = round((microtime(true) - $start_time) * 1000, 2);
            
            $health_data[$name] = [
                'status' => isset($response['error']) ? 'down' : 'up',
                'response_time' => $response_time,
                'last_check' => date('Y-m-d H:i:s'),
                'error' => isset($response['error']) ? $response['error'] : null
            ];
        }
        
        // Store health data
        $this->storePersistentData('system', 'health_check', $health_data, 120);
        
        if ($this->isCliRequest()) {
            echo json_encode($health_data, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo json_encode($health_data);
        }
    }
    
    /**
     * Generate performance report
     */
    public function performanceReport()
    {
        $cache_stats = $this->getCacheStatsDetailed();
        $health_data = $this->getPersistentData('system', 'health_check');
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'cache_stats' => $cache_stats,
            'server_health' => $health_data,
            'system_load' => sys_getloadavg(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        if ($this->isCliRequest()) {
            echo "=== PERFORMANCE REPORT ===\n";
            echo "Timestamp: " . $report['timestamp'] . "\n";
            echo "Cache Files: " . $cache_stats['total_files'] . "\n";
            echo "Cache Size: " . $cache_stats['total_size'] . " KB\n";
            echo "Memory Usage: " . round($report['memory_usage'] / 1024 / 1024, 2) . " MB\n";
            echo "Peak Memory: " . round($report['peak_memory'] / 1024 / 1024, 2) . " MB\n";
        } else {
            header('Content-Type: application/json');
            echo json_encode($report);
        }
    }
    
    /**
     * Store persistent data (longer cache)
     */
    private function storePersistentData($namespace, $key, $data, $ttl = 3600)
    {
        $cache_dir = APPPATH . 'cache/supervisor/persistent/';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        $cache_file = $cache_dir . $namespace . '_' . $key . '.data';
        $cache_data = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        file_put_contents($cache_file, serialize($cache_data));
    }
    
    /**
     * Get persistent data
     */
    private function getPersistentData($namespace, $key)
    {
        $cache_dir = APPPATH . 'cache/supervisor/persistent/';
        $cache_file = $cache_dir . $namespace . '_' . $key . '.data';
        
        if (file_exists($cache_file)) {
            $data = file_get_contents($cache_file);
            $cache_data = unserialize($data);
            
            if ($cache_data && $cache_data['expires'] > time()) {
                return $cache_data['data'];
            }
        }
        
        return null;
    }
    
    /**
     * Cleanup old cache files
     */
    private function cleanupOldCache()
    {
        $cache_dirs = [
            APPPATH . 'cache/supervisor/',
            APPPATH . 'cache/supervisor/persistent/'
        ];
        
        $deleted_count = 0;
        
        foreach ($cache_dirs as $cache_dir) {
            if (is_dir($cache_dir)) {
                $files = glob($cache_dir . '*');
                
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $age = time() - filemtime($file);
                        
                        // Delete files older than 24 hours
                        if ($age > 86400) {
                            unlink($file);
                            $deleted_count++;
                        }
                    }
                }
            }
        }
        
        if ($deleted_count > 0) {
            echo "Cleaned up $deleted_count old cache files\n";
        }
    }
    
    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics($execution_time, $success_count, $error_count)
    {
        $log_file = APPPATH . 'logs/supervisor_performance.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'execution_time' => $execution_time,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND);
    }
    
    /**
     * Get detailed cache statistics
     */
    private function getCacheStatsDetailed()
    {
        $cache_dirs = [
            'standard' => APPPATH . 'cache/supervisor/',
            'persistent' => APPPATH . 'cache/supervisor/persistent/'
        ];
        
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'by_type' => []
        ];
        
        foreach ($cache_dirs as $type => $cache_dir) {
            $type_stats = ['files' => 0, 'size' => 0];
            
            if (is_dir($cache_dir)) {
                $files = glob($cache_dir . '*');
                $type_stats['files'] = count($files);
                
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $size = filesize($file);
                        $type_stats['size'] += $size;
                        $stats['total_size'] += $size;
                    }
                }
            }
            
            $stats['by_type'][$type] = $type_stats;
            $stats['total_files'] += $type_stats['files'];
        }
        
        $stats['total_size'] = round($stats['total_size'] / 1024, 2); // KB
        
        return $stats;
    }
    
    /**
     * Check if request is from CLI
     */
    private function isCliRequest()
    {
        return (php_sapi_name() === 'cli');
    }
}

/* End of file cron.php */
/* Location: ./application/controllers/cron.php */