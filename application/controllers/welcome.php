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
        
        // Process responses
        $index = 0;
        foreach ($servers as $name => $config) {
            $data['list'][$name] = isset($responses['list_' . $index]) 
                ? $responses['list_' . $index] 
                : ['error' => 'Failed to get process info'];
                
            $data['version'][$name] = isset($responses['version_' . $index]) 
                ? $responses['version_' . $index] 
                : ['error' => 'Failed to get version'];
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
        
        redirect(base_url());
    }
}

