<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Events extends MY_Controller
{
    public $security_helper;
    
    public function __construct()
    {
        parent::__construct();
        $this->load->config('supervisor');
        
        // Load security config first
        $this->load->config('security');
        
        // Load security helper library
        $this->load->library('Security_helper');
        
        // Prevent caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    /**
     * Event statistics dashboard
     */
    public function index()
    {
        $data['muted'] = $this->input->cookie('mute');
        $data['alert'] = false;
        
        $this->load->view('events_stats', $data);
    }
    
    /**
     * Alternative method name without underscore (for compatibility)
     */
    public function getStats()
    {
        $this->get_stats();
    }
    
    /**
     * AJAX: Get event stats (run locally on web server)
     */
    public function get_stats()
    {
        header('Content-Type: application/json');
        
        // Run stat script locally on this server
        $script_path = APPPATH . 'scripts/stat_remote.sh';
        $command = "sh {$script_path} 2>&1";
        
        // Check if script exists
        if (!file_exists($script_path)) {
            echo json_encode([
                'success' => false,
                'error' => 'Script not found: ' . $script_path
            ]);
            return;
        }
        
        // Execute command
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        $output_string = implode("\n", $output);
        
        // Try to parse the entire output as JSON (multi-line)
        $stats = json_decode($output_string, true);
        
        // If that doesn't work, try line by line
        if ($stats === null || !is_array($stats)) {
            foreach ($output as $line) {
                $decoded = json_decode($line, true);
                if ($decoded !== null && is_array($decoded)) {
                    $stats = $decoded;
                    break;
                }
            }
        }
        
        if ($stats !== null && is_array($stats)) {
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'total_events' => array_sum($stats)
            ]);
            exit;
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to parse stats or script not found',
                'raw_output' => $output_string,
                'return_code' => $return_var
            ]);
            exit;
        }
    }
    
    /**
     * Kill a specific process (run locally on web server)
     */
    public function kill($process_name)
    {
        // Security: Log attempt
        $this->security_helper->log_security_event('KILL_ATTEMPT', [
            'process_name' => $process_name,
            'user' => $this->session->userdata('username'),
            'ip' => $this->input->ip_address()
        ]);
        
        // Security: Check rate limit
        $rate_check = $this->security_helper->check_rate_limit('kill');
        if (!$rate_check['allowed']) {
            $this->session->set_flashdata('error', $rate_check['error']);
            redirect('events');
            return;
        }
        
        // Security: Validate and sanitize process name
        $validation = $this->security_helper->validate_queue_name($process_name);
        if (!$validation['valid']) {
            $this->security_helper->log_security_event('KILL_BLOCKED_INVALID_INPUT', [
                'process_name' => $process_name,
                'error' => $validation['error']
            ]);
            $this->session->set_flashdata('error', 'Security: ' . $validation['error']);
            redirect('events');
            return;
        }
        
        $sanitized_name = $validation['sanitized'];
        
        // Run purge script locally on this server
        $script_path = APPPATH . 'scripts/purge_remote.sh';
        
        // Check if script exists
        if (!file_exists($script_path)) {
            $this->session->set_flashdata('error', "Script not found: $script_path");
            redirect('events');
            return;
        }
        
        // Security: Use escapeshellarg to prevent command injection
        $escaped_name = $this->security_helper->escape_shell_arg($sanitized_name);
        $command = "sh {$script_path} {$escaped_name} 2>&1";
        
        // Execute command
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        // Log result
        if ($return_var === 0) {
            $this->security_helper->log_security_event('KILL_SUCCESS', [
                'process_name' => $sanitized_name,
                'output' => implode(", ", $output)
            ]);
            $this->session->set_flashdata('success', "Process $sanitized_name killed successfully. Output: " . implode(", ", $output));
        } else {
            $this->security_helper->log_security_event('KILL_FAILED', [
                'process_name' => $sanitized_name,
                'return_code' => $return_var,
                'output' => implode(", ", $output)
            ]);
            $this->session->set_flashdata('error', "Failed to kill $sanitized_name. Output: " . implode(", ", $output));
        }
        
        redirect('events');
    }
}
