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
    public function __construct()
    {
        parent::__construct();
        $this->load->config('supervisor');
    }

    /**
     * Start a specific process
     */
    public function start($server, $worker)
    {
        $result = $this->_request($server, 'startProcess', [$worker, true], false);
        
        if (isset($result['error'])) {
            $this->session->set_flashdata('error', "Failed to start $worker on $server: " . $result['error']);
        } else {
            $this->session->set_flashdata('success', "Process $worker started successfully on $server");
        }
        
        redirect(base_url());
    }

    /**
     * Start all processes on a server
     */
    public function startall($server)
    {
        $result = $this->_request($server, 'startAllProcesses', [true], false);
        
        if (isset($result['error'])) {
            $this->session->set_flashdata('error', "Failed to start all processes on $server: " . $result['error']);
        } else {
            $this->session->set_flashdata('success', "All processes started successfully on server $server");
        }
        
        redirect(base_url());
    }

    /**
     * Stop a specific process
     */
    public function stop($server, $worker)
    {
        $result = $this->_request($server, 'stopProcess', [$worker, true], false);
        
        if (isset($result['error'])) {
            $this->session->set_flashdata('error', "Failed to stop $worker on $server: " . $result['error']);
        } else {
            $this->session->set_flashdata('success', "Process $worker stopped successfully on $server");
        }
        
        redirect(base_url());
    }

    /**
     * Stop all processes on a server
     */
    public function stopall($server)
    {
        $result = $this->_request($server, 'stopAllProcesses', [true], false);
        
        if (isset($result['error'])) {
            $this->session->set_flashdata('error', "Failed to stop all processes on $server: " . $result['error']);
        } else {
            $this->session->set_flashdata('success', "All processes stopped successfully on server $server");
        }
        
        redirect(base_url());
    }

    /**
     * Restart a specific process with detailed monitoring
     */
    public function restart($server, $worker)
    {
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
            $this->session->set_flashdata('error', "Failed to stop $worker for restart: " . $stop_result['error'] . " | Log: " . implode(' | ', $log));
            redirect(base_url());
            return;
        }
        $log[] = "Stop command sent successfully";
        
        // Step 2: Wait and verify stop
        $stop_verified = false;
        $log[] = "Waiting for process to stop...";
        for ($i = 0; $i < 15; $i++) { // Increased from 10 to 15
            sleep(1);
            $check_info = $this->_request($server, 'getProcessInfo', [$worker], false);
            $current_state = isset($check_info['statename']) ? $check_info['statename'] : 'UNKNOWN';
            $log[] = "Check $i: state=$current_state";
            
            if ($current_state === 'STOPPED' || $current_state === 'EXITED') {
                $stop_verified = true;
                $log[] = "Process stopped successfully";
                break;
            }
        }
        
        if (!$stop_verified) {
            $log[] = "WARNING: Process did not stop in time";
            $this->session->set_flashdata('warning', "Process $worker may not have stopped completely before restart attempt");
        }
        
        // Wait a bit longer to ensure clean shutdown
        sleep(2);
        $log[] = "Waited 2s for clean shutdown";
        
        // Step 3: Start the process
        $log[] = "Attempting to start process...";
        $start_result = $this->_request($server, 'startProcess', [$worker, true], false);
        
        if (isset($start_result['error'])) {
            $log[] = "Start failed: " . $start_result['error'];
            $this->session->set_flashdata('error', "Failed to start $worker after stop. Process is now STOPPED. Error: " . $start_result['error'] . " | Log: " . implode(' | ', $log));
            redirect(base_url());
            return;
        }
        $log[] = "Start command sent successfully";
        
        // Step 4: Wait and verify start
        $start_verified = false;
        $final_state = 'UNKNOWN';
        $log[] = "Waiting for process to start...";
        for ($i = 0; $i < 20; $i++) { // Increased from 10 to 20
            sleep(1);
            $final_info = $this->_request($server, 'getProcessInfo', [$worker], false);
            $final_state = isset($final_info['statename']) ? $final_info['statename'] : 'UNKNOWN';
            $log[] = "Start check $i: state=$final_state";
            
            if ($final_state === 'RUNNING') {
                $start_verified = true;
                $log[] = "Process started and running";
                break;
            } elseif ($final_state === 'STARTING') {
                // Keep waiting, it's starting
                $log[] = "Process is starting...";
            } elseif ($final_state === 'BACKOFF' || $final_state === 'FATAL') {
                // Process failed to start properly
                $log[] = "Process entered error state: $final_state";
                break;
            }
        }
        
        // Final result with detailed logging
        if ($start_verified) {
            $this->session->set_flashdata('success', "Process $worker successfully restarted on $server (was: $initial_state → now: $final_state)");
        } else {
            $error_msg = "Restart failed: Process $worker stopped but did not start properly (final state: $final_state)";
            $error_msg .= " | Debug log: " . implode(' | ', $log);
            $this->session->set_flashdata('error', $error_msg);
        }
        
        redirect(base_url());
    }

    /**
     * Restart all processes on a server
     */
    public function restartall($server)
    {
        // Stop all processes first
        $stop_result = $this->_request($server, 'stopAllProcesses', [true], false);
        
        if (isset($stop_result['error'])) {
            $this->session->set_flashdata('error', "Failed to stop processes for restart on $server: " . $stop_result['error']);
            redirect(base_url());
            return;
        }
        
        // Wait for clean shutdown
        sleep(2);
        
        // Start all processes
        $start_result = $this->_request($server, 'startAllProcesses', [true], false);
        
        if (isset($start_result['error'])) {
            $this->session->set_flashdata('error', "Failed to start processes after restart on $server: " . $start_result['error']);
        } else {
            $this->session->set_flashdata('success', "All processes restarted successfully on server $server");
        }
        
        redirect(base_url());
    }

    /**
     * Clear process logs
     */
    public function clear($server, $worker)
    {
        $result = $this->_request($server, 'clearProcessLogs', [$worker], false);
        
        if (isset($result['error'])) {
            $this->session->set_flashdata('error', "Failed to clear logs for $worker: " . $result['error']);
        } else {
            $this->session->set_flashdata('success', "Logs cleared for process $worker on $server");
        }
        
        redirect(base_url());
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
