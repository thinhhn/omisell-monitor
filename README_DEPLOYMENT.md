# 🚀 Supervisord Monitor - Deployment Package

## 📦 Package Contents

### Configuration Files
- `supervisord.omisell.com.conf` - Nginx configuration template
- `deploy.sh` - Automated deployment script
- `test_deployment.sh` - Deployment verification script
- `DEPLOYMENT_GUIDE.md` - Detailed deployment documentation

### Application Updates
✅ **All UI improvements included:**
- 3 servers per row (responsive)
- 2-line card headers (server name + IP/version)
- CPU/RAM monitoring with color indicators
- 18+ tooltips for better UX
- Load time & auto-refresh in navbar
- Server grouping (Main Platform / Omni Platform)
- Web/Celery separation

---

## 🎯 Quick Start

### Option 1: Automated Deployment (Recommended)

```bash
# 1. Upload to server
scp -r ./* user@your-server:/tmp/supervisord-monitor/

# 2. SSH to server
ssh user@your-server

# 3. Run deployment script
cd /tmp/supervisord-monitor
sudo bash deploy.sh

# 4. Follow the prompts:
#    - Deployment path: /var/www/supervisord-monitor
#    - Server domain/IP: your-domain.com or IP

# 5. Configure supervisor servers
sudo nano /var/www/supervisord-monitor/application/config/supervisor.php
sudo nano /var/www/supervisord-monitor/application/config/supervisor.server.php

# 6. Test deployment
bash test_deployment.sh http://your-server-ip
```

### Option 2: Manual Deployment

Follow the detailed guide in `DEPLOYMENT_GUIDE.md`

---

## ⚡ Quick Commands

### Test deployment
```bash
bash test_deployment.sh http://your-server
```

### Check logs
```bash
# Nginx logs
sudo tail -f /var/log/nginx/supervisor-monitor-error.log
sudo tail -f /var/log/nginx/supervisor-monitor-access.log

# PHP-FPM logs
sudo tail -f /var/log/php-fpm/error.log
```

### Restart services
```bash
# Restart PHP-FPM
sudo systemctl restart php-fpm

# Reload Nginx
sudo systemctl reload nginx

# Or restart Nginx
sudo systemctl restart nginx
```

### Check service status
```bash
# PHP-FPM status
sudo systemctl status php-fpm

# Nginx status
sudo systemctl status nginx

# Check PHP-FPM socket
ls -la /var/run/php-fpm/php-fpm.sock
# Or for Ubuntu/Debian:
ls -la /run/php/php7.4-fpm.sock
```

---

## 🔧 Configuration

### 1. Application Config
```bash
# Edit base URL and settings
sudo nano /var/www/supervisord-monitor/application/config/config.php
```

### 2. Supervisor Servers
```bash
# Add your supervisor servers
sudo nano /var/www/supervisord-monitor/application/config/supervisor.php

# Configure server details
sudo nano /var/www/supervisord-monitor/application/config/supervisor.server.php
```

Example supervisor config:
```php
$config['servers'] = array(
    'web_001' => array(
        'url' => 'http://192.168.1.10:9001/RPC2',
        'username' => 'admin',
        'password' => 'your_password'
    ),
    'celery_001' => array(
        'url' => 'http://192.168.1.11:9001/RPC2',
        'username' => 'admin',
        'password' => 'your_password'
    ),
);
```

### 3. Nginx Config (if needed)
```bash
sudo nano /etc/nginx/conf.d/supervisor-monitor.conf
```

---

## 🔒 Security Recommendations

### 1. Add Basic Authentication
```bash
# Create password file
sudo htpasswd -c /etc/nginx/.htpasswd admin

# Add to Nginx config
location / {
    auth_basic "Restricted Access";
    auth_basic_user_file /etc/nginx/.htpasswd;
    try_files $uri $uri/ /index.php?$query_string;
}

# Reload Nginx
sudo systemctl reload nginx
```

### 2. IP Whitelisting
Add to Nginx config:
```nginx
location / {
    allow 192.168.1.0/24;  # Your office network
    deny all;
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 3. SSL/TLS (Production)
```bash
# Install certbot
sudo yum install certbot python3-certbot-nginx  # CentOS
sudo apt-get install certbot python3-certbot-nginx  # Ubuntu

# Get certificate
sudo certbot --nginx -d supervisor.yourdomain.com

# Auto-renewal (test)
sudo certbot renew --dry-run
```

---

## 🐛 Troubleshooting

### Error: 502 Bad Gateway
**Cause:** PHP-FPM not running or wrong socket path

**Fix:**
```bash
# Check PHP-FPM
sudo systemctl status php-fpm
sudo systemctl start php-fpm

# Check socket exists
ls -la /var/run/php-fpm/php-fpm.sock

# Verify socket path in Nginx config
grep fastcgi_pass /etc/nginx/conf.d/supervisor-monitor.conf
```

### Error: 404 Not Found
**Cause:** Wrong document root or missing files

**Fix:**
```bash
# Check files exist
ls -la /var/www/supervisord-monitor/public_html/index.php

# Verify root in Nginx config
grep "root" /etc/nginx/conf.d/supervisor-monitor.conf

# Test Nginx config
sudo nginx -t
```

### Error: Permission Denied
**Cause:** Wrong file permissions

**Fix:**
```bash
# Set correct owner (CentOS)
sudo chown -R nginx:nginx /var/www/supervisord-monitor

# Or for Ubuntu/Debian
sudo chown -R www-data:www-data /var/www/supervisord-monitor

# Set correct permissions
sudo find /var/www/supervisord-monitor -type d -exec chmod 755 {} \;
sudo find /var/www/supervisord-monitor -type f -exec chmod 644 {} \;

# Logs directory needs write permission
sudo chmod -R 775 /var/www/supervisord-monitor/application/logs
```

### Error: Cannot connect to Supervisor servers
**Cause:** Network, firewall, or wrong credentials

**Fix:**
```bash
# Test connection from server
curl -v http://supervisor-server:9001/RPC2

# Check firewall
sudo firewall-cmd --list-all
sudo firewall-cmd --add-port=9001/tcp --permanent
sudo firewall-cmd --reload

# Verify supervisor is listening
sudo netstat -tlnp | grep 9001

# Check supervisor config on remote server
sudo cat /etc/supervisord.conf | grep inet_http_server -A 5
```

---

## 📊 Performance Tuning

### PHP-FPM Optimization
```bash
sudo nano /etc/php-fpm.d/www.conf

# Adjust based on your server resources
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

sudo systemctl restart php-fpm
```

### Nginx Caching (Optional)
```nginx
# Add to nginx config
fastcgi_cache_path /var/cache/nginx levels=1:2 keys_zone=phpcache:100m inactive=60m;

location ~ \.php$ {
    fastcgi_cache phpcache;
    fastcgi_cache_valid 200 5m;
    fastcgi_cache_bypass $http_pragma;
    add_header X-Cache-Status $upstream_cache_status;
    # ... other settings
}
```

---

## ✅ Post-Deployment Checklist

- [ ] PHP-FPM is running: `systemctl status php-fpm`
- [ ] Nginx is running: `systemctl status nginx`
- [ ] Nginx config is valid: `nginx -t`
- [ ] Website is accessible: `curl http://your-server`
- [ ] Can login to application
- [ ] Supervisor servers are configured
- [ ] Can view server status and processes
- [ ] Tooltips are working (hover on elements)
- [ ] Auto-refresh countdown is working
- [ ] Can start/stop/restart processes
- [ ] Logs directory is writable
- [ ] Firewall ports are open (80, 443, 9001)
- [ ] SSL certificate installed (if using HTTPS)
- [ ] Basic auth or IP whitelist configured (recommended)
- [ ] Backup strategy in place

---

## 📚 Additional Resources

- **Full Documentation:** `DEPLOYMENT_GUIDE.md`
- **Nginx Config Template:** `supervisord.omisell.com.conf`
- **Automated Deployment:** `deploy.sh`
- **Test Suite:** `test_deployment.sh`

---

## 🆘 Support

If you encounter issues:

1. **Check logs:**
   - Nginx: `/var/log/nginx/supervisor-monitor-error.log`
   - PHP-FPM: `/var/log/php-fpm/error.log`
   - Application: `/var/www/supervisord-monitor/application/logs/`

2. **Run tests:**
   ```bash
   bash test_deployment.sh http://your-server
   ```

3. **Verify services:**
   ```bash
   sudo systemctl status php-fpm
   sudo systemctl status nginx
   ```

4. **Check configuration:**
   ```bash
   sudo nginx -t
   php -v
   ```

---

## 🎉 Success!

Once deployed, you'll have:
- ✅ Modern responsive UI with 3 servers per row
- ✅ Real-time monitoring with auto-refresh
- ✅ CPU/RAM statistics with color indicators
- ✅ Intuitive tooltips on all actions
- ✅ Clean navbar with load time and countdown
- ✅ Organized server groups (Main/Omni Platform)
- ✅ Separate Web and Celery server sections
- ✅ Process control (start/stop/restart)
- ✅ Mobile-friendly responsive design

**Enjoy your improved Supervisord Monitor! 🚀**
