<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Control Controller
 * Handles start, stop, restart operations for supervisor processes
 */
class Control extends MY_Controller
{
    public $security_helper;
    private $is_ajax = false;
    
    public function __construct()
    {
        parent::__construct();
        $this->load->config('supervisor');
        
        // Load security config first
        $this->load->config('security');
        
        // Load security helper library
        $this->load->library('Security_helper');
        
        // Detect AJAX request
        $this->is_ajax = $this->input->is_ajax_request() || 
                         $this->input->get('ajax') == '1' ||
                         $this->input->post('ajax') == '1';
        
        // Prevent caching for control actions (important for Cloudflare/CDN)
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    /**
     * Helper to return response (JSON for AJAX, redirect for normal)
     */
    private function _respond($success, $message, $data = [])
    {
        if ($this->is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success,
                'message' => $message,
                'data' => $data
            ]);
            exit; // IMPORTANT: Stop execution to prevent HTML output
        } else {
            if ($success) {
                $this->session->set_flashdata('success', $message);
            } else {
                $this->session->set_flashdata('error', $message);
            }
            redirect('');
        }
    }

    /**
     * Start a specific process
     */
    public function start($server, $worker)
    {
        // Security: Validate inputs
        $server_validation = $this->security_helper->validate_server_name($server);
        $worker_validation = $this->security_helper->validate_process_name($worker);
        
        if (!$server_validation['valid']) {
            $this->_respond(false, 'Security: ' . $server_validation['error']);
            return;
        }
        
        if (!$worker_validation['valid']) {
            $this->_respond(false, 'Security: ' . $worker_validation['error']);
            return;
        }
        
        $this->security_helper->log_security_event('START_PROCESS', [
            'server' => $server,
            'worker' => $worker
        ]);
        
        $result = $this->_request($server, 'startProcess', [$worker, true], false);
        
        if (isset($result['error'])) {
            $this->_respond(false, "Failed to start $worker on $server: " . $result['error']);
        } else {
            $this->_respond(true, "Process $worker started successfully on $server");
        }
    }

    /**
     * Start all processes on a server using parallel operations
     */
    public function startall($server)
    {
        // Increase time limit for long operations
        set_time_limit(120);
        
        // Use parallel start operations for better performance
        $result = $this->startAllProcessesParallel($server);
        
        if (isset($result['error'])) {
            $this->_respond(false, $result['error']);
        } else {
            $this->_respond($result['success'], $result['message'], $result);
        }
    }

    /**
     * Stop a specific process
     */
    public function stop($server, $worker)
    {
        // Security: Validate inputs
        $server_validation = $this->security_helper->validate_server_name($server);
        $worker_validation = $this->security_helper->validate_process_name($worker);
        
        if (!$server_validation['valid']) {
            $this->_respond(false, 'Security: ' . $server_validation['error']);
            return;
        }
        
        if (!$worker_validation['valid']) {
            $this->_respond(false, 'Security: ' . $worker_validation['error']);
            return;
        }
        
        $this->security_helper->log_security_event('STOP_PROCESS', [
            'server' => $server,
            'worker' => $worker
        ]);
        
        $result = $this->_request($server, 'stopProcess', [$worker, true], false);
        
        if (isset($result['error'])) {
            $this->_respond(false, "Failed to stop $worker on $server: " . $result['error']);
        } else {
            $this->_respond(true, "Process $worker stopped successfully on $server");
        }
    }

    /**
     * Stop all processes on a server using parallel operations (much faster)
     */
    public function stopall($server)
    {
        // Increase time limit for long operations
        set_time_limit(120);
        
        // Use parallel stop operations for better performance
        // Instead of sequential stopAllProcesses (12s), use parallel stopProcess (2-3s)
        $result = $this->stopAllProcessesParallel($server);
        
        if (isset($result['error'])) {
            $this->_respond(false, $result['error']);
        } else {
            $this->_respond($result['success'], $result['message'], $result);
        }
    }

    /**
     * Restart a specific process with detailed monitoring and optimized polling
     */
    public function restart($server, $worker)
    {
        // Security: Validate inputs
        $server_validation = $this->security_helper->validate_server_name($server);
        $worker_validation = $this->security_helper->validate_process_name($worker);
        
        if (!$server_validation['valid']) {
            $this->_respond(false, 'Security: ' . $server_validation['error']);
            return;
        }
        
        if (!$worker_validation['valid']) {
            $this->_respond(false, 'Security: ' . $worker_validation['error']);
            return;
        }
        
        $this->security_helper->log_security_event('RESTART_PROCESS', [
            'server' => $server,
            'worker' => $worker
        ]);
        
        $log = []; // Debug log
        
        // Get initial state for reference
        $initial_info = $this->_request($server, 'getProcessInfo', [$worker], false);
        $initial_state = isset($initial_info['statename']) ? $initial_info['statename'] : 'UNKNOWN';
        $log[] = "Initial state: $initial_state";
        
        // Step 1: Stop the process
        $log[] = "Attempting to stop process...";
        $stop_result = $this->_request($server, 'stopProcess', [$worker, true], false);
        
        if (isset($stop_result['error'])) {
            $log[] = "Stop failed: " . $stop_result['error'];
            $this->_respond(false, "Failed to stop $worker for restart: " . $stop_result['error'] . " | Log: " . implode(' | ', $log));
            return;
        }
        $log[] = "Stop command sent successfully";
        
        // Step 2: Wait and verify stop with optimized polling (0.5s intervals)
        $stop_verified = false;
        $log[] = "Waiting for process to stop...";
        for ($i = 0; $i < 60; $i++) { // 60 * 0.5s = 30 seconds max
            usleep(500000); // 0.5 second delay (faster than 1s)
            $check_info = $this->_request($server, 'getProcessInfo', [$worker], false);
            $current_state = isset($check_info['statename']) ? $check_info['statename'] : 'UNKNOWN';
            
            // Only log state changes or every 10 checks (5s)
            if ($i % 10 == 0 || $current_state === 'STOPPED' || $current_state === 'EXITED') {
                $log[] = "Check $i: state=$current_state";
            }
            
            if ($current_state === 'STOPPED' || $current_state === 'EXITED') {
                $stop_verified = true;
                $log[] = "Process stopped successfully after " . ($i * 0.5) . "s";
                break;
            }
        }
        
        if (!$stop_verified) {
            $log[] = "WARNING: Process did not stop in time (30s timeout)";
            if (!$this->is_ajax) {
                $this->session->set_flashdata('warning', "Process $worker may not have stopped completely before restart attempt");
            }
        }
        
        // Wait a bit longer to ensure clean shutdown (reduced from 3s to 1s)
        usleep(1000000);
        $log[] = "Waited 1s for clean shutdown";
        
        // Step 3: Start the process
        $log[] = "Attempting to start process...";
        $start_result = $this->_request($server, 'startProcess', [$worker, true], false);
        
        if (isset($start_result['error'])) {
            $log[] = "Start failed: " . $start_result['error'];
            $this->_respond(false, "Failed to start $worker after stop. Process is now STOPPED. Error: " . $start_result['error'] . " | Log: " . implode(' | ', $log));
            return;
        }
        $log[] = "Start command sent successfully";
        
        // Step 4: Wait and verify start with optimized polling (0.5s intervals)
        $start_verified = false;
        $final_state = 'UNKNOWN';
        $log[] = "Waiting for process to start...";
        for ($i = 0; $i < 120; $i++) { // 120 * 0.5s = 60 seconds max
            usleep(500000); // 0.5 second delay (faster than 1s)
            $final_info = $this->_request($server, 'getProcessInfo', [$worker], false);
            $final_state = isset($final_info['statename']) ? $final_info['statename'] : 'UNKNOWN';
            
            // Only log state changes or every 10 checks (5s)
            if ($i % 10 == 0 || $final_state === 'RUNNING' || $final_state === 'BACKOFF' || $final_state === 'FATAL') {
                $log[] = "Start check $i: state=$final_state";
            }
            
            if ($final_state === 'RUNNING') {
                $start_verified = true;
                $log[] = "Process started and running successfully after " . ($i * 0.5) . "s";
                break;
            } elseif ($final_state === 'BACKOFF' || $final_state === 'FATAL') {
                // Process failed to start properly - exit early instead of waiting full timeout
                $log[] = "Process entered error state: $final_state (exiting early)";
                break;
            }
        }
        
        // Final result with detailed logging
        if ($start_verified) {
            $this->_respond(true, "Process $worker successfully restarted on $server (was: $initial_state → now: $final_state)");
        } else {
            $error_msg = "Restart failed: Process $worker stopped but did not start properly (final state: $final_state)";
            $error_msg .= " | Debug log: " . implode(' | ', $log);
            $this->_respond(false, $error_msg);
        }
    }

    /**
     * Restart all processes on a server using parallel operations
     * Much faster than sequential: 2-3s stop + 2-3s start = 4-6s total
     */
    public function restartall($server)
    {
        // Increase time limit for long operations
        set_time_limit(180);
        
        $log = [];
        $log[] = "Starting parallel restartall on server: $server";
        
        // Stop all processes in parallel (2-3s instead of 12s)
        $log[] = "Stopping all processes in parallel...";
        $stop_result = $this->stopAllProcessesParallel($server);
        
        if (isset($stop_result['error']) || !$stop_result['success']) {
            $log[] = "Error stopping processes: " . (isset($stop_result['error']) ? $stop_result['error'] : $stop_result['message']);
            $this->_respond(false, "Failed to stop processes for restart: " . (isset($stop_result['error']) ? $stop_result['error'] : $stop_result['message']));
            return;
        }
        
        $log[] = "Stop completed in " . $stop_result['elapsed_time'] . "s (" . $stop_result['stopped_count'] . " processes)";
        
        // Wait a bit for clean shutdown
        $log[] = "Waiting 2s for clean shutdown...";
        usleep(2000000);
        
        // Start all processes in parallel (2-3s)
        $log[] = "Starting all processes in parallel...";
        $start_result = $this->startAllProcessesParallel($server);
        
        if (isset($start_result['error']) || !$start_result['success']) {
            $log[] = "Error starting processes: " . (isset($start_result['error']) ? $start_result['error'] : $start_result['message']);
            $this->security_helper->log_security_event('RESTARTALL_PARTIAL', [
                'server' => $server,
                'stopped' => $stop_result['stopped_count'],
                'start_error' => isset($start_result['error']) ? $start_result['error'] : $start_result['message']
            ]);
            $this->_respond(false, "Failed to start processes after stop. " . (isset($start_result['error']) ? $start_result['error'] : $start_result['message']));
            return;
        }
        
        $log[] = "Start completed in " . $start_result['elapsed_time'] . "s (" . $start_result['started_count'] . " processes)";
        
        $total_time = $stop_result['elapsed_time'] + $start_result['elapsed_time'] + 2;  // +2s for wait
        $log[] = "Total time: " . round($total_time, 2) . "s";
        
        $this->security_helper->log_security_event('RESTARTALL_SUCCESS', [
            'server' => $server,
            'stopped_count' => $stop_result['stopped_count'],
            'started_count' => $start_result['started_count'],
            'total_time' => round($total_time, 2)
        ]);
        
        $this->_respond(true, "All processes restarted successfully in parallel (Stop: " . $stop_result['elapsed_time'] . "s + Start: " . $start_result['elapsed_time'] . "s = " . round($total_time, 2) . "s total)", ['stopped_count' => $stop_result['stopped_count'], 'started_count' => $start_result['started_count'], 'total_time' => round($total_time, 2)]);
    }

    /**
     * Clear process logs
     */
    public function clear($server, $worker)
    {
        $result = $this->_request($server, 'clearProcessLogs', [$worker], false);
        
        if (isset($result['error'])) {
            $this->_respond(false, "Failed to clear logs for $worker: " . $result['error']);
        } else {
            $this->_respond(true, "Logs cleared for process $worker on $server");
        }
    }
    
    /**
     * Kill a specific process using remote purge script
     */
    public function kill($server, $worker)
    {
        $log = [];
        $log[] = "Kill request for: $worker on $server";
        
        // Get server configuration
        $servers = $this->config->item('supervisor_servers');
        if (!isset($servers[$server])) {
            $this->session->set_flashdata('error', "Server $server not found");
            redirect('');
            return;
        }
        
        $server_config = $servers[$server];
        
        // Parse worker name - if it's group:name format, extract just the name
        $process_name = $worker;
        if (strpos($worker, ':') !== false) {
            $parts = explode(':', $worker);
            $process_name = $parts[1];
        }
        
        $log[] = "Process name: $process_name";
        
        // Execute remote purge script via SSH
        $url = parse_url($server_config['url']);
        $host = $url['host'];
        
        // Build SSH command with auto-accept host key
        $ssh_options = "-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null";
        $ssh_command = "ssh {$ssh_options} root@{$host} 'sh /data/code/omisell-backend/deploy/purge_remote.sh {$process_name}'";
        $log[] = "SSH command: $ssh_command";
        
        // Execute command
        $output = [];
        $return_var = 0;
        exec($ssh_command . " 2>&1", $output, $return_var);
        
        $log[] = "Return code: $return_var";
        $log[] = "Output: " . implode("\n", $output);
        
        if ($return_var === 0) {
            $this->session->set_flashdata('success', "Process $worker killed successfully on $server. Output: " . implode(", ", $output));
        } else {
            $error_msg = "Failed to kill $worker on $server. ";
            $error_msg .= "Return code: $return_var. ";
            $error_msg .= "Output: " . implode(", ", $output);
            $error_msg .= " | Log: " . implode(' | ', $log);
            $this->session->set_flashdata('error', $error_msg);
        }
        
        redirect('');
    }

    /**
     * Get process info (AJAX endpoint)
     */
    public function info($server, $worker)
    {
        header('Content-Type: application/json');
        
        $result = $this->_request($server, 'getProcessInfo', [$worker], false);
        echo json_encode($result);
    }

    /**
     * Get process logs (AJAX endpoint)
     */
    public function logs($server, $worker, $type = 'stdout', $offset = 0, $length = 1000)
    {
        header('Content-Type: application/json');
        
        if ($type === 'stderr') {
            $result = $this->_request($server, 'readProcessStderrLog', [$worker, $offset, $length], false);
        } else {
            $result = $this->_request($server, 'readProcessStdoutLog', [$worker, $offset, $length], false);
        }
        
        echo json_encode($result);
    }
}

/* End of file control.php */
/* Location: ./application/controllers/control.php */
