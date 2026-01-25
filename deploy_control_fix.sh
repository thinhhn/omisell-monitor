#!/bin/bash
#
# Deploy control.php fix to production
#

echo "======================================"
echo "Deploy Control.php Fix"
echo "======================================"
echo ""

# Get server info
read -p "Enter production server (e.g., user@server): " SERVER
read -p "Enter deployment path [/var/www/supervisord-monitor]: " DEPLOY_PATH
DEPLOY_PATH=${DEPLOY_PATH:-/var/www/supervisord-monitor}

echo ""
echo "Deploying to: $SERVER:$DEPLOY_PATH"
echo ""

# Backup current file
echo "Creating backup on server..."
ssh $SERVER "cp $DEPLOY_PATH/application/controllers/control.php $DEPLOY_PATH/application/controllers/control.php.backup-$(date +%Y%m%d-%H%M%S)"

# Upload new file
echo "Uploading updated control.php..."
scp application/controllers/control.php $SERVER:$DEPLOY_PATH/application/controllers/control.php

# Set permissions
echo "Setting permissions..."
ssh $SERVER "chown nginx:nginx $DEPLOY_PATH/application/controllers/control.php && chmod 644 $DEPLOY_PATH/application/controllers/control.php"

# Verify syntax
echo "Verifying PHP syntax..."
ssh $SERVER "php -l $DEPLOY_PATH/application/controllers/control.php"

echo ""
echo "======================================"
echo "✅ Deployment Complete!"
echo "======================================"
echo ""
echo "Backup saved at:"
echo "  $DEPLOY_PATH/application/controllers/control.php.backup-*"
echo ""
echo "Next: Test restart on production:"
echo "  https://supervisord.omisell.com/control/restart/web_001/omi_report_gunicorn"
echo ""
echo "Check flashdata message for debug log!"
