#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY belum diisi di .env. Gunakan key aplikasi yang sudah ada." >&2
    exit 1
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage/framework storage/logs bootstrap/cache

# Existing local files must not bypass the MinIO route after switching storage.
if [ -L public/storage ]; then
    unlink public/storage
fi

php artisan config:cache
php artisan view:cache

exec docker-php-entrypoint "$@"
