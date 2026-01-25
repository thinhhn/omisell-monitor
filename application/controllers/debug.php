<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Debug extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('supervisor');
    }

    /**
     * Test supervisor connections via web interface
     */
    public function testConnections()
    {
        echo "<h1>🔍 Supervisor Connection Debug</h1>";
        echo "<style>
            body { font-family: monospace; padding: 20px; background: #f5f5f5; }
            .server { background: white; margin: 10px 0; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
            .error { border-left-color: #dc3545; }
            .success { border-left-color: #28a745; }
            .warning { border-left-color: #ffc107; }
            pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        </style>";

        $servers = $this->config->item('supervisor_servers');
        
        if (!$servers) {
            echo "<div class='server error'><h3>❌ No servers configured</h3></div>";
            return;
        }

        echo "<p><strong>📋 Testing " . count($servers) . " configured servers...</strong></p>";

        foreach ($servers as $name => $config) {
            $this->testSingleServer($name, $config);
        }

        echo "<div class='server'>";
        echo "<h3>🔧 Quick Fix Suggestions:</h3>";
        echo "<ol>";
        echo "<li><strong>URL Format Issue:</strong> Make sure supervisor URL doesn't include port twice</li>";
        echo "<li><strong>Network Access:</strong> Check if your local machine can reach the server IPs</li>";
        echo "<li><strong>Supervisord Config:</strong> Verify inet_http_server is properly configured</li>";
        echo "<li><strong>Firewall:</strong> Ensure port 9001 is open on the supervisor servers</li>";
        echo "</ol>";
        echo "</div>";
    }

    private function testSingleServer($name, $config)
    {
        echo "<div class='server'>";
        echo "<h3>🖥️ Server: $name</h3>";
        echo "<p><strong>URL:</strong> {$config['url']}<br>";
        echo "<strong>Port:</strong> {$config['port']}<br>";
        if (isset($config['username'])) {
            echo "<strong>Auth:</strong> {$config['username']}/****<br>";
        }
        echo "</p>";

        // Parse and fix URL
        $url_parts = parse_url($config['url']);
        $scheme = $url_parts['scheme'] ?? 'http';
        $host = $url_parts['host'] ?? 'unknown';
        $path = $url_parts['path'] ?? '/RPC2';
        
        // Build correct URL
        $full_url = $scheme . '://' . $host . ':' . $config['port'] . $path;
        echo "<p><strong>🌐 Full URL:</strong> $full_url</p>";

        // Test using CodeIgniter's built-in method (if available)
        try {
            echo "<h4>📡 Testing with Application Method:</h4>";
            $result = $this->_request($name, 'getState', [], false);
            
            if (isset($result['error'])) {
                echo "<pre class='error'>❌ Error: " . htmlspecialchars($result['error']) . "</pre>";
                
                // Additional debugging
                $this->debugWithCurl($full_url, $config);
            } else {
                echo "<pre class='success'>✅ Success! Supervisor responded correctly.</pre>";
                echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
            }
            
        } catch (Exception $e) {
            echo "<pre class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</pre>";
            $this->debugWithCurl($full_url, $config);
        }

        echo "</div>";
    }

    private function debugWithCurl($url, $config)
    {
        echo "<h4>🔧 Manual cURL Debug:</h4>";
        
        // Test simple connection first
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true
        ]);
        
        if (isset($config['username']) && isset($config['password'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        if ($error) {
            echo "<pre class='error'>❌ cURL Error: " . htmlspecialchars($error) . "</pre>";
        } else {
            echo "<pre class='success'>✅ cURL Connection: HTTP $http_code</pre>";
        }

        // Test XML-RPC call
        $xml_request = '<?xml version="1.0"?>
<methodCall>
    <methodName>supervisor.getState</methodName>
    <params></params>
</methodCall>';

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml_request,
            CURLOPT_NOBODY => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml',
                'Content-Length: ' . strlen($xml_request)
            ]
        ]);
        
        $xml_response = curl_exec($ch);
        $xml_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $xml_error = curl_error($ch);
        
        if ($xml_error) {
            echo "<pre class='error'>❌ XML-RPC Error: " . htmlspecialchars($xml_error) . "</pre>";
        } else {
            echo "<pre>📤 XML-RPC HTTP Code: $xml_http_code</pre>";
            if ($xml_response) {
                $clean_response = strip_tags($xml_response);
                echo "<pre>📥 Response: " . htmlspecialchars(substr($clean_response, 0, 200)) . "...</pre>";
            }
        }
    }

    /**
     * Show current configuration
     */
    public function showConfig()
    {
        echo "<h1>⚙️ Current Configuration</h1>";
        echo "<style>
            body { font-family: monospace; padding: 20px; background: #f5f5f5; }
            pre { background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
        </style>";

        $servers = $this->config->item('supervisor_servers');
        echo "<h3>📋 Supervisor Servers:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($servers, true)) . "</pre>";

        $login_accounts = $this->config->item('login_accounts');
        echo "<h3>🔐 Login Accounts:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($login_accounts, true)) . "</pre>";

        echo "<h3>⚙️ Other Settings:</h3>";
        $other_settings = [
            'enable_login' => $this->config->item('enable_login'),
            'login_timeout' => $this->config->item('login_timeout'),
            'timeout' => $this->config->item('timeout'),
            'refresh' => $this->config->item('refresh'),
            'supervisor_cols' => $this->config->item('supervisor_cols')
        ];
        echo "<pre>" . htmlspecialchars(print_r($other_settings, true)) . "</pre>";
    }
}