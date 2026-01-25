# Hướng dẫn Deploy Supervisord Monitor với PHP-FPM

## 1. Yêu cầu hệ thống

### PHP Requirements:
- PHP >= 7.0 (khuyến nghị 7.4 hoặc 8.x)
- PHP-FPM đã cài đặt
- PHP Extensions cần thiết:
  - php-xml
  - php-xmlrpc
  - php-curl
  - php-json
  - php-mbstring

### Server Requirements:
- Nginx
- Supervisor daemon đang chạy
- Quyền truy cập vào các supervisor servers cần monitor

## 2. Cài đặt PHP-FPM (nếu chưa có)

### CentOS/RHEL:
```bash
sudo yum install php-fpm php-xml php-xmlrpc php-curl php-json php-mbstring
sudo systemctl start php-fpm
sudo systemctl enable php-fpm
```

### Ubuntu/Debian:
```bash
sudo apt-get install php-fpm php-xml php-xmlrpc php-curl php-json php-mbstring
sudo systemctl start php7.4-fpm  # Thay 7.4 bằng version của bạn
sudo systemctl enable php7.4-fpm
```

## 3. Kiểm tra PHP-FPM Socket

```bash
# Kiểm tra socket file có tồn tại không
ls -la /var/run/php-fpm/php-fpm.sock

# Hoặc
ls -la /run/php/php7.4-fpm.sock  # Ubuntu/Debian

# Kiểm tra PHP-FPM đang chạy
sudo systemctl status php-fpm
```

**Lưu ý:** Đường dẫn socket có thể khác nhau:
- CentOS/RHEL: `/var/run/php-fpm/php-fpm.sock`
- Ubuntu/Debian: `/run/php/php7.4-fpm.sock` (thay 7.4 bằng version của bạn)

## 4. Deploy ứng dụng

### Bước 1: Upload code lên server
```bash
# Upload toàn bộ thư mục lên server
cd /var/www/
sudo git clone <your-repo> supervisord-monitor
# Hoặc upload qua SCP/SFTP

# Set permissions
sudo chown -R nginx:nginx /var/www/supervisord-monitor
sudo chmod -R 755 /var/www/supervisord-monitor
```

### Bước 2: Cấu hình ứng dụng
```bash
cd /var/www/supervisord-monitor

# Edit config
sudo nano application/config/config.php
# Sửa base_url thành domain/IP của bạn

# Edit supervisor config
sudo nano application/config/supervisor.php
# Thêm/sửa thông tin các supervisor servers

# Edit supervisor server config
sudo nano application/config/supervisor.server.php
# Cấu hình chi tiết cho từng server
```

### Bước 3: Cấu hình Nginx
```bash
# Copy file config
sudo cp nginx_supervisor_monitor.conf /etc/nginx/conf.d/supervisor-monitor.conf

# Sửa file config
sudo nano /etc/nginx/conf.d/supervisor-monitor.conf
```

**Cần thay đổi:**
1. `server_name`: Domain hoặc IP của bạn
2. `root`: Đường dẫn tuyệt đối đến thư mục public_html
3. `fastcgi_pass`: Đường dẫn socket PHP-FPM đúng của hệ thống

**Ví dụ:**
```nginx
server_name 192.168.1.100;  # Hoặc monitor.yourdomain.com
root /var/www/supervisord-monitor/public_html;
fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;  # Hoặc /run/php/php7.4-fpm.sock
```

### Bước 4: Test và reload Nginx
```bash
# Test cấu hình Nginx
sudo nginx -t

# Nếu OK, reload Nginx
sudo systemctl reload nginx

# Hoặc restart
sudo systemctl restart nginx
```

## 5. Kiểm tra logs nếu có lỗi

```bash
# Nginx error log
sudo tail -f /var/log/nginx/supervisor-monitor-error.log

# Nginx access log
sudo tail -f /var/log/nginx/supervisor-monitor-access.log

# PHP-FPM error log
sudo tail -f /var/log/php-fpm/error.log

# PHP-FPM pool log (CentOS/RHEL)
sudo tail -f /var/log/php-fpm/www-error.log
```

## 6. Troubleshooting

### Lỗi: "502 Bad Gateway"
**Nguyên nhân:** Không kết nối được PHP-FPM

**Giải pháp:**
```bash
# 1. Kiểm tra PHP-FPM đang chạy
sudo systemctl status php-fpm

# 2. Kiểm tra socket file tồn tại
ls -la /var/run/php-fpm/php-fpm.sock

# 3. Kiểm tra quyền socket
sudo chmod 666 /var/run/php-fpm/php-fpm.sock

# 4. Kiểm tra user/group trong PHP-FPM config
sudo nano /etc/php-fpm.d/www.conf
# Tìm và sửa:
# user = nginx
# group = nginx
# listen.owner = nginx
# listen.group = nginx

# 5. Restart PHP-FPM
sudo systemctl restart php-fpm
```

### Lỗi: "File not found" hoặc "404"
**Nguyên nhân:** Đường dẫn root không đúng

**Giải pháp:**
```bash
# Kiểm tra đường dẫn
ls -la /var/www/supervisord-monitor/public_html/index.php

# Sửa lại root trong nginx config
root /var/www/supervisord-monitor/public_html;
```

### Lỗi: "Permission denied"
**Nguyên nhân:** Quyền file/folder không đúng

**Giải pháp:**
```bash
# Set owner
sudo chown -R nginx:nginx /var/www/supervisord-monitor

# Set permissions
sudo find /var/www/supervisord-monitor -type d -exec chmod 755 {} \;
sudo find /var/www/supervisord-monitor -type f -exec chmod 644 {} \;

# Nếu có writable folders (logs, cache, etc)
sudo chmod -R 775 /var/www/supervisord-monitor/application/logs
```

### Lỗi: XMLRPC connection failed
**Nguyên nhân:** Không kết nối được supervisor servers

**Giải pháp:**
```bash
# Test kết nối từ server
curl -v http://supervisor-server:9001/RPC2

# Kiểm tra firewall
sudo firewall-cmd --list-all
sudo firewall-cmd --add-port=9001/tcp --permanent
sudo firewall-cmd --reload

# Kiểm tra supervisor config trên remote server
sudo nano /etc/supervisord.conf
# Đảm bảo có:
# [inet_http_server]
# port=*:9001
# username=admin
# password=password123
```

## 7. Bảo mật

### Thêm Basic Authentication (Optional)
```bash
# Tạo htpasswd file
sudo yum install httpd-tools  # CentOS
sudo apt-get install apache2-utils  # Ubuntu

sudo htpasswd -c /etc/nginx/.htpasswd admin

# Thêm vào nginx config
location / {
    auth_basic "Restricted Access";
    auth_basic_user_file /etc/nginx/.htpasswd;
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Giới hạn IP truy cập (Optional)
```nginx
location / {
    allow 192.168.1.0/24;  # Cho phép subnet này
    allow 10.0.0.5;        # Cho phép IP cụ thể
    deny all;              # Chặn tất cả còn lại
    
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 8. SSL/TLS (Khuyến nghị cho production)

### Sử dụng Let's Encrypt (Free SSL)
```bash
# Cài đặt certbot
sudo yum install certbot python3-certbot-nginx  # CentOS
sudo apt-get install certbot python3-certbot-nginx  # Ubuntu

# Tạo SSL certificate
sudo certbot --nginx -d supervisor-monitor.yourdomain.com

# Auto-renew
sudo certbot renew --dry-run
```

## 9. Monitoring & Maintenance

### Auto-reload khi có thay đổi
```bash
# Thêm vào crontab
crontab -e

# Reload nginx mỗi ngày lúc 3 AM
0 3 * * * /usr/sbin/nginx -s reload
```

### Backup configuration
```bash
# Backup config files
sudo tar -czf supervisord-monitor-config-$(date +%Y%m%d).tar.gz \
  /var/www/supervisord-monitor/application/config/ \
  /etc/nginx/conf.d/supervisor-monitor.conf
```

## 10. Performance Tuning

### PHP-FPM Pool Settings
```bash
sudo nano /etc/php-fpm.d/www.conf

# Tối ưu cho production
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# Restart sau khi sửa
sudo systemctl restart php-fpm
```

### Nginx Cache (Optional)
```nginx
# Thêm vào nginx config
fastcgi_cache_path /var/cache/nginx levels=1:2 keys_zone=phpcache:100m inactive=60m;

location ~ \.php$ {
    fastcgi_cache phpcache;
    fastcgi_cache_valid 200 10m;
    fastcgi_cache_bypass $http_pragma $http_authorization;
    add_header X-Cache-Status $upstream_cache_status;
    
    # ... các setting khác
}
```

## 11. Checklist trước khi go live

- [ ] PHP-FPM đang chạy và accessible
- [ ] Nginx config đã test OK (`nginx -t`)
- [ ] Permissions files/folders đã đúng
- [ ] Config supervisor servers đã đúng và test được kết nối
- [ ] Logs folders có quyền write
- [ ] SSL certificate đã cài (nếu dùng HTTPS)
- [ ] Firewall đã mở ports cần thiết
- [ ] Basic auth hoặc IP whitelist đã setup (nếu cần)
- [ ] Backup config đã có
- [ ] Monitoring/alerting đã setup

---

## Liên hệ & Support

Nếu gặp vấn đề, kiểm tra:
1. Nginx error logs
2. PHP-FPM error logs
3. Application logs (nếu có)
4. Supervisor connection từ server

Good luck with your deployment! 🚀
