<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('supervisor');
        $this->load->library('session');
        $this->load->helper('url');
        
        // Check login if login is enabled
        $this->checkLogin();
    }
    
    /**
     * Check if user is logged in (middleware)
     */
    private function checkLogin()
    {
        $enable_login = $this->config->item('enable_login');
        
        // Skip login check if login is disabled
        if (!$enable_login) {
            return;
        }
        
        // Skip login check for auth controller
        if ($this->router->class === 'auth') {
            return;
        }
        
        $logged_in = $this->session->userdata('logged_in');
        $login_time = $this->session->userdata('login_time');
        $timeout = $this->config->item('login_timeout');
        
        // Check if user is logged in and session is valid
        if (!$logged_in || !$login_time || (time() - $login_time) > $timeout) {
            // Clear invalid session
            $this->session->unset_userdata(['logged_in', 'username', 'login_time']);
            $this->session->set_flashdata(
                'error', 
                'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.'
            );
            redirect('auth');
        }
        
        // Update login time for session extension
        $this->session->set_userdata('login_time', time());
    }
    
    /**
     * Get current logged in username
     */
    protected function getUsername()
    {
        return $this->session->userdata('username');
    }
    
    /**
     * Check if current user has admin privileges
     */
    protected function isAdmin()
    {
        $username = $this->getUsername();
        return ($username === 'admin');
    }

    /**
     * Direct request without caching - always fresh data
     */
    public function _request($server, $method, $request = [], $use_cache = false)
    {
        // Always get fresh data - no cache

        $servers = $this->config->item('supervisor_servers');
        if (!isset($servers[$server])) {
            return ['error' => "Invalid server: " . $server];
        }
        
        $config = $servers[$server];
        $response = $this->executeRequest($server, $method, $request, $config);
        
        // No caching - always return fresh data
        return $response;
    }

    /**
     * Execute XML-RPC request with retry mechanism and better error handling
     */
    private function executeRequest($server, $method, $request, $config)
    {
        $max_retries = 2;
        $retry_count = 0;
        
        while ($retry_count <= $max_retries) {
            try {
                // Try cURL approach first (more reliable)
                $result = $this->executeRequestWithCurl($server, $method, $request, $config);
                if (!isset($result['error'])) {
                    return $result;
                }
                
                // Fallback to CodeIgniter's XML-RPC library
                $this->load->library('xmlrpc', [], $server . '_' . $retry_count);
                $xmlrpc_instance = $server . '_' . $retry_count;
                
                $this->{$xmlrpc_instance}->initialize();
                $this->{$xmlrpc_instance}->server($config['url'], $config['port']);
                $this->{$xmlrpc_instance}->method('supervisor.' . $method);
                $this->{$xmlrpc_instance}->timeout($this->config->item('timeout'));
                
                // Enable debugging for better error messages
                $this->{$xmlrpc_instance}->set_debug(TRUE);
                
                if (isset($config['username']) && isset($config['password'])) {
                    $this->{$xmlrpc_instance}->setCredentials($config['username'], $config['password']);
                }
                
                $this->{$xmlrpc_instance}->request($request);

                if (!$this->{$xmlrpc_instance}->send_request()) {
                    $error = $this->{$xmlrpc_instance}->display_error();
                    
                    // Log detailed error for debugging
                    $this->logXmlRpcError($server, $method, $error, $config);
                    
                    // If it's a temporary error, retry
                    if ($retry_count < $max_retries && $this->isTemporaryError($error)) {
                        $retry_count++;
                        usleep(200000); // Wait 200ms before retry
                        continue;
                    }
                    
                    return [
                        'error' => $this->formatXmlRpcError($error), 
                        'server' => $server,
                        'method' => $method
                    ];
                } else {
                    $response = $this->{$xmlrpc_instance}->display_response();
                    
                    // Clean up the instance
                    unset($this->{$xmlrpc_instance});
                    
                    return $response;
                }
            } catch (Exception $e) {
                $this->logXmlRpcError($server, $method, $e->getMessage(), $config);
                
                if ($retry_count < $max_retries) {
                    $retry_count++;
                    usleep(200000);
                    continue;
                }
                return [
                    'error' => 'Connection failed: ' . $e->getMessage(), 
                    'server' => $server,
                    'method' => $method
                ];
            }
        }
        
        return [
            'error' => 'Max retries exceeded', 
            'server' => $server,
            'method' => $method
        ];
    }
    
    /**
     * Execute XML-RPC request using cURL (more reliable)
     */
    private function executeRequestWithCurl($server, $method, $request, $config)
    {
        $url = $config['url'] . ':' . $config['port'] . '/RPC2';
        
        // Build proper XML-RPC request
        $xml_request = $this->buildXmlrpcRequest('supervisor.' . $method, $request);
        
        // Determine appropriate timeout based on method
        // Batch operations need much longer timeout as they block supervisor
        $timeout = $this->getTimeoutForMethod($method);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml_request,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml',
                'Content-Length: ' . strlen($xml_request),
                'User-Agent: SupervisorMonitor/1.0',
                'Accept: text/xml'
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if (isset($config['username']) && isset($config['password'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_info = curl_getinfo($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return ['error' => "cURL Error: $curl_error"];
        }
        
        if ($http_code !== 200) {
            return ['error' => "HTTP Error: $http_code - " . substr($response, 0, 200)];
        }
        
        if (empty($response)) {
            return ['error' => 'Empty response from server'];
        }
        
        // Parse XML-RPC response
        return $this->parseXmlrpcResponse($response);
    }
    
    /**
     * Get appropriate timeout for different supervisor methods
     * Batch operations need longer timeouts as they block supervisor
     */
    private function getTimeoutForMethod($method)
    {
        switch ($method) {
            // Batch operations - these block supervisor while executing
            case 'stopAllProcesses':
            case 'startAllProcesses':
                return 90; // 90 seconds for batch operations
            
            // Individual operations - faster
            case 'stopProcess':
            case 'startProcess':
            case 'restartProcess':
                return 30; // 30 seconds for individual operations
            
            // Information queries - very fast
            case 'getAllProcessInfo':
            case 'getProcessInfo':
            case 'getPID':
            case 'getState':
            case 'getSupervisorVersion':
                return 10; // 10 seconds for read operations
            
            // Default
            default:
                return $this->config->item('timeout') ?: 60;
        }
    }

    /**
     * Parallel requests to multiple servers with NO CACHE
     */
    public function _parallel_requests_no_cache($servers_methods)
    {
        $responses = [];
        $handles = [];
        
        // Create cURL multi handle
        $mh = curl_multi_init();
        
        foreach ($servers_methods as $key => $data) {
            $server = $data['server'];
            $method = $data['method'];
            $request = isset($data['request']) ? $data['request'] : [];
            
            // No cache check - always fetch fresh data
            $ch = $this->createCurlHandle($server, $method, $request);
            if ($ch) {
                $handles[$key] = $ch;
                curl_multi_add_handle($mh, $ch);
            }
        }
        
        // Execute all requests in parallel
        if (!empty($handles)) {
            $running = null;
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            } while ($running > 0);
            
            // Collect results
            foreach ($handles as $key => $ch) {
                $result = curl_multi_getcontent($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                
                if ($curl_error) {
                    $responses[$key] = [
                        'error' => 'cURL Error: ' . $curl_error, 
                        'server' => $servers_methods[$key]['server']
                    ];
                } elseif ($http_code == 200 && $result) {
                    $response = $this->parseXmlrpcResponse($result);
                    $responses[$key] = $response;
                    
                    // No caching - fresh data only
                } else {
                    $responses[$key] = [
                        'error' => "HTTP Error $http_code", 
                        'server' => $servers_methods[$key]['server']
                    ];
                }
                
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
        }
        
        curl_multi_close($mh);
        return $responses;
    }
    
    /**
     * Legacy parallel requests (with cache) - kept for compatibility
     */
    public function _parallel_requests($servers_methods)
    {
        // For now, redirect to no-cache version since cache is disabled
        return $this->_parallel_requests_no_cache($servers_methods);
    }

    /**
     * Create cURL handle for XML-RPC request
     */
    private function createCurlHandle($server, $method, $request)
    {
        $servers = $this->config->item('supervisor_servers');
        if (!isset($servers[$server])) {
            return false;
        }
        
        $config = $servers[$server];
        $url = $config['url'] . ':' . $config['port'] . '/RPC2';
        
        // Build XML-RPC request
        $xml_request = $this->buildXmlrpcRequest('supervisor.' . $method, $request);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml_request,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->item('timeout'),
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml',
                'Content-Length: ' . strlen($xml_request)
            ],
            CURLOPT_USERAGENT => 'SupervisorMonitor/1.0'
        ]);
        
        if (isset($config['username']) && isset($config['password'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
        }
        
        return $ch;
    }

    /**
     * Simple cache implementation using files
     */
    private function getCache($key)
    {
        $cache_dir = APPPATH . 'cache/supervisor/';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        $cache_file = $cache_dir . md5($key) . '.cache';
        
        if (file_exists($cache_file)) {
            $data = file_get_contents($cache_file);
            $cache_data = unserialize($data);
            
            if ($cache_data && $cache_data['expires'] > time()) {
                return $cache_data['data'];
            } else {
                unlink($cache_file);
            }
        }
        
        return false;
    }

    /**
     * Set cache data
     */
    private function setCache($key, $data, $ttl = 60)
    {
        $cache_dir = APPPATH . 'cache/supervisor/';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        $cache_file = $cache_dir . md5($key) . '.cache';
        $cache_data = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($cache_file, serialize($cache_data));
    }

    /**
     * Generate cache key
     */
    private function getCacheKey($server, $method, $request)
    {
        return 'supervisor_' . $server . '_' . $method . '_' . md5(serialize($request));
    }

    /**
     * Check if error is temporary and worth retrying
     */
    private function isTemporaryError($error)
    {
        $temp_errors = ['timeout', 'connection', 'refused', 'unreachable'];
        $error_lower = strtolower($error);
        
        foreach ($temp_errors as $temp_error) {
            if (strpos($error_lower, $temp_error) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Build XML-RPC request
     */
    private function buildXmlrpcRequest($method, $params)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<methodCall>' . "\n";
        $xml .= '<methodName>' . htmlspecialchars($method, ENT_XML1, 'UTF-8') . '</methodName>' . "\n";
        $xml .= '<params>' . "\n";
        
        if (!empty($params)) {
            foreach ($params as $param) {
                $xml .= '<param><value>';
                if (is_string($param)) {
                    $xml .= '<string>' . htmlspecialchars($param, ENT_XML1, 'UTF-8') . '</string>';
                } elseif (is_int($param)) {
                    $xml .= '<int>' . $param . '</int>';
                } elseif (is_bool($param)) {
                    $xml .= '<boolean>' . ($param ? '1' : '0') . '</boolean>';
                } elseif (is_array($param)) {
                    $xml .= '<array><data>';
                    foreach ($param as $item) {
                        $xml .= '<value><string>' . htmlspecialchars($item, ENT_XML1, 'UTF-8') . '</string></value>';
                    }
                    $xml .= '</data></array>';
                }
                $xml .= '</value></param>' . "\n";
            }
        }
        
        $xml .= '</params>' . "\n";
        $xml .= '</methodCall>';
        return $xml;
    }

    /**
     * Parse XML-RPC response with improved error handling
     */
    private function parseXmlrpcResponse($xml)
    {
        // Clean up response
        $xml = trim($xml);
        
        // Check if it's valid XML
        if (empty($xml) || strpos($xml, '<?xml') === false) {
            return ['error' => 'Invalid XML response: ' . substr($xml, 0, 100)];
        }
        
        // Check for XML-RPC fault
        if (strpos($xml, '<fault>') !== false) {
            // Extract fault message
            preg_match('/<string>(.*?)<\/string>/s', $xml, $matches);
            $fault_message = isset($matches[1]) ? $matches[1] : 'Unknown XML-RPC fault';
            return ['error' => 'XML-RPC Fault: ' . $fault_message];
        }
        
        // Check for methodResponse
        if (strpos($xml, '<methodResponse>') === false) {
            return ['error' => 'Invalid XML-RPC response format'];
        }
        
        // Try to use built-in XML parsing if available
        if (function_exists('xmlrpc_decode')) {
            $decoded = @xmlrpc_decode($xml);
            if ($decoded !== null) {
                return $decoded;
            }
        }
        
        // Enhanced manual parsing for supervisor XML format
        return $this->parseXmlrpcResponseManual($xml);
    }
    
    /**
     * Manual XML-RPC parser specifically for supervisor format
     */
    private function parseXmlrpcResponseManual($xml)
    {
        // Extract the main value from methodResponse
        preg_match('/<methodResponse>\s*<params>\s*<param>\s*<value>(.*?)<\/value>\s*<\/param>\s*<\/params>\s*<\/methodResponse>/s', $xml, $matches);
        
        if (!isset($matches[1])) {
            return ['error' => 'No param value found in methodResponse'];
        }
        
        $value = $matches[1];
        
        // Check if it's an array
        if (strpos($value, '<array>') !== false) {
            // Extract array data
            preg_match('/<array>\s*<data>(.*?)<\/data>\s*<\/array>/s', $value, $array_matches);
            
            if (!isset($array_matches[1])) {
                return ['error' => 'No data found in array'];
            }
            
            $array_data = $array_matches[1];
            
            // Find all struct values in the array
            preg_match_all('/<value>\s*<struct>(.*?)<\/struct>\s*<\/value>/s', $array_data, $struct_matches);
            
            $result = [];
            
            foreach ($struct_matches[1] as $struct_content) {
                $struct_data = $this->parseXmlrpcStruct($struct_content);
                if ($struct_data) {
                    $result[] = $struct_data;
                }
            }
            
            return $result;
        }
        
        // If not an array, parse as single value
        return $this->parseXmlrpcValue($value);
    }
    
    /**
     * Parse XML-RPC struct (for process info)
     */
    private function parseXmlrpcStruct($struct_content)
    {
        $result = [];
        
        // Find all members in the struct
        preg_match_all('/<member>\s*<name>(.*?)<\/name>\s*<value>(.*?)<\/value>\s*<\/member>/s', $struct_content, $member_matches, PREG_SET_ORDER);
        
        foreach ($member_matches as $member) {
            $key = trim($member[1]);
            $value_content = $member[2];
            
            // Parse the value based on its type
            if (preg_match('/<string>(.*?)<\/string>/s', $value_content, $string_match)) {
                $result[$key] = html_entity_decode($string_match[1], ENT_XML1, 'UTF-8');
            } elseif (preg_match('/<int>(.*?)<\/int>/s', $value_content, $int_match)) {
                $result[$key] = (int) $int_match[1];
            } elseif (preg_match('/<boolean>(.*?)<\/boolean>/s', $value_content, $bool_match)) {
                $result[$key] = ($bool_match[1] === '1' || $bool_match[1] === 'true');
            } elseif (preg_match('/<double>(.*?)<\/double>/s', $value_content, $double_match)) {
                $result[$key] = (float) $double_match[1];
            } else {
                // Fallback for other types
                $result[$key] = strip_tags($value_content);
            }
        }
        
        return $result;
    }

    /**
     * Parse XML-RPC value with better type handling
     */
    private function parseXmlrpcValue($value)
    {
        $value = trim($value);
        
        // Handle arrays
        if (strpos($value, '<array>') !== false) {
            $result = [];
            preg_match_all('/<value>(.*?)<\/value>/s', $value, $array_matches);
            foreach ($array_matches[1] as $item) {
                $result[] = $this->parseXmlrpcValue($item);
            }
            return $result;
        }
        
        // Handle structs (dictionaries)
        if (strpos($value, '<struct>') !== false) {
            $result = [];
            preg_match_all('/<member>\s*<name>(.*?)<\/name>\s*<value>(.*?)<\/value>\s*<\/member>/s', $value, $struct_matches, PREG_SET_ORDER);
            foreach ($struct_matches as $match) {
                $key = trim($match[1]);
                $val = $this->parseXmlrpcValue($match[2]);
                $result[$key] = $val;
            }
            return $result;
        }
        
        // Handle strings
        if (preg_match('/<string>(.*?)<\/string>/s', $value, $matches)) {
            return html_entity_decode($matches[1], ENT_XML1, 'UTF-8');
        }
        
        // Handle integers
        if (preg_match('/<int>(.*?)<\/int>/', $value, $matches)) {
            return (int) $matches[1];
        }
        
        // Handle booleans
        if (preg_match('/<boolean>(.*?)<\/boolean>/', $value, $matches)) {
            return $matches[1] === '1' || $matches[1] === 'true';
        }
        
        // Handle doubles/floats
        if (preg_match('/<double>(.*?)<\/double>/', $value, $matches)) {
            return (float) $matches[1];
        }
        
        // Return as-is if no type wrapper found
        return strip_tags($value);
    }
    
    /**
     * Log XML-RPC errors for debugging
     */
    private function logXmlRpcError($server, $method, $error, $config)
    {
        $log_dir = APPPATH . 'logs/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => $server,
            'method' => $method,
            'error' => $error,
            'url' => $config['url'] . ':' . $config['port'] . '/RPC2',
            'has_auth' => isset($config['username'])
        ];
        
        $log_file = $log_dir . 'xmlrpc_errors.log';
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND);
    }
    
    /**
     * Format XML-RPC error for better user display
     */
    private function formatXmlRpcError($error)
    {
        // Common error translations
        $error_map = [
            'The XML data received was either invalid or not in the correct form for XML-RPC' => 'Server không hỗ trợ XML-RPC hoặc cấu hình sai endpoint',
            'Connection refused' => 'Không thể kết nối đến server (Connection refused)',
            'Connection timed out' => 'Kết nối bị timeout',
            'Could not connect to host' => 'Không thể kết nối đến host',
            'HTTP/1.1 401 Unauthorized' => 'Sai username/password',
            'HTTP/1.1 404 Not Found' => 'Endpoint /RPC2 không tồn tại'
        ];
        
        foreach ($error_map as $pattern => $translation) {
            if (strpos($error, $pattern) !== false) {
                return $translation;
            }
        }
        
        return $error;
    }
    
    /**
     * Parallel stop all processes on a server
     * Uses curl_multi to stop processes in parallel instead of sequential
     * Expected time: 2-3 seconds instead of 12+ seconds
     */
    public function stopAllProcessesParallel($server)
    {
        $servers = $this->config->item('supervisor_servers');
        if (!isset($servers[$server])) {
            return ['error' => 'Invalid server: ' . $server];
        }
        
        $config = $servers[$server];
        $log = [];
        $log[] = "Getting all processes...";
        
        // First, get all processes
        $processes_result = $this->_request($server, 'getAllProcessInfo', [], false);
        
        if (isset($processes_result['error'])) {
            $log[] = "Error getting process list: " . $processes_result['error'];
            return [
                'success' => false,
                'message' => 'Failed to get process list',
                'log' => implode(' | ', $log)
            ];
        }
        
        if (!is_array($processes_result)) {
            $log[] = "Invalid process list format";
            return [
                'success' => false,
                'message' => 'Invalid process list format',
                'log' => implode(' | ', $log)
            ];
        }
        
        $process_count = count($processes_result);
        $log[] = "Found $process_count processes";
        
        if ($process_count === 0) {
            $log[] = "No processes to stop";
            return [
                'success' => true,
                'message' => 'No processes to stop',
                'log' => implode(' | ', $log)
            ];
        }
        
        // Build parallel stop requests
        $log[] = "Building parallel stop requests...";
        $stop_requests = [];
        
        foreach ($processes_result as $index => $process) {
            $process_name = isset($process['name']) ? $process['name'] : '';
            $process_state = isset($process['statename']) ? $process['statename'] : 'UNKNOWN';
            
            // Only stop if not already stopped
            if ($process_state !== 'STOPPED' && $process_state !== 'EXITED') {
                $stop_requests[] = [
                    'index' => $index,
                    'server' => $server,
                    'method' => 'stopProcess',
                    'request' => [$process_name, true],  // wait=true for graceful shutdown
                    'process_name' => $process_name
                ];
            }
        }
        
        $processes_to_stop = count($stop_requests);
        $log[] = "Stopping $processes_to_stop processes in parallel...";
        
        if ($processes_to_stop === 0) {
            $log[] = "All processes already stopped";
            return [
                'success' => true,
                'message' => 'All processes already stopped',
                'log' => implode(' | ', $log)
            ];
        }
        
        // Execute parallel stop requests
        $start_time = microtime(true);
        $stop_responses = $this->executeParallelRequests($stop_requests);
        $elapsed = microtime(true) - $start_time;
        
        $log[] = "Parallel stop completed in " . round($elapsed, 2) . "s";
        
        // Check results
        $success_count = 0;
        $failed_processes = [];
        
        foreach ($stop_responses as $index => $response) {
            $process_name = $stop_requests[$index]['process_name'];
            
            if (isset($response['error'])) {
                $failed_processes[] = "$process_name: " . $response['error'];
                $log[] = "Failed to stop $process_name: " . $response['error'];
            } else {
                $success_count++;
                $log[] = "Stopped: $process_name";
            }
        }
        
        if (count($failed_processes) > 0) {
            return [
                'success' => false,
                'message' => "Stopped $success_count/$processes_to_stop processes. Failed: " . implode(', ', $failed_processes),
                'log' => implode(' | ', $log)
            ];
        }
        
        return [
            'success' => true,
            'message' => "Successfully stopped $success_count processes in parallel (took " . round($elapsed, 2) . "s)",
            'log' => implode(' | ', $log),
            'stopped_count' => $success_count,
            'elapsed_time' => round($elapsed, 2)
        ];
    }
    
    /**
     * Parallel start all processes on a server
     * Uses curl_multi to start processes in parallel
     */
    public function startAllProcessesParallel($server)
    {
        $servers = $this->config->item('supervisor_servers');
        if (!isset($servers[$server])) {
            return ['error' => 'Invalid server: ' . $server];
        }
        
        $config = $servers[$server];
        $log = [];
        $log[] = "Getting all processes...";
        
        // First, get all processes
        $processes_result = $this->_request($server, 'getAllProcessInfo', [], false);
        
        if (isset($processes_result['error'])) {
            $log[] = "Error getting process list: " . $processes_result['error'];
            return [
                'success' => false,
                'message' => 'Failed to get process list',
                'log' => implode(' | ', $log)
            ];
        }
        
        if (!is_array($processes_result)) {
            $log[] = "Invalid process list format";
            return [
                'success' => false,
                'message' => 'Invalid process list format',
                'log' => implode(' | ', $log)
            ];
        }
        
        $process_count = count($processes_result);
        $log[] = "Found $process_count processes";
        
        // Build parallel start requests
        $log[] = "Building parallel start requests...";
        $start_requests = [];
        
        foreach ($processes_result as $index => $process) {
            $process_name = isset($process['name']) ? $process['name'] : '';
            $process_state = isset($process['statename']) ? $process['statename'] : 'UNKNOWN';
            
            // Only start if not already running
            if ($process_state !== 'RUNNING' && $process_state !== 'STARTING') {
                $start_requests[] = [
                    'index' => $index,
                    'server' => $server,
                    'method' => 'startProcess',
                    'request' => [$process_name, true],  // wait=true
                    'process_name' => $process_name
                ];
            }
        }
        
        $processes_to_start = count($start_requests);
        $log[] = "Starting $processes_to_start processes in parallel...";
        
        if ($processes_to_start === 0) {
            $log[] = "All processes already running";
            return [
                'success' => true,
                'message' => 'All processes already running',
                'log' => implode(' | ', $log)
            ];
        }
        
        // Execute parallel start requests
        $start_time = microtime(true);
        $start_responses = $this->executeParallelRequests($start_requests);
        $elapsed = microtime(true) - $start_time;
        
        $log[] = "Parallel start completed in " . round($elapsed, 2) . "s";
        
        // Check results
        $success_count = 0;
        $failed_processes = [];
        
        foreach ($start_responses as $index => $response) {
            $process_name = $start_requests[$index]['process_name'];
            
            if (isset($response['error'])) {
                $failed_processes[] = "$process_name: " . $response['error'];
                $log[] = "Failed to start $process_name: " . $response['error'];
            } else {
                $success_count++;
                $log[] = "Started: $process_name";
            }
        }
        
        if (count($failed_processes) > 0) {
            return [
                'success' => false,
                'message' => "Started $success_count/$processes_to_start processes. Failed: " . implode(', ', $failed_processes),
                'log' => implode(' | ', $log)
            ];
        }
        
        return [
            'success' => true,
            'message' => "Successfully started $processes_to_start processes in parallel (took " . round($elapsed, 2) . "s)",
            'log' => implode(' | ', $log),
            'started_count' => $processes_to_start,
            'elapsed_time' => round($elapsed, 2)
        ];
    }
    
    /**
     * Execute multiple XML-RPC requests in parallel using curl_multi
     */
    private function executeParallelRequests($requests)
    {
        $servers = $this->config->item('supervisor_servers');
        $responses = [];
        $handles = [];
        $request_map = [];
        
        // Create cURL multi handle
        $mh = curl_multi_init();
        
        foreach ($requests as $index => $req) {
            $server = $req['server'];
            $method = $req['method'];
            $request = $req['request'];
            
            if (!isset($servers[$server])) {
                $responses[$index] = ['error' => 'Invalid server: ' . $server];
                continue;
            }
            
            $config = $servers[$server];
            $url = $config['url'] . ':' . $config['port'] . '/RPC2';
            
            // Build XML-RPC request
            $xml_request = $this->buildXmlrpcRequest('supervisor.' . $method, $request);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $xml_request,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,  // Individual timeout for each process
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml',
                    'Content-Length: ' . strlen($xml_request),
                    'User-Agent: SupervisorMonitor/1.0'
                ],
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]);
            
            if (isset($config['username']) && isset($config['password'])) {
                curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            }
            
            $handles[$index] = $ch;
            $request_map[$index] = $req['process_name'];
            curl_multi_add_handle($mh, $ch);
        }
        
        // Execute all requests in parallel
        if (!empty($handles)) {
            $running = null;
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            } while ($running > 0);
            
            // Collect results
            foreach ($handles as $index => $ch) {
                $result = curl_multi_getcontent($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                
                if ($curl_error) {
                    $responses[$index] = ['error' => 'cURL Error: ' . $curl_error];
                } elseif ($http_code !== 200) {
                    $responses[$index] = ['error' => "HTTP Error: $http_code"];
                } elseif (empty($result)) {
                    $responses[$index] = ['error' => 'Empty response'];
                } else {
                    $parsed = $this->parseXmlrpcResponse($result);
                    $responses[$index] = $parsed;
                }
                
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
        }
        
        curl_multi_close($mh);
        return $responses;
    }

}
