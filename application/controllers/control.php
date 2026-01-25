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
     * Restart a specific process
     */
    public function restart($server, $worker)
    {
        // First stop the process
        $stop_result = $this->_request($server, 'stopProcess', [$worker, true], false);
        
        if (isset($stop_result['error'])) {
            $this->session->set_flashdata('error', "Failed to stop $worker for restart: " . $stop_result['error']);
            redirect(base_url());
            return;
        }
        
        // Wait for clean shutdown
        sleep(2);
        
        // Then start it
        $start_result = $this->_request($server, 'startProcess', [$worker, true], false);
        
        if (isset($start_result['error'])) {
            $this->session->set_flashdata('error', "Failed to start $worker after stop: " . $start_result['error']);
        } else {
            $this->session->set_flashdata('success', "Process $worker restarted successfully on $server");
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
