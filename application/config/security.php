<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Security Configuration
 * Whitelist và các quy tắc bảo mật cho hệ thống
 */

// Bật/tắt whitelist cho queue names
// false = Cho phép tất cả queue names (chỉ check regex pattern)
// true = Chỉ cho phép các queue trong whitelist
$config['use_queue_whitelist'] = false;

// Whitelist cho các queue names được phép (chỉ dùng khi use_queue_whitelist = true)
// Nếu use_queue_whitelist = false, script sẽ cho phép tất cả queue names
// miễn là chúng pass regex validation (alphanumeric + underscore)
$config['allowed_queue_names'] = [
    'omisell_report',
    'omisell_notification',
    'omisell_shipment_label',
    'omisell_app_flashsale',
    'omisell_shipment_slow',
    'inbound_shopee_create',
    'omisell_inventory_excute_market',
    'omisell_shipment_to_3pf',
    'omisell_inventory_excute_normal',
    'omisell_order_update_after',
    'omisell_inventory_queue_auto',
    'omisell_inventory_excute_market_priority',
    'omisell_webhook_app',
    'unknown'
];

// Whitelist cho các server names được phép
$config['allowed_servers'] = [
    'web_001',
    'web_002',
    'celery_hook',
    'celery_001',
    'celery_002',
    'celery_003',
    'celery_004',
    'celery_005',
    'celery_006',
    'celery_007',
    'web_omni_001',
    'web_omni_002',
    'celery_omni'
];

// Whitelist cho các actions được phép
$config['allowed_actions'] = [
    'start',
    'stop',
    'restart',
    'startall',
    'stopall'
];

// IP whitelist - chỉ cho phép các IP này thực hiện các hành động nguy hiểm
$config['admin_ip_whitelist'] = [
    '127.0.0.1',
    '::1',
    // Thêm IP của admin vào đây
    // '10.148.0.26'
];

// Require authentication for dangerous operations
$config['require_auth_for_kill'] = true;
$config['require_auth_for_restart'] = false;

// Audit log settings
$config['enable_audit_log'] = true;
$config['audit_log_path'] = APPPATH . 'logs/security_audit.log';

// Rate limiting
$config['rate_limit_enabled'] = true;
$config['max_actions_per_minute'] = 10;
$config['max_actions_per_hour'] = 100;
