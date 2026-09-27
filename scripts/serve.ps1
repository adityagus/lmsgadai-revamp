$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot
$env:PHP_INI_SCAN_DIR = Join-Path $projectRoot '.runtime/php'
php artisan serve --host=127.0.0.1 --port=8001
exit $LASTEXITCODE
