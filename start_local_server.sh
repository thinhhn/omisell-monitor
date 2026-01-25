#!/bin/bash

echo "🚀 Starting Supervisor Monitor Local Server"
echo "=========================================="

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP not found. Please install PHP first."
    echo "   - Ubuntu/Debian: sudo apt install php php-cli php-curl php-xml"
    echo "   - macOS: brew install php"
    echo "   - Windows: Download from https://www.php.net/downloads"
    exit 1
fi

echo "✅ PHP found: $(php --version | head -n 1)"

# Create cache directories
echo "📁 Creating cache directories..."
mkdir -p application/cache/supervisor
mkdir -p application/cache/supervisor/persistent
mkdir -p application/logs

# Set permissions
echo "🔐 Setting permissions..."
chmod 755 application/cache/supervisor
chmod 755 application/cache/supervisor/persistent
chmod 755 application/logs

# Get current directory
CURRENT_DIR=$(pwd)
echo "📂 Project directory: $CURRENT_DIR"

# Check for public_html/index.php
if [ ! -f "public_html/index.php" ]; then
    echo "❌ public_html/index.php not found!"
    echo "   Make sure you're in the project root directory"
    exit 1
fi

echo "✅ CodeIgniter found"

# Choose available port
PORT=8000
while lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null; do
    PORT=$((PORT + 1))
done

echo "🌐 Starting PHP development server..."
echo "   URL: http://localhost:$PORT"
echo "   Document root: public_html/"
echo ""
echo "🔑 Default login accounts:"
echo "   - admin / admin123"
echo "   - supervisor / supervisor123" 
echo "   - monitor / monitor123"
echo ""
echo "📊 Features available:"
echo "   - Real-time supervisor monitoring"
echo "   - Performance optimizations"
echo "   - Caching system"
echo "   - Background jobs"
echo ""
echo "⏹️  Press Ctrl+C to stop the server"
echo "=========================================="

# Start PHP built-in server
cd public_html
php -S localhost:$PORT