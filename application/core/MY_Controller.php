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
     * Optimized request with caching and error handling
     */
    public function _request($server, $method, $request = [], $use_cache = true)
    {
        // Check cache first if enabled
        if ($use_cache) {
            $cache_key = $this->getCacheKey($server, $method, $request);
            $cached_response = $this->getCache($cache_key);
            if ($cached_response !== false) {
                return $cached_response;
            }
        }

        $servers = $this->config->item('supervisor_servers');
        if (!isset($servers[$server])) {
            return ['error' => "Invalid server: " . $server];
        }
        
        $config = $servers[$server];
        $response = $this->executeRequest($server, $method, $request, $config);
        
        // Cache successful responses
        if ($use_cache && !isset($response['error'])) {
            $this->setCache($cache_key, $response, 30); // Cache for 30 seconds
        }
        
        return $response;
    }

    /**
     * Execute XML-RPC request with retry mechanism
     */
    private function executeRequest($server, $method, $request, $config)
    {
        $max_retries = 2;
        $retry_count = 0;
        
        while ($retry_count <= $max_retries) {
            try {
                $this->load->library('xmlrpc', [], $server);
                $this->{$server}->initialize();
                $this->{$server}->server($config['url'], $config['port']);
                $this->{$server}->method('supervisor.' . $method);
                $this->{$server}->timeout($this->config->item('timeout'));
                
                if (isset($config['username']) && isset($config['password'])) {
                    $this->{$server}->setCredentials($config['username'], $config['password']);
                }
                
                $this->{$server}->request($request);

                if (!$this->{$server}->send_request()) {
                    $error = $this->{$server}->display_error();
                    
                    // If it's a temporary error, retry
                    if ($retry_count < $max_retries && $this->isTemporaryError($error)) {
                        $retry_count++;
                        usleep(100000); // Wait 100ms before retry
                        continue;
                    }
                    
                    return ['error' => $error, 'server' => $server];
                } else {
                    return $this->{$server}->display_response();
                }
            } catch (Exception $e) {
                if ($retry_count < $max_retries) {
                    $retry_count++;
                    usleep(100000);
                    continue;
                }
                return ['error' => 'Connection failed: ' . $e->getMessage(), 'server' => $server];
            }
        }
        
        return ['error' => 'Max retries exceeded', 'server' => $server];
    }

    /**
     * Parallel requests to multiple servers
     */
    public function _parallel_requests($servers_methods)
    {
        $responses = [];
        $handles = [];
        
        // Create cURL multi handle
        $mh = curl_multi_init();
        
        foreach ($servers_methods as $key => $data) {
            $server = $data['server'];
            $method = $data['method'];
            $request = isset($data['request']) ? $data['request'] : [];
            
            // Check cache first
            $cache_key = $this->getCacheKey($server, $method, $request);
            $cached_response = $this->getCache($cache_key);
            
            if ($cached_response !== false) {
                $responses[$key] = $cached_response;
                continue;
            }
            
            // Create cURL handle for non-cached requests
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
                
                if ($http_code == 200 && $result) {
                    $response = $this->parseXmlrpcResponse($result);
                    $responses[$key] = $response;
                    
                    // Cache successful responses
                    $server = $servers_methods[$key]['server'];
                    $method = $servers_methods[$key]['method'];
                    $request = isset($servers_methods[$key]['request']) ? $servers_methods[$key]['request'] : [];
                    $cache_key = $this->getCacheKey($server, $method, $request);
                    $this->setCache($cache_key, $response, 30);
                } else {
                    $responses[$key] = [
                        'error' => 'Request failed', 
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
        $xml = '<?xml version="1.0"?><methodCall>';
        $xml .= '<methodName>' . htmlspecialchars($method) . '</methodName>';
        $xml .= '<params>';
        
        foreach ($params as $param) {
            $xml .= '<param><value>';
            if (is_string($param)) {
                $xml .= '<string>' . htmlspecialchars($param) . '</string>';
            } elseif (is_int($param)) {
                $xml .= '<int>' . $param . '</int>';
            } elseif (is_bool($param)) {
                $xml .= '<boolean>' . ($param ? '1' : '0') . '</boolean>';
            }
            $xml .= '</value></param>';
        }
        
        $xml .= '</params></methodCall>';
        return $xml;
    }

    /**
     * Parse XML-RPC response
     */
    private function parseXmlrpcResponse($xml)
    {
        // Simple XML parsing - in production, use proper XML parser
        if (strpos($xml, '<fault>') !== false) {
            return ['error' => 'XML-RPC Fault'];
        }
        
        // Extract value from response (simplified)
        preg_match('/<value>(.*?)<\/value>/s', $xml, $matches);
        if (isset($matches[1])) {
            return $this->parseXmlrpcValue($matches[1]);
        }
        
        return ['error' => 'Invalid response'];
    }

    /**
     * Parse XML-RPC value (simplified)
     */
    private function parseXmlrpcValue($value)
    {
        // This is a simplified parser - in production use xmlrpc_decode
        if (strpos($value, '<array>') !== false) {
            return []; // Simplified array handling
        } elseif (strpos($value, '<string>') !== false) {
            preg_match('/<string>(.*?)<\/string>/', $value, $matches);
            return isset($matches[1]) ? $matches[1] : '';
        }
        
        return $value;
    }

}
