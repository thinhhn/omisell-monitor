<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
|--------------------------------------------------------------------------
| Environment Detection and Configuration
|--------------------------------------------------------------------------
|
| This file handles different configurations for different environments
|
*/

// Detect environment based on server characteristics
function detect_environment()
{
    // Check if running on localhost
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        
        // Local development
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            return 'local';
        }
        
        // Production or staging
        return 'production';
    }
    
    // CLI environment (for cron jobs)
    if (php_sapi_name() === 'cli') {
        return 'cli';
    }
    
    return 'production';
}

// Set environment-specific configurations
$environment = detect_environment();

switch ($environment) {
    case 'local':
        // Local development settings
        $config['base_url'] = 'http://localhost:8000/';
        $config['log_threshold'] = 4; // All messages
        $config['supervisor_debug'] = true;
        break;
        
    case 'production':
        // Production settings - auto-detect base URL
        $config['base_url'] = '';
        $config['log_threshold'] = 1; // Error messages only
        $config['supervisor_debug'] = false;
        break;
        
    case 'cli':
        // CLI settings for cron jobs
        $config['base_url'] = '';
        $config['log_threshold'] = 2; // Error and debug
        $config['supervisor_debug'] = false;
        break;
}

/* End of file environments.php */
/* Location: ./application/config/environments.php */