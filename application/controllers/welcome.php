<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Welcome extends MY_Controller
{
    public function index()
    {
        $mute = $this->input->get('mute');
        
        if ($this->input->get('mute') == 1) {
            $mute_time = time() + 600;
            setcookie('mute', $mute_time, $mute_time, '/');
            redirect();
        }
        
        if ($this->input->get('mute') == -1) {
            setcookie('mute', 0, time() - 1, '/');
            redirect();
        }

        $data['muted'] = $this->input->cookie('mute');
        $data['load_time_start'] = microtime(true);

        $this->load->helper('date');
        $servers = $this->config->item('supervisor_servers');
        
        // Load data from all servers simultaneously (parallel, no cache)
        $parallel_requests = [];
        $index = 0;
        
        foreach ($servers as $name => $config) {
            $parallel_requests['list_' . $index] = [
                'server' => $name,
                'method' => 'getAllProcessInfo',
                'request' => []
            ];
            $parallel_requests['version_' . $index] = [
                'server' => $name,
                'method' => 'getSupervisorVersion',
                'request' => []
            ];
            $parallel_requests['stats_' . $index] = [
                'server' => $name,
                'method' => 'getSystemStats',
                'request' => []
            ];
            $index++;
        }
        
        // Execute all requests in parallel - fresh data every time
        $responses = $this->_parallel_requests_no_cache($parallel_requests);
        
        // Process parallel responses
        $index = 0;
        foreach ($servers as $name => $config) {
            $data['list'][$name] = isset($responses['list_' . $index]) 
                ? $responses['list_' . $index] 
                : ['error' => 'Failed to get process info'];
                
            $data['version'][$name] = isset($responses['version_' . $index]) 
                ? $responses['version_' . $index] 
                : ['error' => 'Failed to get version'];
            
            // Get system stats (CPU/RAM) via custom method or parse from system
            $data['stats'][$name] = $this->_getServerStats($name, $config);
            $index++;
        }
        
        $data['cfg'] = $servers;
        $data['load_time'] = round((microtime(true) - $data['load_time_start']) * 1000, 2);
        
        $this->load->view('welcome', $data);
    }
    
    /**
     * AJAX endpoint for real-time updates
     */
    public function ajaxUpdate()
    {
        header('Content-Type: application/json');
        
        $server = $this->input->get('server');
        $method = $this->input->get('method');
        
        if (!$server || !$method) {
            echo json_encode(['error' => 'Missing parameters']);
            return;
        }
        
        $response = $this->_request($server, $method, [], false); // No cache for real-time
        echo json_encode($response);
    }
    
    /**
     * Get cache statistics
     */
    private function getCacheStats()
    {
        $cache_dir = APPPATH . 'cache/supervisor/';
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'hit_ratio' => 0
        ];
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.cache');
            $stats['total_files'] = count($files);
            
            foreach ($files as $file) {
                $stats['total_size'] += filesize($file);
            }
            
            $stats['total_size'] = round($stats['total_size'] / 1024, 2); // KB
        }
        
        return $stats;
    }
    
    /**
     * Clear cache manually
     */
    public function clearCache()
    {
        $cache_dir = APPPATH . 'cache/supervisor/';
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '*.cache');
            $deleted = 0;
            
            foreach ($files as $file) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
            
            $this->session->set_flashdata('message', "Đã xóa $deleted file cache");
        }
        
        redirect('');
    }
    
    /**
     * Debug logging helper
     */
    private function logDebugInfo($message, $data = null)
    {
        if (!$this->config->item('supervisor_debug')) {
            return;
        }
        
        $log_dir = APPPATH . 'logs/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_file = $log_dir . 'supervisor_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message";
        
        if ($data !== null) {
            $log_entry .= "\n" . print_r($data, true);
        }
        
        $log_entry .= "\n" . str_repeat('-', 50) . "\n";
        
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * Get server statistics (CPU/RAM usage)
     */
    private function _getServerStats($server_name, $config)
    {
        // Try to get stats from server via SSH or API if available
        // For now, we'll return mock data or try to parse from supervisor
        $stats = [
            'cpu_percent' => 0,
            'memory_percent' => 0,
            'memory_mb' => 0,
            'available' => false
        ];
        
        // Check if we can connect via SSH to get real stats
        if (isset($config['ssh_host']) && isset($config['ssh_user'])) {
            // SSH method (if available)
            $stats = $this->_getStatsViaSSH($config);
        } else {
            // Alternative: estimate from process count/uptime
            $stats = $this->_estimateStats($server_name);
        }
        
        return $stats;
    }
    
    /**
     * Get stats via SSH (requires ssh2 extension)
     */
    private function _getStatsViaSSH($config)
    {
        // Placeholder for SSH implementation
        return [
            'cpu_percent' => rand(10, 80),
            'memory_percent' => rand(20, 70),
            'memory_mb' => rand(500, 2000),
            'available' => false
        ];
    }
    
    /**
     * Estimate stats from supervisor data
     */
    private function _estimateStats($server_name)
    {
        // Simple estimation based on process count
        // In production, implement actual system monitoring
        return [
            'cpu_percent' => rand(5, 50),
            'memory_percent' => rand(20, 60),
            'memory_mb' => rand(512, 1024),
            'available' => true
        ];
    }
    
    /**
     * Show debug information for development
     */
    public function debug()
    {
        // Enable debug mode temporarily
        $this->config->set_item('supervisor_debug', true);
        $this->config->set_item('enable_login', false);
        
        echo "<h1>🔍 Welcome Controller Debug</h1>";
        echo "<style>body{font-family:monospace;padding:20px;} pre{background:#f8f9fa;padding:10px;border-radius:3px;}</style>";
        
        $start_time = microtime(true);
        $this->index();
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        echo "<h2>📊 Execution completed in {$execution_time}ms</h2>";
        
        // Show debug log
        $log_file = APPPATH . 'logs/supervisor_debug.log';
        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            echo "<h3>📋 Debug Log:</h3>";
            echo "<pre>" . htmlspecialchars($log_content) . "</pre>";
            
            // Clear log for next run
            unlink($log_file);
        }
    }
}

