# 🚀 Supervisor Monitor - Local Development Setup

## Quick Start

### 📋 Prerequisites
- **PHP 7.4+** with extensions: `curl`, `xml`, `json`
- **Web browser** (Chrome, Firefox, Safari)

### ⚡ Option 1: One-Click Start

#### Linux/macOS:
```bash
./start_local_server.sh
```

#### Windows:
```batch
start_local_server.bat
```

### 🔧 Option 2: Manual Setup

1. **Install PHP** (if not already installed):
   ```bash
   # Ubuntu/Debian
   sudo apt install php php-cli php-curl php-xml
   
   # macOS (with Homebrew)
   brew install php
   
   # Windows: Download from https://www.php.net/downloads
   ```

2. **Start the server**:
   ```bash
   cd public_html
   php -S localhost:8000
   ```

3. **Open browser**: http://localhost:8000

## 🔑 Login Credentials

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | Administrator |
| `supervisor` | `supervisor123` | Supervisor |
| `monitor` | `monitor123` | Monitor |

## 📊 Available Features

### 🎯 Core Features
- ✅ Real-time supervisor process monitoring
- ✅ Multi-server management
- ✅ Performance dashboard with metrics
- ✅ Auto-refresh with pause/resume controls
- ✅ User authentication system

### ⚡ Performance Features
- ✅ Parallel XML-RPC requests
- ✅ Smart caching system (30s-1h TTL)
- ✅ Connection pooling & retry mechanism
- ✅ Background data updates
- ✅ AJAX real-time updates

### 🛡️ Security Features
- ✅ Session-based authentication
- ✅ Encrypted session cookies
- ✅ Session timeout management
- ✅ CSRF protection ready

## 🏗️ Project Structure

```
supervisor-monitor/
├── application/
│   ├── controllers/       # Main application logic
│   │   ├── auth.php      # Authentication system
│   │   ├── welcome.php   # Main dashboard
│   │   └── cron.php      # Background jobs
│   ├── core/
│   │   └── MY_Controller.php  # Enhanced base controller
│   ├── config/
│   │   ├── supervisor.php     # Supervisor & login config
│   │   └── config.php         # CodeIgniter main config
│   └── views/
│       ├── welcome.php        # Main dashboard view
│       └── auth/login.php     # Login form
├── public_html/          # Web root
│   ├── index.php        # CodeIgniter entry point
│   ├── css/            # Bootstrap & custom styles
│   ├── js/             # jQuery & Bootstrap JS
│   └── img/            # Icons and images
└── system/              # CodeIgniter framework
```

## 🔧 Configuration

### 📡 Add Your Supervisor Servers
Edit `application/config/supervisor.php`:

```php
$config['supervisor_servers'] = [
    'your_server' => [
        'url' => 'http://your-server-ip/RPC2',
        'port' => '9001',
        'username' => 'your_username',
        'password' => 'your_password'
    ]
];
```

### 👥 Manage Login Accounts
Edit `application/config/supervisor.php`:

```php
$config['login_accounts'] = [
    'your_username' => 'your_password',
    'admin' => 'new_secure_password'
];
```

## 🚀 Background Jobs (Optional)

For production environments, set up cron jobs:

```bash
# Update supervisor data every minute
*/1 * * * * php /path/to/project/public_html/index.php cron updateSupervisorData

# Health check every 2 minutes  
*/2 * * * * php /path/to/project/public_html/index.php cron healthCheck

# Performance report every 5 minutes
*/5 * * * * php /path/to/project/public_html/index.php cron performanceReport
```

## 🐛 Troubleshooting

### Common Issues:

1. **"Session class requires encryption key"**:
   - ✅ **Fixed**: Encryption key already set in config.php

2. **"Permission denied" errors**:
   ```bash
   chmod 755 application/cache/supervisor
   chmod 755 application/logs
   ```

3. **"Cannot connect to supervisor server"**:
   - Check if supervisord is running
   - Verify server URLs and credentials in config
   - Check firewall settings

4. **PHP version issues**:
   ```bash
   php --version  # Should be 7.4+
   ```

## 🌐 Production Deployment

1. **Web Server**: Apache/Nginx configuration
2. **HTTPS**: SSL certificate setup  
3. **Caching**: Redis/Memcached integration
4. **Monitoring**: Log rotation and monitoring
5. **Backup**: Database and config backups

## 📞 Support

- **Documentation**: Check inline code comments
- **Logs**: `application/logs/` directory
- **Cache**: `application/cache/supervisor/` directory
- **Performance**: Built-in performance metrics

---

🎉 **Happy Monitoring!** Your supervisor dashboard is now ready for local development.