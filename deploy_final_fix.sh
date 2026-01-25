#!/bin/bash
#
# Final fix deployment script
#

echo "════════════════════════════════════════════════════════════════"
echo "🚀 DEPLOYING FINAL FIX TO PRODUCTION"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Get server info
read -p "Enter server (user@host): " SERVER
read -p "Enter path [/var/www/supervisord-monitor]: " DEPLOY_PATH
DEPLOY_PATH=${DEPLOY_PATH:-/var/www/supervisord-monitor}

echo ""
echo "Deploying to: $SERVER:$DEPLOY_PATH"
echo ""

# Step 1: Backup
echo "Step 1: Creating backup..."
ssh $SERVER "cp $DEPLOY_PATH/application/controllers/control.php $DEPLOY_PATH/application/controllers/control.php.backup-$(date +%Y%m%d-%H%M%S)"

# Step 2: Deploy files
echo "Step 2: Deploying files..."
scp application/config/config.php $SERVER:$DEPLOY_PATH/application/config/
scp application/controllers/control.php $SERVER:$DEPLOY_PATH/application/controllers/
scp application/views/welcome.php $SERVER:$DEPLOY_PATH/application/views/

# Step 3: Set permissions
echo "Step 3: Setting permissions..."
ssh $SERVER "chown -R nginx:nginx $DEPLOY_PATH/application/ && chmod 644 $DEPLOY_PATH/application/controllers/control.php"

# Step 4: Verify deployment
echo "Step 4: Verifying deployment..."
ssh $SERVER "grep -c \"redirect('')\" $DEPLOY_PATH/application/controllers/control.php"
echo "^ Should see number > 0 (count of redirect('') calls)"

# Step 5: Restart PHP-FPM
echo "Step 5: Restarting PHP-FPM to clear cache..."
ssh $SERVER "sudo systemctl restart php-fpm"

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "✅ DEPLOYMENT COMPLETE!"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "Now test:"
echo "1. Clear browser cache (Ctrl+Shift+Delete)"
echo "2. Go to: https://supervisord.omisell.com/"
echo "3. Click Restart All on celery_hook"
echo "4. Should redirect to homepage with success message"
echo ""
