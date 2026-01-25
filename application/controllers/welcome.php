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
        
        // Use parallel processing for better performance
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
            $index++;
        }
        
        // Execute all requests in parallel
        $responses = $this->_parallel_requests($parallel_requests);
        
        // Debug logging
        $this->logDebugInfo('Parallel responses received', $responses);
        
        // Process responses
        $index = 0;
        foreach ($servers as $name => $config) {
            // Get process list
            $list_response = $responses['list_' . $index] ?? null;
            if ($list_response && !isset($list_response['error'])) {
                $data['list'][$name] = $list_response;
            } else {
                // Fallback to individual request if parallel failed
                $fallback_list = $this->_request($name, 'getAllProcessInfo', [], false);
                $data['list'][$name] = $fallback_list;
                $this->logDebugInfo("Fallback request for $name", $fallback_list);
            }
            
            // Get version
            $version_response = $responses['version_' . $index] ?? null;
            if ($version_response && !isset($version_response['error'])) {
                $data['version'][$name] = $version_response;
            } else {
                // Fallback to individual request if parallel failed
                $fallback_version = $this->_request($name, 'getSupervisorVersion', [], false);
                $data['version'][$name] = $fallback_version;
            }
            
            $index++;
        }
        
        $data['cfg'] = $servers;
        $data['load_time'] = round((microtime(true) - $data['load_time_start']) * 1000, 2);
        $data['cache_stats'] = $this->getCacheStats();
        
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

