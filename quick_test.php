<?php
/**
 * Quick Test Script - Test if application works
 */

// Set up basic environment
define('BASEPATH', TRUE);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 QUICK TEST - Supervisor Monitor\n";
echo "==================================\n\n";

// Test 1: Basic file loading
echo "1. Testing Core Files:\n";
$core_files = [
    'application/config/config.php' => 'Main Config',
    'application/config/supervisor.php' => 'Supervisor Config',
    'application/controllers/welcome.php' => 'Welcome Controller',
    'application/controllers/auth.php' => 'Auth Controller',
    'application/core/MY_Controller.php' => 'Base Controller'
];

$all_good = true;
foreach ($core_files as $file => $desc) {
    if (file_exists($file)) {
        // Test if file can be included without errors
        ob_start();
        $error = false;
        try {
            include_once $file;
        } catch (ParseError $e) {
            $error = "Parse Error: " . $e->getMessage();
        } catch (Error $e) {
            $error = "Error: " . $e->getMessage();
        }
        ob_end_clean();
        
        if ($error) {
            echo "   ❌ $desc: $error\n";
            $all_good = false;
        } else {
            echo "   ✅ $desc: OK\n";
        }
    } else {
        echo "   ❌ $desc: File missing\n";
        $all_good = false;
    }
}

// Test 2: Configuration check
echo "\n2. Testing Configuration:\n";
if (isset($config)) {
    if (isset($config['base_url'])) {
        echo "   ✅ base_url: " . ($config['base_url'] ?: 'auto-detect') . "\n";
    }
    if (isset($config['encryption_key']) && !empty($config['encryption_key'])) {
        echo "   ✅ encryption_key: Set\n";
    } else {
        echo "   ❌ encryption_key: Not set\n";
        $all_good = false;
    }
    if (isset($config['supervisor_servers'])) {
        $server_count = count($config['supervisor_servers']);
        echo "   ✅ supervisor_servers: $server_count servers configured\n";
    }
    if (isset($config['login_accounts'])) {
        $account_count = count($config['login_accounts']);
        echo "   ✅ login_accounts: $account_count accounts configured\n";
    }
} else {
    echo "   ❌ Config not loaded properly\n";
    $all_good = false;
}

// Test 3: Web server test
echo "\n3. Testing Web Server Access:\n";
if (file_exists('public_html/index.php')) {
    echo "   ✅ CodeIgniter entry point exists\n";
    
    // Test if we can start the built-in server
    $host = 'localhost';
    $port = 8000;
    
    // Check if port is available
    $connection = @fsockopen($host, $port, $errno, $errstr, 1);
    if ($connection) {
        fclose($connection);
        echo "   ⚠️ Port $port is already in use\n";
        $port = 8001;
    }
    
    echo "   💡 To start server: cd public_html && php -S $host:$port\n";
    echo "   🌐 Then visit: http://$host:$port\n";
} else {
    echo "   ❌ CodeIgniter entry point missing\n";
    $all_good = false;
}

// Test 4: Quick syntax check on key files
echo "\n4. Quick Start Commands:\n";
echo "   ./start_local_server.sh    # Start development server\n";
echo "   php debug_errors.php       # Full error diagnosis\n";
echo "   php quick_test.php         # This test script\n";

// Final result
echo "\n" . str_repeat("=", 50) . "\n";
if ($all_good) {
    echo "🎉 SUCCESS! Application should work properly.\n\n";
    echo "📋 Next Steps:\n";
    echo "1. Start the server: cd public_html && php -S localhost:8000\n";
    echo "2. Visit: http://localhost:8000\n";
    echo "3. Login with: admin / admin123\n";
    echo "4. View supervisor processes\n\n";
    echo "🔍 If you still get errors:\n";
    echo "- Check web server error logs\n";
    echo "- Run: php debug_errors.php\n";
    echo "- Share the specific error message\n";
} else {
    echo "❌ ISSUES FOUND! Please fix the errors above first.\n\n";
    echo "🛠️ Common fixes:\n";
    echo "- Run: php debug_errors.php\n";
    echo "- Check file permissions: chmod 755 application/cache application/logs\n";
    echo "- Verify all files are properly uploaded\n";
}
echo "\n";
?>