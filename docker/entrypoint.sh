#!/bin/sh
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY belum diisi di .env. Gunakan key aplikasi yang sudah ada." >&2
    exit 1
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage/framework bootstrap/cache

# Existing local files must not bypass the MinIO route after switching storage.
if [ "${LMS_STORAGE_DRIVER:-local}" = "s3" ]; then
    if [ -L public/storage ]; then
        unlink public/storage
    fi
elif [ ! -e public/storage ]; then
    ln -s /var/www/uploads/public public/storage
fi

exec docker-php-entrypoint "$@"
