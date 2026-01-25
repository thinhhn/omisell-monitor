<?php
/**
 * Debug Script - Check for common errors
 */

echo "🔍 SUPERVISOR MONITOR - ERROR DEBUGGING\n";
echo "======================================\n\n";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check PHP version
echo "1. PHP Environment:\n";
echo "   Version: " . PHP_VERSION . "\n";
echo "   SAPI: " . php_sapi_name() . "\n";

// Check required extensions
$required_extensions = ['curl', 'xml', 'json', 'mbstring'];
echo "\n2. Required Extensions:\n";
foreach ($required_extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "   $status $ext\n";
}

// Check file permissions
echo "\n3. File Permissions:\n";
$files_to_check = [
    'application/config/config.php',
    'application/config/supervisor.php',
    'application/controllers/welcome.php',
    'application/controllers/auth.php',
    'application/controllers/control.php',
    'application/core/MY_Controller.php',
    'application/views/welcome.php',
    'application/views/auth/login.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "   ✅ $file ($perms)\n";
    } else {
        echo "   ❌ $file - MISSING\n";
    }
}

// Check directories
echo "\n4. Directory Permissions:\n";
$dirs_to_check = [
    'application/cache',
    'application/cache/supervisor', 
    'application/logs',
    'application/views/auth'
];

foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? 'writable' : 'not writable';
        echo "   ✅ $dir ($perms, $writable)\n";
    } else {
        echo "   ❌ $dir - MISSING\n";
        @mkdir($dir, 0755, true);
        if (is_dir($dir)) {
            echo "   ✅ $dir - CREATED\n";
        }
    }
}

// Test basic CodeIgniter loading
echo "\n5. CodeIgniter Test:\n";
try {
    if (file_exists('public_html/index.php')) {
        echo "   ✅ CodeIgniter entry point exists\n";
        
        // Test if we can include config files
        if (file_exists('application/config/config.php')) {
            $config_content = file_get_contents('application/config/config.php');
            if (strpos($config_content, 'base_url') !== false) {
                echo "   ✅ Config file is readable\n";
            } else {
                echo "   ❌ Config file format issue\n";
            }
        }
    } else {
        echo "   ❌ CodeIgniter entry point missing\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Check for syntax errors in key files
echo "\n6. PHP Syntax Check:\n";
$php_files_to_check = [
    'application/controllers/welcome.php',
    'application/controllers/auth.php', 
    'application/controllers/control.php',
    'application/core/MY_Controller.php'
];

foreach ($php_files_to_check as $file) {
    if (file_exists($file)) {
        $output = [];
        $return_code = 0;
        exec("php -l \"$file\" 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "   ✅ $file - syntax OK\n";
        } else {
            echo "   ❌ $file - syntax error:\n";
            echo "      " . implode("\n      ", $output) . "\n";
        }
    }
}

// Check .htaccess
echo "\n7. Web Server Config:\n";
if (file_exists('public_html/.htaccess')) {
    echo "   ✅ .htaccess exists\n";
    $htaccess_content = file_get_contents('public_html/.htaccess');
    if (strpos($htaccess_content, 'RewriteEngine') !== false) {
        echo "   ✅ URL rewriting enabled\n";
    } else {
        echo "   ⚠️ URL rewriting may not be configured\n";
    }
} else {
    echo "   ⚠️ .htaccess missing - creating basic one\n";
    $basic_htaccess = "RewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^(.*)$ index.php/\$1 [L]";
    file_put_contents('public_html/.htaccess', $basic_htaccess);
}

echo "\n8. Common Issues & Solutions:\n";
echo "=============================\n\n";

echo "❌ If you see 'Class not found' errors:\n";
echo "   - Check file paths are correct\n";
echo "   - Verify class names match file names\n";
echo "   - Check for PHP syntax errors above\n\n";

echo "❌ If you see 'Session' errors:\n";
echo "   - Check encryption_key is set in config.php\n";
echo "   - Verify session directories are writable\n\n";

echo "❌ If you see 'Database' errors:\n";
echo "   - This app doesn't use database\n";
echo "   - Check if autoload is trying to load database\n\n";

echo "❌ If you see '404 Not Found':\n";
echo "   - Check .htaccess configuration\n";
echo "   - Verify web server document root\n";
echo "   - Check routes.php configuration\n\n";

echo "❌ If you see 'XML-RPC' errors:\n";
echo "   - Run: php debug_supervisor_connection.php\n";
echo "   - Check supervisor server connectivity\n\n";

echo "🛠️ Quick Fixes:\n";
echo "   chmod 755 application/cache application/logs\n";
echo "   php -S localhost:8000 -t public_html\n";
echo "   Check error logs: tail -f /var/log/apache2/error.log\n\n";

echo "🔍 For specific errors, please share:\n";
echo "   1. The exact error message\n";
echo "   2. Which page/action triggers it\n";
echo "   3. Web server error logs\n";
echo "   4. Results from this debug script\n\n";

echo "✅ Debug check completed!\n";
?>