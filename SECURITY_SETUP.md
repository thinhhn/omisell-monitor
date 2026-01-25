# Hướng Dẫn Cấu Hình Bảo Mật

## 🔒 Các Biện Pháp Bảo Mật Đã Được Triển Khai

### 1. Input Validation & Sanitization
- ✅ Whitelist cho queue names, server names, process names
- ✅ Regex validation chỉ cho phép alphanumeric và underscore
- ✅ Escapeshellarg() để prevent command injection
- ✅ Length limits cho tất cả inputs

### 2. Security Configuration
- ✅ File `application/config/security.php` - Whitelist và quy tắc bảo mật
- ✅ File `application/config/remote_servers.php` - SSH credentials (PHẢI BẢO MẬT)
- ✅ Security Helper Library - Tất cả validation functions

### 3. Audit Logging
- ✅ Tất cả actions được log vào `application/logs/security_audit.log`
- ✅ Log bao gồm: timestamp, user, IP, action, parameters

### 4. Rate Limiting
- ✅ Max 10 actions/minute, 100 actions/hour per user
- ✅ Prevent brute force và abuse

### 5. Script Security
- ✅ SSH scripts kiểm tra key file permissions (phải là 600 hoặc 400)
- ✅ Input validation trong bash scripts
- ✅ Support environment variables thay vì hardcode credentials

---

## 🚀 Các Bước Cần Thực Hiện Để Tăng Cường Bảo Mật

### BƯỚC 1: Bảo Mật SSH Key
```bash
# Đảm bảo SSH key có permissions an toàn
chmod 600 /home/thinhhn/.ssh/id_rsa

# Chỉ user owner có thể đọc
chown thinhhn:thinhhn /home/thinhhn/.ssh/id_rsa
```

### BƯỚC 2: Cấu Hình Git Ignore
Thêm vào `.gitignore`:
```
application/config/remote_servers.php
application/logs/security_audit.log
application/logs/*.log
```

### BƯỚC 3: Bảo Vệ File Config
```bash
# Chỉ web server user có thể đọc
chmod 600 application/config/remote_servers.php
chown www-data:www-data application/config/remote_servers.php

chmod 600 application/config/security.php
chown www-data:www-data application/config/security.php
```

### BƯỚC 4: Sử Dụng Environment Variables (Recommended)
Thay vì hardcode trong scripts, set environment variables:

**Cách 1: Trong `.env` file (nếu dùng)**
```bash
REMOTE_CELERY_IP=10.148.0.26
REMOTE_CELERY_USER=thinhhn
REMOTE_CELERY_KEY=/home/thinhhn/.ssh/id_rsa
REMOTE_CELERY_CODE_DIR=/data/code/omisell-backend
REMOTE_CELERY_VENV_PYTHON=/data/venv/omisell3.11/bin/python
REMOTE_CELERY_VENV_CELERY=/data/venv/omisell3.11/bin/celery
```

**Cách 2: Trong web server config (Apache/Nginx)**
```apache
# Apache .htaccess hoặc vhost config
SetEnv REMOTE_CELERY_IP "10.148.0.26"
SetEnv REMOTE_CELERY_USER "thinhhn"
SetEnv REMOTE_CELERY_KEY "/home/thinhhn/.ssh/id_rsa"
```

```nginx
# Nginx config
location ~ \.php$ {
    fastcgi_param REMOTE_CELERY_IP "10.148.0.26";
    fastcgi_param REMOTE_CELERY_USER "thinhhn";
    fastcgi_param REMOTE_CELERY_KEY "/home/thinhhn/.ssh/id_rsa";
}
```

### BƯỚC 5: Cấu Hình Whitelist
Chỉnh sửa `application/config/security.php`:

```php
// Thêm/bớt queue names được phép
$config['allowed_queue_names'] = [
    'omisell_report',
    'omisell_notification',
    // ... thêm queue names của bạn
];

// Thêm/bớt server names được phép
$config['allowed_servers'] = [
    'celery_001',
    'celery_002',
    // ... thêm server names của bạn
];

// Giới hạn IP được phép thực hiện actions nguy hiểm (optional)
$config['admin_ip_whitelist'] = [
    '127.0.0.1',
    '10.148.0.26',  // Thêm IP của admin
];
```

### BƯỚC 6: Tạo Read-Only Mode (Optional)
Để disable tất cả thao tác kill/purge, set trong config:

```php
// application/config/remote_servers.php
$config['read_only_mode'] = true;
```

Sau đó thêm check trong `events.php`:
```php
public function kill($process_name)
{
    // Check read-only mode
    if ($this->config->item('read_only_mode')) {
        $this->session->set_flashdata('error', 'System is in read-only mode');
        redirect('events');
        return;
    }
    // ... rest of code
}
```

### BƯỚC 7: Review Audit Logs Định Kỳ
```bash
# Xem audit log
tail -f application/logs/security_audit.log

# Tìm các attempt đáng ngờ
grep "BLOCKED" application/logs/security_audit.log

# Rotate logs hàng tháng
logrotate -f /path/to/logrotate.conf
```

---

## ⚠️ NHỮNG ĐIỀU TUYỆT ĐỐI KHÔNG NÊN LÀM

1. ❌ **KHÔNG** commit SSH key hoặc credentials vào Git
2. ❌ **KHÔNG** để file config có permissions 777 hoặc 666
3. ❌ **KHÔNG** hardcode passwords/keys trong code
4. ❌ **KHÔNG** disable input validation để "debug"
5. ❌ **KHÔNG** expose audit logs ra public
6. ❌ **KHÔNG** cho phép user input trực tiếp vào shell commands mà không validate

---

## 🔍 Kiểm Tra Bảo Mật

### Test 1: Command Injection
Thử kill process với tên: `test"; rm -rf / #`
- ✅ Kết quả mong đợi: Blocked by validation

### Test 2: Invalid Queue Name
Thử kill process với tên: `invalid_queue_not_in_whitelist`
- ✅ Kết quả mong đợi: "Queue name not in whitelist"

### Test 3: Rate Limiting
Thử kill 15 processes trong 1 phút
- ✅ Kết quả mong đợi: Blocked sau 10 lần

### Test 4: Audit Log
Kiểm tra xem mọi action có được log không
```bash
tail -20 application/logs/security_audit.log
```

---

## 📞 Liên Hệ
Nếu phát hiện lỗ hổng bảo mật, vui lòng báo ngay cho team security.

**Version:** 1.0  
**Last Updated:** 2026-01-25
