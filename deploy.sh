#!/bin/bash
#
# Deployment Script for Supervisord Monitor
# Usage: sudo bash deploy.sh
#

set -e

echo "======================================"
echo "Supervisord Monitor Deployment Script"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

# Detect OS
if [ -f /etc/redhat-release ]; then
    OS="centos"
    PHP_FPM_SOCKET="/var/run/php-fpm/php-fpm.sock"
    PHP_FPM_SERVICE="php-fpm"
    WEB_USER="nginx"
elif [ -f /etc/debian_version ]; then
    OS="ubuntu"
    # Detect PHP version
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    PHP_FPM_SOCKET="/run/php/php${PHP_VERSION}-fpm.sock"
    PHP_FPM_SERVICE="php${PHP_VERSION}-fpm"
    WEB_USER="www-data"
else
    echo -e "${RED}Unsupported OS${NC}"
    exit 1
fi

echo -e "${GREEN}Detected OS: $OS${NC}"
echo -e "${GREEN}PHP-FPM Socket: $PHP_FPM_SOCKET${NC}"
echo -e "${GREEN}Web User: $WEB_USER${NC}"
echo ""

# Get deployment path
read -p "Enter deployment path [/var/www/supervisord-monitor]: " DEPLOY_PATH
DEPLOY_PATH=${DEPLOY_PATH:-/var/www/supervisord-monitor}

# Get domain/IP
read -p "Enter server domain or IP [localhost]: " SERVER_NAME
SERVER_NAME=${SERVER_NAME:-localhost}

# Confirm
echo ""
echo -e "${YELLOW}Deployment Configuration:${NC}"
echo "  Deploy Path: $DEPLOY_PATH"
echo "  Server Name: $SERVER_NAME"
echo "  PHP-FPM Socket: $PHP_FPM_SOCKET"
echo "  Web User: $WEB_USER"
echo ""
read -p "Continue with deployment? (y/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# Step 1: Check PHP-FPM
echo ""
echo "Step 1: Checking PHP-FPM..."
if systemctl is-active --quiet $PHP_FPM_SERVICE; then
    echo -e "${GREEN}✓ PHP-FPM is running${NC}"
else
    echo -e "${RED}✗ PHP-FPM is not running${NC}"
    echo "Starting PHP-FPM..."
    systemctl start $PHP_FPM_SERVICE
    systemctl enable $PHP_FPM_SERVICE
fi

# Check socket
if [ -S "$PHP_FPM_SOCKET" ]; then
    echo -e "${GREEN}✓ PHP-FPM socket found${NC}"
else
    echo -e "${RED}✗ PHP-FPM socket not found at $PHP_FPM_SOCKET${NC}"
    echo "Please check your PHP-FPM configuration"
    exit 1
fi

# Step 2: Create deployment directory
echo ""
echo "Step 2: Setting up deployment directory..."
CURRENT_DIR=$(pwd)

if [ "$CURRENT_DIR" != "$DEPLOY_PATH" ]; then
    if [ -d "$DEPLOY_PATH" ]; then
        echo -e "${YELLOW}Directory $DEPLOY_PATH already exists${NC}"
        read -p "Backup and overwrite? (y/n): " -n 1 -r
        echo ""
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            BACKUP_NAME="supervisord-monitor-backup-$(date +%Y%m%d-%H%M%S)"
            mv "$DEPLOY_PATH" "/tmp/$BACKUP_NAME"
            echo -e "${GREEN}✓ Backup created: /tmp/$BACKUP_NAME${NC}"
        else
            echo "Using existing directory"
        fi
    fi
    
    mkdir -p "$DEPLOY_PATH"
    cp -r ./* "$DEPLOY_PATH/"
    echo -e "${GREEN}✓ Files copied to $DEPLOY_PATH${NC}"
else
    echo -e "${GREEN}✓ Already in deployment directory${NC}"
fi

# Step 3: Set permissions
echo ""
echo "Step 3: Setting permissions..."
chown -R $WEB_USER:$WEB_USER "$DEPLOY_PATH"
find "$DEPLOY_PATH" -type d -exec chmod 755 {} \;
find "$DEPLOY_PATH" -type f -exec chmod 644 {} \;

# Create logs directory if doesn't exist
if [ ! -d "$DEPLOY_PATH/application/logs" ]; then
    mkdir -p "$DEPLOY_PATH/application/logs"
fi
chmod -R 775 "$DEPLOY_PATH/application/logs"
chown -R $WEB_USER:$WEB_USER "$DEPLOY_PATH/application/logs"

echo -e "${GREEN}✓ Permissions set${NC}"

# Step 4: Configure Nginx
echo ""
echo "Step 4: Configuring Nginx..."

# Create nginx config from template
cat > /etc/nginx/conf.d/supervisor-monitor.conf << EOF
server {
    listen 80;
    server_name $SERVER_NAME;
    
    root $DEPLOY_PATH/public_html;
    index index.php index.html;
    
    # Logging
    access_log /var/log/nginx/supervisor-monitor-access.log;
    error_log /var/log/nginx/supervisor-monitor-error.log;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Main location
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    # PHP-FPM configuration
    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_pass unix:$PHP_FPM_SOCKET;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        
        # PHP-FPM timeouts
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_connect_timeout 300;
        
        # Buffer settings
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }
    
    # Deny access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    # Deny access to application folder
    location ~ ^/(application|system)/ {
        deny all;
        return 404;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
}
EOF

echo -e "${GREEN}✓ Nginx config created${NC}"

# Test nginx config
echo ""
echo "Testing Nginx configuration..."
if nginx -t; then
    echo -e "${GREEN}✓ Nginx config is valid${NC}"
    
    # Reload nginx
    systemctl reload nginx
    echo -e "${GREEN}✓ Nginx reloaded${NC}"
else
    echo -e "${RED}✗ Nginx config has errors${NC}"
    exit 1
fi

# Step 5: Update application config
echo ""
echo "Step 5: Updating application configuration..."

# Update base_url in config.php
if [ -f "$DEPLOY_PATH/application/config/config.php" ]; then
    sed -i "s|http://localhost|http://$SERVER_NAME|g" "$DEPLOY_PATH/application/config/config.php"
    echo -e "${GREEN}✓ Updated base_url in config.php${NC}"
fi

# Step 6: Final checks
echo ""
echo "Step 6: Running final checks..."

# Check PHP-FPM
if systemctl is-active --quiet $PHP_FPM_SERVICE; then
    echo -e "${GREEN}✓ PHP-FPM is running${NC}"
else
    echo -e "${RED}✗ PHP-FPM is not running${NC}"
fi

# Check Nginx
if systemctl is-active --quiet nginx; then
    echo -e "${GREEN}✓ Nginx is running${NC}"
else
    echo -e "${RED}✗ Nginx is not running${NC}"
fi

# Check permissions
if [ -w "$DEPLOY_PATH/application/logs" ]; then
    echo -e "${GREEN}✓ Logs directory is writable${NC}"
else
    echo -e "${YELLOW}⚠ Logs directory is not writable${NC}"
fi

# Summary
echo ""
echo "======================================"
echo -e "${GREEN}Deployment Complete!${NC}"
echo "======================================"
echo ""
echo "Application URL: http://$SERVER_NAME"
echo "Deployment Path: $DEPLOY_PATH"
echo ""
echo "Next steps:"
echo "1. Edit supervisor configuration:"
echo "   nano $DEPLOY_PATH/application/config/supervisor.php"
echo "   nano $DEPLOY_PATH/application/config/supervisor.server.php"
echo ""
echo "2. Test the application:"
echo "   curl http://$SERVER_NAME"
echo ""
echo "3. Check logs if needed:"
echo "   tail -f /var/log/nginx/supervisor-monitor-error.log"
echo "   tail -f /var/log/php-fpm/error.log"
echo ""
echo "4. View deployment guide:"
echo "   cat $DEPLOY_PATH/DEPLOYMENT_GUIDE.md"
echo ""
echo -e "${GREEN}Happy monitoring! 🚀${NC}"
