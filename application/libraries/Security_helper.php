<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Security Helper Library
 * Provides security validation and sanitization functions
 */
class Security_helper
{
    private $CI;
    private $config;
    
    public function __construct()
    {
        $this->CI =& get_instance();
        
        // Config should already be loaded by controller
        // No need to load again here
    }
    
    /**
     * Validate và sanitize queue name
     * Chỉ cho phép alphanumeric và underscore
     */
    public function validate_queue_name($queue_name)
    {
        // Loại bỏ khoảng trắng
        $queue_name = trim($queue_name);
        
        // Kiểm tra rỗng
        if (empty($queue_name)) {
            return ['valid' => false, 'error' => 'Queue name cannot be empty'];
        }
        
        // Kiểm tra độ dài
        if (strlen($queue_name) > 100) {
            return ['valid' => false, 'error' => 'Queue name too long'];
        }
        
        // Chỉ cho phép a-z, A-Z, 0-9, underscore
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $queue_name)) {
            return ['valid' => false, 'error' => 'Queue name contains invalid characters. Only alphanumeric and underscore allowed.'];
        }
        
        // Kiểm tra whitelist (nếu có whitelist được cấu hình)
        $allowed_queues = $this->CI->config->item('allowed_queue_names');
        $use_whitelist = $this->CI->config->item('use_queue_whitelist');
        
        // Chỉ check whitelist nếu được bật và có danh sách
        if ($use_whitelist && !empty($allowed_queues) && !in_array($queue_name, $allowed_queues)) {
            return ['valid' => false, 'error' => 'Queue name not in whitelist'];
        }
        
        return ['valid' => true, 'sanitized' => $queue_name];
    }
    
    /**
     * Validate server name
     */
    public function validate_server_name($server_name)
    {
        $server_name = trim($server_name);
        
        if (empty($server_name)) {
            return ['valid' => false, 'error' => 'Server name cannot be empty'];
        }
        
        // Chỉ cho phép a-z, A-Z, 0-9, underscore
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $server_name)) {
            return ['valid' => false, 'error' => 'Server name contains invalid characters'];
        }
        
        // Kiểm tra whitelist
        $allowed_servers = $this->CI->config->item('allowed_servers');
        if (!in_array($server_name, $allowed_servers)) {
            return ['valid' => false, 'error' => 'Server name not in whitelist'];
        }
        
        return ['valid' => true, 'sanitized' => $server_name];
    }
    
    /**
     * Validate process/worker name
     */
    public function validate_process_name($process_name)
    {
        $process_name = trim($process_name);
        
        if (empty($process_name)) {
            return ['valid' => false, 'error' => 'Process name cannot be empty'];
        }
        
        if (strlen($process_name) > 200) {
            return ['valid' => false, 'error' => 'Process name too long'];
        }
        
        // Cho phép: a-z, A-Z, 0-9, underscore, hyphen, colon (cho group:name format)
        if (!preg_match('/^[a-zA-Z0-9_:\-]+$/', $process_name)) {
            return ['valid' => false, 'error' => 'Process name contains invalid characters'];
        }
        
        return ['valid' => true, 'sanitized' => $process_name];
    }
    
    /**
     * Escape shell argument an toàn
     */
    public function escape_shell_arg($arg)
    {
        return escapeshellarg($arg);
    }
    
    /**
     * Kiểm tra IP có trong whitelist không
     */
    public function check_ip_whitelist()
    {
        $client_ip = $this->CI->input->ip_address();
        $whitelist = $this->CI->config->item('admin_ip_whitelist');
        
        if (empty($whitelist)) {
            return true; // Nếu không có whitelist thì cho phép tất cả
        }
        
        return in_array($client_ip, $whitelist);
    }
    
    /**
     * Log security event
     */
    public function log_security_event($event_type, $details = [])
    {
        if (!$this->CI->config->item('enable_audit_log')) {
            return;
        }
        
        $log_file = $this->CI->config->item('audit_log_path');
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $user = $this->CI->session->userdata('username') ?: 'guest';
        $ip = $this->CI->input->ip_address();
        $user_agent = $this->CI->input->user_agent();
        
        $log_entry = sprintf(
            "[%s] EVENT: %s | USER: %s | IP: %s | UA: %s | DETAILS: %s\n",
            $timestamp,
            $event_type,
            $user,
            $ip,
            $user_agent,
            json_encode($details)
        );
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Rate limiting check
     */
    public function check_rate_limit($action_type)
    {
        if (!$this->CI->config->item('rate_limit_enabled')) {
            return ['allowed' => true];
        }
        
        $session_key = 'rate_limit_' . $action_type;
        $actions = $this->CI->session->userdata($session_key) ?: [];
        $now = time();
        
        // Loại bỏ các action cũ hơn 1 giờ
        $actions = array_filter($actions, function($timestamp) use ($now) {
            return ($now - $timestamp) < 3600;
        });
        
        // Kiểm tra limit per minute
        $last_minute = array_filter($actions, function($timestamp) use ($now) {
            return ($now - $timestamp) < 60;
        });
        
        $max_per_minute = $this->CI->config->item('max_actions_per_minute');
        $max_per_hour = $this->CI->config->item('max_actions_per_hour');
        
        if (count($last_minute) >= $max_per_minute) {
            return ['allowed' => false, 'error' => 'Rate limit exceeded: too many actions per minute'];
        }
        
        if (count($actions) >= $max_per_hour) {
            return ['allowed' => false, 'error' => 'Rate limit exceeded: too many actions per hour'];
        }
        
        // Thêm action hiện tại
        $actions[] = $now;
        $this->CI->session->set_userdata($session_key, $actions);
        
        return ['allowed' => true];
    }
}
