@echo off
title Supervisor Monitor Local Server

echo.
echo 🚀 Starting Supervisor Monitor Local Server
echo ==========================================

REM Check if PHP is installed
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP not found. Please install PHP first.
    echo    Download from https://www.php.net/downloads
    echo    Or use XAMPP: https://www.apachefriends.org/
    pause
    exit /b 1
)

echo ✅ PHP found
php --version

REM Create cache directories
echo.
echo 📁 Creating cache directories...
if not exist "application\cache\supervisor" mkdir "application\cache\supervisor"
if not exist "application\cache\supervisor\persistent" mkdir "application\cache\supervisor\persistent" 
if not exist "application\logs" mkdir "application\logs"

REM Check for public_html/index.php
if not exist "public_html\index.php" (
    echo ❌ public_html\index.php not found!
    echo    Make sure you're in the project root directory
    pause
    exit /b 1
)

echo ✅ CodeIgniter found

REM Start server on available port
set PORT=8000
:find_port
netstat -an | find ":%PORT%" >nul
if not errorlevel 1 (
    set /a PORT+=1
    goto find_port
)

echo.
echo 🌐 Starting PHP development server...
echo    URL: http://localhost:%PORT%
echo    Document root: public_html\
echo.
echo 🔑 Default login accounts:
echo    - admin / admin123
echo    - supervisor / supervisor123
echo    - monitor / monitor123
echo.
echo 📊 Features available:
echo    - Real-time supervisor monitoring
echo    - Performance optimizations  
echo    - Caching system
echo    - Background jobs
echo.
echo ⏹️  Press Ctrl+C to stop the server
echo ==========================================
echo.

REM Start PHP built-in server
cd public_html
php -S localhost:%PORT%
pause