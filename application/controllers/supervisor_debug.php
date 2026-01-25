<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Supervisor_debug extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->config('supervisor');
        // Bypass login for debugging
        $this->config->set_item('enable_login', false);
    }

    /**
     * Debug supervisor connections and data retrieval
     */
    public function index()
    {
        echo "<h1>🔍 Supervisor Data Debug</h1>";
        echo "<style>
            body { font-family: monospace; padding: 20px; background: #f5f5f5; }
            .server { background: white; margin: 15px 0; padding: 15px; border-radius: 5px; }
            .error { border-left: 4px solid #dc3545; }
            .success { border-left: 4px solid #28a745; }
            .warning { border-left: 4px solid #ffc107; }
            pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px; }
            .section { margin: 20px 0; padding: 15px; background: white; border-radius: 5px; }
        </style>";

        $this->testWelcomeControllerLogic();
        $this->testIndividualServers();
        $this->testParallelRequests();
        $this->showTroubleshootingTips();
    }

    private function testWelcomeControllerLogic()
    {
        echo "<div class='section'>";
        echo "<h2>📊 Testing Welcome Controller Logic</h2>";

        $servers = $this->config->item('supervisor_servers');
        if (!$servers) {
            echo "<p class='error'>❌ No supervisor_servers configured!</p>";
            echo "</div>";
            return;
        }

        echo "<p>✅ Found " . count($servers) . " configured servers</p>";

        // Simulate Welcome controller logic
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

        echo "<p>📡 Prepared " . count($parallel_requests) . " parallel requests</p>";
        
        $start_time = microtime(true);
        $responses = $this->_parallel_requests($parallel_requests);
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        echo "<p>⏱️ Execution time: {$execution_time}ms</p>";
        echo "<p>📥 Received " . count($responses) . " responses</p>";

        // Process responses like Welcome controller does
        $index = 0;
        $success_count = 0;
        $error_count = 0;

        foreach ($servers as $name => $config) {
            echo "<div class='server'>";
            echo "<h4>🖥️ Server: $name</h4>";

            $list_response = $responses['list_' . $index] ?? null;
            $version_response = $responses['version_' . $index] ?? null;

            if ($list_response && !isset($list_response['error'])) {
                echo "<p>✅ Process list: " . (is_array($list_response) ? count($list_response) . " processes" : "Success") . "</p>";
                $success_count++;
            } else {
                echo "<p>❌ Process list error: " . ($list_response['error'] ?? 'Unknown error') . "</p>";
                $error_count++;
            }

            if ($version_response && !isset($version_response['error'])) {
                echo "<p>✅ Version: " . (is_string($version_response) ? $version_response : "Retrieved") . "</p>";
            } else {
                echo "<p>❌ Version error: " . ($version_response['error'] ?? 'Unknown error') . "</p>";
            }

            echo "</div>";
            $index++;
        }

        echo "<div class='server success'>";
        echo "<h4>📈 Summary</h4>";
        echo "<p>Successful connections: $success_count</p>";
        echo "<p>Failed connections: $error_count</p>";
        echo "</div>";

        echo "</div>";
    }

    private function testIndividualServers()
    {
        echo "<div class='section'>";
        echo "<h2>🖥️ Individual Server Testing</h2>";

        $servers = $this->config->item('supervisor_servers');
        
        foreach ($servers as $name => $config) {
            echo "<div class='server'>";
            echo "<h4>Testing: $name</h4>";
            echo "<p><strong>Config:</strong></p>";
            echo "<pre>" . htmlspecialchars(print_r($config, true)) . "</pre>";

            // Test connection manually
            echo "<p><strong>Testing getAllProcessInfo:</strong></p>";
            $start_time = microtime(true);
            $result = $this->_request($name, 'getAllProcessInfo', [], false);
            $time = round((microtime(true) - $start_time) * 1000, 2);

            if (isset($result['error'])) {
                echo "<div class='error'>";
                echo "<p>❌ Error ({$time}ms): " . htmlspecialchars($result['error']) . "</p>";
                echo "</div>";
                
                // Additional debugging
                $this->debugConnection($config);
            } else {
                echo "<div class='success'>";
                echo "<p>✅ Success ({$time}ms): " . (is_array($result) ? count($result) . " processes" : "Data received") . "</p>";
                if (is_array($result) && !empty($result)) {
                    echo "<p><strong>Sample process:</strong></p>";
                    echo "<pre>" . htmlspecialchars(print_r($result[0], true)) . "</pre>";
                }
                echo "</div>";
            }

            echo "</div>";
        }

        echo "</div>";
    }

    private function debugConnection($config)
    {
        echo "<div class='warning'>";
        echo "<h5>🔧 Connection Debug</h5>";

        $url = $config['url'] . ':' . $config['port'] . '/RPC2';
        echo "<p><strong>Full URL:</strong> $url</p>";

        // Test basic connectivity
        $url_parts = parse_url($config['url']);
        $host = $url_parts['host'];
        $port = $config['port'];

        echo "<p><strong>Testing TCP connection...</strong></p>";
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($connection) {
            echo "<p>✅ TCP connection successful</p>";
            fclose($connection);
        } else {
            echo "<p>❌ TCP connection failed: $errstr ($errno)</p>";
        }

        // Test HTTP
        echo "<p><strong>Testing HTTP request...</strong></p>";
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
            echo "<p>❌ HTTP Error: $error</p>";
        } else {
            echo "<p>✅ HTTP Response: $http_code</p>";
        }

        echo "</div>";
    }

    private function testParallelRequests()
    {
        echo "<div class='section'>";
        echo "<h2>⚡ Parallel Requests Test</h2>";

        $servers = $this->config->item('supervisor_servers');
        $test_requests = [];

        // Create test requests
        foreach (array_slice($servers, 0, 3, true) as $name => $config) {  // Test first 3 servers only
            $test_requests['test_' . $name] = [
                'server' => $name,
                'method' => 'getState'
            ];
        }

        echo "<p>Testing parallel requests to " . count($test_requests) . " servers...</p>";

        $start_time = microtime(true);
        $results = $this->_parallel_requests($test_requests);
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);

        echo "<p>⏱️ Total execution time: {$execution_time}ms</p>";
        echo "<p>📊 Results:</p>";

        foreach ($results as $key => $result) {
            $server_name = str_replace('test_', '', $key);
            echo "<div class='server'>";
            echo "<h4>$server_name</h4>";
            if (isset($result['error'])) {
                echo "<p class='error'>❌ " . htmlspecialchars($result['error']) . "</p>";
            } else {
                echo "<p class='success'>✅ Success: " . htmlspecialchars(print_r($result, true)) . "</p>";
            }
            echo "</div>";
        }

        echo "</div>";
    }

    private function showTroubleshootingTips()
    {
        echo "<div class='section'>";
        echo "<h2>💡 Troubleshooting Guide</h2>";

        echo "<h3>Common Issues & Solutions:</h3>";
        echo "<ol>";
        echo "<li><strong>Connection Timeout:</strong> Check firewall settings and supervisord is running</li>";
        echo "<li><strong>Authentication Error:</strong> Verify username/password in config</li>";
        echo "<li><strong>Empty Response:</strong> Check supervisord XML-RPC interface is enabled</li>";
        echo "<li><strong>Permission Denied:</strong> Verify supervisord user permissions</li>";
        echo "<li><strong>Network Error:</strong> Test connectivity from your server to supervisor servers</li>";
        echo "</ol>";

        echo "<h3>Manual Testing Commands:</h3>";
        echo "<pre>";
        echo "# Test supervisord connectivity\n";
        echo "telnet [supervisor-ip] 9001\n\n";
        echo "# Check supervisord status\n";
        echo "supervisorctl status\n\n";
        echo "# Test XML-RPC manually\n";
        echo "curl -X POST http://[supervisor-ip]:9001/RPC2 \\\n";
        echo "     -H 'Content-Type: text/xml' \\\n";
        echo "     -u 'username:password' \\\n";
        echo "     -d '<?xml version=\"1.0\"?><methodCall><methodName>supervisor.getState</methodName><params></params></methodCall>'\n";
        echo "</pre>";

        echo "<h3>Supervisord Configuration Check:</h3>";
        echo "<pre>";
        echo "[inet_http_server]\n";
        echo "port=0.0.0.0:9001\n";
        echo "username=thinhhn\n";
        echo "password=thinh49121\n\n";
        echo "[supervisorctl]\n";
        echo "serverurl=http://127.0.0.1:9001\n";
        echo "username=thinhhn\n";
        echo "password=thinh49121\n";
        echo "</pre>";

        echo "</div>";
    }
}

/* End of file supervisor_debug.php */
/* Location: ./application/controllers/supervisor_debug.php */