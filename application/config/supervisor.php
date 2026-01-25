<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Login Accounts Configuration
|--------------------------------------------------------------------------
|
| Configure the allowed login accounts for accessing the supervisor monitor
| Format: 'username' => 'password'
|
*/
$config['login_accounts'] = array(
	'admin' => 'admin123',
	'supervisor' => 'supervisor123',
	'monitor' => 'monitor123'
);

/*
|--------------------------------------------------------------------------
| Login Settings
|--------------------------------------------------------------------------
*/
$config['enable_login'] = TRUE;
$config['login_timeout'] = 3600; // 1 hour in seconds
$config['redirect_after_login'] = '';

// Dashboard columns. 2 or 3  
$config['supervisor_cols'] = 3;

// Refresh Dashboard every x seconds. 0 to disable
$config['refresh'] = 60;

// Enable or disable Alarm Sound
$config['enable_alarm'] = false;

// Show hostname after server name
$config['show_host'] = true;

$config['supervisor_servers'] = array(
	'web_001' => array(
                'url' => 'http://10.148.0.2/RPC2',
                'port' => '9001',
                'username' => 'thinhhn',
                'password' => 'thinh49121'
        ),
        'web_002' => array(
                'url' => 'http://10.148.0.9/RPC2',
                'port' => '9001',
                'username' => 'thinhhn',
                'password' => 'thinh49121',
		'is_break' => true
	),
	// Web_003 server is commented out
	// 'web_003' => array(
        //        'url' => 'http://10.148.0.50/RPC2',
        //        'port' => '9001',
        //        'username' => 'thinhhn',
        //        'password' => 'thinh49121',
        //        'is_break' => true
        // ),
	
	// Celery GCP Servers
	'celery_hook' => array(
                'url' => 'http://10.148.0.41/RPC2',
                'port' => '9001',
                'username' => 'thinhhn',
                'password' => 'thinh49121'
        ),
        'celery_001' => array(
		'url' => 'http://10.148.0.21/RPC2',
		'port' => '9001',
		'username' => 'thinhhn',
		'password' => 'thinh49121',
		'is_break' => true
	),
        'celery_002' => array(
               'url' => 'http://10.148.0.16/RPC2',
                'port' => '9001',
               'username' => 'thinhhn',
               'password' => 'thinh49121'
        ),
        'celery_003' => array(
                'url' => 'http://10.148.0.26/RPC2',
                'port' => '9001',
                'username' => 'thinhhn',
               'password' => 'thinh49121'
        ),
        'celery_004' => array(
                'url' => 'http://10.148.0.40/RPC2',
                'port' => '9001',
                'username' => 'thinhhn',
                'password' => 'thinh49121',
        ),
	// Commented celery servers 005-007
	// 'celery_005' => array(
        //       'url' => 'http://10.148.0.17/RPC2',
        //       'port' => '9001',
        //       'username' => 'thinhhn',
        //       'password' => 'thinh49121'
        // ),
        // 'celery_006' => array(
        //       'url' => 'http://10.148.0.18/RPC2',
        //       'port' => '9001',
        //       'username' => 'thinhhn',
        //       'password' => 'thinh49121'
        // ),
        // 'celery_007' => array(
        //       'url' => 'http://10.148.0.19/RPC2',
        //       'port' => '9001',
        //       'username' => 'thinhhn',
        //       'password' => 'thinh49121',
	//       'is_break' => true
        // ),
	
	// Azure servers (commented out)
	// 'azure_celery_001' => array(
        //        'url' => 'http://10.20.0.9/RPC2',
        //        'port' => '9001',
        //        'username' => 'thinhhn',
        //        'password' => 'thinh49121'
        // ),
        // 'azure_celery_002' => array(
        //      'url' => 'http://10.20.0.7/RPC2',
        //        'port' => '9001',
        //      'username' => 'thinhhn',
        //      'password' => 'thinh49121'
        // ),
        // 'azure_celery_003' => array(
        //        'url' => 'http://10.148.0.11/RPC2',
        //        'port' => '9001',
        //        'username' => 'thinhhn',
        //        'password' => 'thinh49121',
	//	'is_break' => true
        // ),
	// 'azure_celery_004' => array(
        //        'url' => 'http://10.20.0.10/RPC2',
        //        'port' => '9001',
        //        'username' => 'thinhhn',
        //        'password' => 'thinh49121'
        // ),
        // 'azure_celery_005' => array(
        //      'url' => 'http://10.20.0.12/RPC2',
        //      'port' => '9001',
        //      'username' => 'thinhhn',
        //      'password' => 'thinh49121'
        // ),
        // 'azure_celery_006' => array(
        //        'url' => 'http://10.20.0.13/RPC2',
        //        'port' => '9001',
        //        'username' => 'thinhhn',
        //        'password' => 'thinh49121',
        //        'is_break' => true
        // ),
	
	// Omni servers
	'web_omni_001' => array(
                'url' => 'http://10.148.0.12/RPC2',
                'port' => '9001',
                'username' => 'thinhhn',
                'password' => 'thinh49121'
        ),
        'web_omni_002' => array(
                'url' => 'http://10.148.0.14/RPC2',
                'port' => '9001',
                'username' => 'thinhhn',
                'password' => 'thinh49121'
        ),
        'celery_omni' => array(
                'url' => 'http://10.148.0.15/RPC2',
                'port' => '9001',
                'username' => 'thinhhn',
               'password' => 'thinh49121'
        ),
);

// Set timeout connecting to remote supervisord RPC2 interface
$config['timeout'] = 3;

// Path to Redmine new issue url
$config['redmine_url'] = 'http://redmine.url/path_to_new_issue_url';

// Default Redmine assigne ID
$config['redmine_assigne_id'] = '69';


