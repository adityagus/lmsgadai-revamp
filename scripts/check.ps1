$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot
$env:PHP_INI_SCAN_DIR = Join-Path $projectRoot '.runtime/php'
php artisan test
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
npm.cmd run test:frontend
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
npm.cmd run build
exit $LASTEXITCODE
