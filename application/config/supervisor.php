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
	'admin' => 'Admin@123!'
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
                'url' => 'http://10.148.0.2',
                'port' => 9001,
                'username' => 'thinhhn',
                'password' => 'thinh49121'
        ),
        'web_002' => array(
                'url' => 'http://10.148.0.9',
                'port' => 9001,
                'username' => 'thinhhn',
                'password' => 'thinh49121',
		'is_break' => true
	),
	
	// Celery GCP Servers
	'celery_hook' => array(
                'url' => 'http://10.148.0.41',
                'port' => 9001,
                'username' => 'thinhhn',
                'password' => 'thinh49121'
        ),
        'celery_001' => array(
		'url' => 'http://10.148.0.21',
		'port' => 9001,
		'username' => 'thinhhn',
		'password' => 'thinh49121',
		'is_break' => true
	),
        'celery_002' => array(
               'url' => 'http://10.148.0.16',
                'port' => 9001,
               'username' => 'thinhhn',
               'password' => 'thinh49121'
        ),
        'celery_003' => array(
                'url' => 'http://10.148.0.26',
                'port' => 9001,
                'username' => 'thinhhn',
               'password' => 'thinh49121'
        ),
        'celery_004' => array(
                'url' => 'http://10.148.0.40',
                'port' => 9001,
                'username' => 'thinhhn',
                'password' => 'thinh49121',
        ),
	// 'celery_005' => array(
        //       'url' => 'http://10.148.0.17',
        //       'port' => 9001,
        //       'username' => 'thinhhn',
        //       'password' => 'thinh49121'
        // ),
        // 'celery_006' => array(
        //       'url' => 'http://10.148.0.18',
        //       'port' => 9001,
        //       'username' => 'thinhhn',
        //       'password' => 'thinh49121'
        // ),
        // 'celery_007' => array(
        //       'url' => 'http://10.148.0.19',
        //       'port' => 9001,
        //       'username' => 'thinhhn',
        //       'password' => 'thinh49121',
	//       'is_break' => true
        // ),
	
	// Omni servers
	'web_omni_001' => array(
                'url' => 'http://10.148.0.12',
                'port' => 9001,
                'username' => 'thinhhn',
                'password' => 'thinh49121'
        ),
        'web_omni_002' => array(
                'url' => 'http://10.148.0.14',
                'port' => 9001,
                'username' => 'thinhhn',
                'password' => 'thinh49121'
        ),
        'celery_omni' => array(
                'url' => 'http://10.148.0.15',
                'port' => 9001,
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


