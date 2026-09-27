@echo off
cd /d "%~dp0.."
set "PHP_INI_SCAN_DIR=%CD%\.runtime\php"
set "APP_URL=http://127.0.0.1:8001"
php artisan serve --host=127.0.0.1 --port=8001
