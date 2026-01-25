<?php
/**
 * Test the new XML parser
 */

define('BASEPATH', TRUE);
define('APPPATH', './application/');

// Include the updated controller
include 'application/core/MY_Controller.php';
include 'application/config/supervisor.php';

echo "🧪 TESTING NEW XML-RPC PARSER\n";
echo "=============================\n\n";

// Create a mock CI controller to test the parser
class TestController extends MY_Controller {
    public $config;
    
    public function __construct() {
        // Don't call parent constructor to avoid CI dependencies
        global $config;
        $this->config = $config;
    }
    
    public function testParser($xml) {
        return $this->parseXmlrpcResponse($xml);
    }
    
    public function testManualParser($xml) {
        return $this->parseXmlrpcResponseManual($xml);
    }
    
    public function testStruct($struct_content) {
        return $this->parseXmlrpcStruct($struct_content);
    }
    
    // Make private methods accessible for testing
    public function parseXmlrpcResponse($xml) {
        return parent::parseXmlrpcResponse($xml);
    }
    
    public function parseXmlrpcResponseManual($xml) {
        return parent::parseXmlrpcResponseManual($xml);
    }
    
    public function parseXmlrpcStruct($struct_content) {
        return parent::parseXmlrpcStruct($struct_content);
    }
}

// Get real XML response from supervisor
$servers = $config['supervisor_servers'];
$test_server = 'web_001';
$server_config = $servers[$test_server];
$url = $server_config['url'] . ':' . $server_config['port'] . '/RPC2';

$xml_request = '<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
<methodName>supervisor.getAllProcessInfo</methodName>
<params></params>
</methodCall>';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $xml_request,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Content-Type: text/xml',
        'Content-Length: ' . strlen($xml_request)
    ]
]);

if (isset($server_config['username'])) {
    curl_setopt($ch, CURLOPT_USERPWD, $server_config['username'] . ':' . $server_config['password']);
}

$real_xml = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($http_code !== 200) {
    echo "❌ Could not get real XML response\n";
    exit(1);
}

echo "✅ Got real XML response (" . strlen($real_xml) . " bytes)\n\n";

// Test the new parser
$controller = new TestController();

echo "🔍 Testing new parser:\n";
$result = $controller->testParser($real_xml);

if (isset($result['error'])) {
    echo "❌ Parser error: {$result['error']}\n";
} else if (is_array($result)) {
    echo "✅ Parser success!\n";
    echo "   Parsed " . count($result) . " processes\n";
    
    if (!empty($result)) {
        $first = $result[0];
        echo "   First process:\n";
        echo "     Name: " . ($first['name'] ?? 'unknown') . "\n";
        echo "     State: " . ($first['statename'] ?? 'unknown') . "\n";
        echo "     Description: " . ($first['description'] ?? 'unknown') . "\n";
        
        if (count($result) > 1) {
            echo "   Sample process names:\n";
            for ($i = 0; $i < min(5, count($result)); $i++) {
                echo "     " . ($i + 1) . ". " . ($result[$i]['name'] ?? 'unknown') . "\n";
            }
        }
    }
} else {
    echo "⚠️ Unexpected result type: " . gettype($result) . "\n";
}

echo "\n💾 Now test in browser:\n";
echo "======================\n";
echo "🌐 http://localhost:8001/?debug=1\n";
echo "Should now show process data instead of empty arrays!\n\n";

// Clean up test file
unlink(__FILE__);
?>