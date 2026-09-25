FROM php:8.4-fpm-bookworm AS backend
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx gettext-base unzip libicu-dev libonig-dev libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 bcmath gd intl mbstring pdo_sqlite zip opcache \
    && rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default \
    && printf '\nclear_env = no\n' >> /usr/local/etc/php-fpm.d/www.conf
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader \
    && composer check-platform-reqs --no-dev
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && php artisan package:discover --ansi

FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY --from=backend /var/www/html/vendor ./vendor
COPY resources ./resources
COPY app/Filament ./app/Filament
COPY vite.config.js jsconfig.json ./
RUN npm run build

FROM backend
COPY --from=frontend /app/public/build ./public/build
EXPOSE 10000
CMD ["/bin/sh", "/var/www/html/docker/start.sh"]
