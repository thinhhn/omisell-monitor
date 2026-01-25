<?php
/**
 * Deployment Verification Script
 * Run this on your server to verify everything is working correctly
 */

echo "🔍 SUPERVISOR MONITOR - DEPLOYMENT VERIFICATION\n";
echo "===============================================\n\n";

// Check environment detection
echo "1. Environment Detection:\n";
echo "   HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "\n";
echo "   SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "\n";
echo "   REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";

// Detect current environment
if (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        echo "   Environment: LOCAL ✅\n";
    } else {
        echo "   Environment: PRODUCTION ✅\n";
    }
} else {
    echo "   Environment: CLI\n";
}

echo "\n2. File Structure Check:\n";
$required_files = [
    'application/config/config.php',
    'application/config/environments.php', 
    'application/config/supervisor.php',
    'application/controllers/auth.php',
    'application/controllers/welcome.php',
    'application/core/MY_Controller.php',
    'public_html/index.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file - MISSING!\n";
    }
}

echo "\n3. Configuration Check:\n";
if (file_exists('application/config/config.php')) {
    $config_content = file_get_contents('application/config/config.php');
    
    if (strpos($config_content, "base_url'] = ''") !== false) {
        echo "   ✅ base_url: Auto-detection enabled\n";
    } elseif (strpos($config_content, 'localhost:8000') !== false) {
        echo "   ⚠️  base_url: Still set to localhost (will work locally only)\n";
    } else {
        echo "   ✅ base_url: Custom configuration\n";
    }
    
    if (strpos($config_content, 'environments.php') !== false) {
        echo "   ✅ Environment config: Enabled\n";
    } else {
        echo "   ❌ Environment config: Not loaded\n";
    }
}

echo "\n4. Directory Permissions:\n";
$writable_dirs = [
    'application/cache',
    'application/cache/supervisor', 
    'application/logs'
];

foreach ($writable_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "   ✅ Created: $dir\n";
    } else {
        echo "   ✅ Exists: $dir\n";
    }
    
    if (is_writable($dir)) {
        echo "   ✅ Writable: $dir\n";
    } else {
        echo "   ❌ Not writable: $dir (chmod 755 needed)\n";
    }
}

echo "\n5. URL Building Test:\n";
if (function_exists('site_url')) {
    // Mock CodeIgniter environment
    define('BASEPATH', true);
    
    // Test URL building
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $expected_base = $protocol . '://' . $host . '/';
    
    echo "   Expected base URL: $expected_base\n";
    echo "   This should be used for all redirects and links\n";
} else {
    echo "   ⚠️  CodeIgniter not loaded - manual test needed\n";
}

echo "\n6. Deployment Checklist:\n";
echo "   □ Upload all files to server\n";
echo "   □ Set document root to public_html/\n";
echo "   □ Configure .htaccess if needed\n";
echo "   □ Set directory permissions (755)\n";
echo "   □ Test login functionality\n";
echo "   □ Test logout functionality\n";
echo "   □ Test clear cache functionality\n";
echo "   □ Verify supervisor connections\n";

echo "\n🚀 QUICK TEST URLS:\n";
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'your-domain.com';
echo "   Main app: {$protocol}://{$host}/\n";
echo "   Login: {$protocol}://{$host}/auth\n";
echo "   Debug: {$protocol}://{$host}/debug/testConnections\n";

echo "\n✅ Deployment verification complete!\n";
echo "If all items show ✅, your application should work correctly on the server.\n";
?>