@echo off
echo ========================================
echo CCS Sit-in Monitoring System
echo ========================================
echo.

REM Set the PHP path
set PHP_PATH=D:\system1\xampp\php\php.exe

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo.
    echo Please check if XAMPP is installed at D:\system1\xampp
    pause
    exit
)

echo PHP found at: %PHP_PATH%
echo.
echo Starting server...
echo Open browser and go to: http://localhost:8000/login.php
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

%PHP_PATH% -S localhost:8000

pause