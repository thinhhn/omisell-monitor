<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * XML-RPC Testing Controller
 * Use this to test and debug XML-RPC connections
 */
class TestXmlrpc extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('supervisor');
    }

    /**
     * Test XML-RPC connection for a specific server
     */
    public function index($server_name = null)
    {
        header('Content-Type: text/plain; charset=utf-8');
        
        echo "🧪 XML-RPC Connection Tester\n";
        echo "============================\n\n";

        $servers = $this->config->item('supervisor_servers');
        
        if ($server_name && isset($servers[$server_name])) {
            $this->testSingleServer($server_name, $servers[$server_name]);
        } else {
            echo "📋 Available servers:\n";
            foreach ($servers as $name => $config) {
                echo "   - $name\n";
            }
            echo "\n🔗 Test a specific server:\n";
            echo "   /test_xmlrpc/index/server_name\n\n";
            
            echo "🔍 Testing all servers...\n\n";
            foreach ($servers as $name => $config) {
                $this->testSingleServer($name, $config);
                echo "\n" . str_repeat("-", 60) . "\n\n";
            }
        }
    }
    
    private function testSingleServer($name, $config)
    {
        echo "🖥️ Testing: $name\n";
        echo "URL: {$config['url']}:{$config['port']}/RPC2\n";
        echo "Auth: " . (isset($config['username']) ? $config['username'] . '/****' : 'None') . "\n\n";
        
        // Test 1: Basic connectivity
        echo "1️⃣ Network Connectivity Test:\n";
        $host = parse_url($config['url'], PHP_URL_HOST);
        $connection = @fsockopen($host, $config['port'], $errno, $errstr, 5);
        if ($connection) {
            echo "   ✅ Port {$config['port']} is open on $host\n";
            fclose($connection);
        } else {
            echo "   ❌ Cannot connect to $host:{$config['port']} ($errstr)\n";
            return;
        }
        
        // Test 2: HTTP Response
        echo "\n2️⃣ HTTP Response Test:\n";
        $url = $config['url'] . ':' . $config['port'];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true
        ]);
        
        if (isset($config['username'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "   HTTP Code: $http_code\n";
        if ($http_code == 200 || $http_code == 405) {
            echo "   ✅ Server is responding\n";
        } else {
            echo "   ⚠️ Unexpected HTTP response: $http_code\n";
        }
        
        // Test 3: XML-RPC Call
        echo "\n3️⃣ XML-RPC Test:\n";
        
        $xml_request = '<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
<methodName>supervisor.getState</methodName>
<params>
</params>
</methodCall>';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . '/RPC2',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml_request,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml',
                'Content-Length: ' . strlen($xml_request)
            ]
        ]);
        
        if (isset($config['username'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
        }
        
        $xml_response = curl_exec($ch);
        $xml_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            echo "   ❌ cURL Error: $curl_error\n";
            return;
        }
        
        echo "   XML-RPC HTTP Code: $xml_http_code\n";
        
        if ($xml_http_code == 200) {
            echo "   ✅ XML-RPC endpoint is working\n";
            
            if (strpos($xml_response, '<fault>') !== false) {
                echo "   ⚠️ XML-RPC Fault detected\n";
                echo "   Response: " . substr($xml_response, 0, 200) . "...\n";
            } elseif (strpos($xml_response, '<methodResponse>') !== false) {
                echo "   ✅ Valid XML-RPC response received\n";
                
                // Try to extract state info
                if (preg_match('/<string>(.*?)<\/string>/', $xml_response, $matches)) {
                    echo "   State: " . $matches[1] . "\n";
                }
            } else {
                echo "   ⚠️ Unexpected XML-RPC response format\n";
                echo "   Response: " . substr(strip_tags($xml_response), 0, 100) . "...\n";
            }
        } else {
            echo "   ❌ XML-RPC failed with HTTP code: $xml_http_code\n";
            echo "   Response: " . substr($xml_response, 0, 200) . "...\n";
        }
    }
    
    /**
     * Test raw XML-RPC with custom method
     */
    public function testMethod($server_name, $method = 'getState')
    {
        header('Content-Type: text/plain; charset=utf-8');
        
        $servers = $this->config->item('supervisor_servers');
        if (!isset($servers[$server_name])) {
            echo "❌ Server '$server_name' not found\n";
            return;
        }
        
        echo "🧪 Testing XML-RPC Method: supervisor.$method\n";
        echo "Server: $server_name\n\n";
        
        $config = $servers[$server_name];
        $url = $config['url'] . ':' . $config['port'] . '/RPC2';
        
        $xml_request = '<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
<methodName>supervisor.' . $method . '</methodName>
<params>
</params>
</methodCall>';

        echo "📤 Request:\n$xml_request\n\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml_request,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml',
                'Content-Length: ' . strlen($xml_request)
            ]
        ]);
        
        if (isset($config['username'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "📥 Response (HTTP $http_code):\n";
        echo $response . "\n";
    }
}