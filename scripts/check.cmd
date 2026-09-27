@echo off
cd /d "%~dp0.."
set "PHP_INI_SCAN_DIR=%CD%\.runtime\php"
php artisan test --compact --no-ansi
if errorlevel 1 exit /b 1
call npm.cmd run test:frontend
if errorlevel 1 exit /b 1
call npm.cmd run build
exit /b %errorlevel%
