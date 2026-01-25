#!/bin/bash

# Supervisor Monitor - Cron Setup Script
# This script sets up background jobs for optimal performance

echo "=== Supervisor Monitor - Performance Optimization Setup ==="

# Create necessary directories
echo "Creating cache directories..."
mkdir -p application/cache/supervisor
mkdir -p application/cache/supervisor/persistent
mkdir -p application/logs

# Set proper permissions
echo "Setting permissions..."
chmod 755 application/cache/supervisor
chmod 755 application/cache/supervisor/persistent
chmod 755 application/logs

# Get current directory
CURRENT_DIR=$(pwd)
PHP_PATH=$(which php)

if [ -z "$PHP_PATH" ]; then
    echo "⚠️  Warning: PHP not found in PATH. Please install PHP CLI."
    PHP_PATH="/usr/bin/php"
fi

echo "PHP Path: $PHP_PATH"
echo "Project Path: $CURRENT_DIR"

# Create crontab entries
echo ""
echo "=== Recommended Crontab Entries ==="
echo "Add these entries to your crontab (crontab -e):"
echo ""

echo "# Supervisor Monitor - Background data updates"
echo "*/1 * * * * $PHP_PATH $CURRENT_DIR/public_html/index.php cron update_supervisor_data >> $CURRENT_DIR/application/logs/cron.log 2>&1"
echo ""

echo "# Supervisor Monitor - Health check every 2 minutes"  
echo "*/2 * * * * $PHP_PATH $CURRENT_DIR/public_html/index.php cron health_check >> $CURRENT_DIR/application/logs/health.log 2>&1"
echo ""

echo "# Supervisor Monitor - Performance report every 5 minutes"
echo "*/5 * * * * $PHP_PATH $CURRENT_DIR/public_html/index.php cron performance_report >> $CURRENT_DIR/application/logs/performance.log 2>&1"
echo ""

echo "# Cleanup old cache files daily at 2 AM"
echo "0 2 * * * find $CURRENT_DIR/application/cache/supervisor -name '*.cache' -mtime +1 -delete"
echo ""

# Create systemd service file (optional)
cat > supervisor-monitor.service << EOF
[Unit]
Description=Supervisor Monitor Background Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=$CURRENT_DIR/public_html
ExecStart=$PHP_PATH index.php cron update_supervisor_data
Restart=always
RestartSec=60

[Install]
WantedBy=multi-user.target
EOF

echo "=== Optional: Systemd Service ==="
echo "Systemd service file created: supervisor-monitor.service"
echo "To install: sudo cp supervisor-monitor.service /etc/systemd/system/"
echo "Then run: sudo systemctl enable supervisor-monitor && sudo systemctl start supervisor-monitor"
echo ""

# Create logrotate configuration
cat > supervisor-monitor.logrotate << EOF
$CURRENT_DIR/application/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
EOF

echo "=== Log Rotation ==="
echo "Logrotate config created: supervisor-monitor.logrotate"
echo "To install: sudo cp supervisor-monitor.logrotate /etc/logrotate.d/supervisor-monitor"
echo ""

# Test the cron jobs
echo "=== Testing Cron Jobs ==="
echo "Testing background data update..."
$PHP_PATH public_html/index.php cron update_supervisor_data

echo ""
echo "Testing health check..."
$PHP_PATH public_html/index.php cron health_check

echo ""
echo "=== Setup Complete! ==="
echo ""
echo "📊 Performance Features Enabled:"
echo "✅ Parallel XML-RPC requests"
echo "✅ Smart caching system" 
echo "✅ Connection pooling"
echo "✅ Background data updates"
echo "✅ Real-time AJAX monitoring"
echo "✅ Automatic error handling & retry"
echo "✅ Performance metrics logging"
echo ""
echo "🚀 Next Steps:"
echo "1. Add the crontab entries shown above"
echo "2. Configure your web server for optimal performance"
echo "3. Monitor application/logs/ for performance metrics"
echo "4. Access your monitoring dashboard with improved speed!"
echo ""
echo "📝 Configuration Files Updated:"
echo "- application/config/supervisor.php (server config + login)"
echo "- application/core/MY_Controller.php (performance optimizations)"
echo "- application/controllers/welcome.php (parallel processing)"
echo "- application/controllers/auth.php (authentication)"
echo "- application/controllers/cron.php (background jobs)"
echo "- application/views/welcome.php (enhanced frontend)"
echo ""