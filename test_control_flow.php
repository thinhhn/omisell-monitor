<?php
/**
 * Test complete control flow
 */

echo "🔧 TESTING COMPLETE CONTROL FLOW\n";
echo "================================\n\n";

// Test the complete flow as browser would do
$cookie_jar = '/tmp/supervisor_cookies.txt';

echo "1. Login:\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/auth/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => 'username=admin&password=admin123',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookie_jar,
    CURLOPT_HEADER => true
]);

$login_response = curl_exec($ch);
$login_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Login HTTP code: $login_code\n";
if ($login_code == 200) {
    echo "   ✅ Login successful\n";
} else {
    echo "   ❌ Login failed\n";
    exit(1);
}

echo "\n2. Test control action:\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/control/restart/web_002/omi_api_gunicorn',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEFILE => $cookie_jar,
    CURLOPT_HEADER => true
]);

$control_response = curl_exec($ch);
$control_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirect_count = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
curl_close($ch);

echo "   Control HTTP code: $control_code\n";
echo "   Redirect count: $redirect_count\n";

// Check for flash messages in response
if (strpos($control_response, 'success') !== false) {
    echo "   ✅ Success message found in response\n";
} elseif (strpos($control_response, 'error') !== false) {
    echo "   ❌ Error message found in response\n";
} else {
    echo "   ⚠️ No clear success/error message\n";
}

// Check if we're back on main page
if (strpos($control_response, 'Supervisor') !== false && strpos($control_response, 'web_002') !== false) {
    echo "   ✅ Redirected back to main page\n";
} else {
    echo "   ❌ Not on expected page\n";
}

echo "\n3. Check if action actually worked by checking supervisor:\n";
// Load supervisor config and test directly
include 'application/config/supervisor.php';
$servers = $config['supervisor_servers'];
$server_config = $servers['web_002'];
$supervisor_url = $server_config['url'] . ':' . $server_config['port'] . '/RPC2';

$info_xml = '<?xml version="1.0" encoding="UTF-8"?>
<methodCall>
<methodName>supervisor.getProcessInfo</methodName>
<params>
<param><value><string>omi_api_gunicorn</string></value></param>
</params>
</methodCall>';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $supervisor_url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $info_xml,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_HTTPHEADER => [
        'Content-Type: text/xml',
        'Content-Length: ' . strlen($info_xml)
    ],
    CURLOPT_USERPWD => $server_config['username'] . ':' . $server_config['password']
]);

$supervisor_response = curl_exec($ch);
curl_close($ch);

if (strpos($supervisor_response, '<fault>') === false) {
    preg_match('/<name>statename<\/name>\s*<value><string>(.*?)<\/string><\/value>/s', $supervisor_response, $matches);
    $status = isset($matches[1]) ? $matches[1] : 'UNKNOWN';
    echo "   Current process status: $status\n";
    
    preg_match('/<name>start<\/name>\s*<value><int>(.*?)<\/int><\/value>/s', $supervisor_response, $start_matches);
    $start_time = isset($start_matches[1]) ? $start_matches[1] : 0;
    
    if ($start_time > 0) {
        $ago = time() - $start_time;
        echo "   Started $ago seconds ago\n";
        if ($ago < 60) {
            echo "   🎉 Process was recently restarted!\n";
        }
    }
} else {
    echo "   ❌ Failed to get process info\n";
}

// Cleanup
@unlink($cookie_jar);
unlink(__FILE__);

echo "\n💡 SUMMARY:\n";
echo "============\n";
echo "Control action flow tested end-to-end.\n";
echo "If process shows recent restart time, the action IS working!\n";
?>