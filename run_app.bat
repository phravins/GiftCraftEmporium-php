@echo off
echo Preparing database...
php setup.php
if %errorlevel% neq 0 (
    echo Failed to run setup.php. Please make sure PHP 8.1+ is installed, in your PATH, and pdo_sqlite is enabled.
    pause
    exit /b
)

echo.
echo Starting GiftCraft Emporium...
echo Open http://127.0.0.1:8000 in your browser
php -S 127.0.0.1:8000 router.php
pause
