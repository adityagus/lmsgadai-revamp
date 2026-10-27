FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund --foreground-scripts --loglevel=verbose --fetch-retries=1 --fetch-timeout=60000
COPY . .
RUN npm run build

FROM php:8.3.36-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev libpq-dev libcurl4-openssl-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql mbstring curl gd zip bcmath opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && composer check-platform-reqs --no-dev
COPY --from=assets /app/public/build ./public/build
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/lms.ini
COPY docker/entrypoint.sh /usr/local/bin/lms-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/lms-entrypoint \
    && chmod +x /usr/local/bin/lms-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache
ENTRYPOINT ["lms-entrypoint"]
CMD ["apache2-foreground"]
