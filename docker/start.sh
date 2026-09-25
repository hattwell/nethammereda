#!/bin/sh
set -eu

export PORT="${PORT:-10000}"
if [ "${APP_ENV:-}" != production ] || [ "${APP_DEBUG:-}" != false ] || \
   [ "${HOSTED_DEMO:-}" != true ] || [ "${DB_CONNECTION:-}" != sqlite ] || \
   [ "${DB_DATABASE:-}" != /var/www/html/storage/demo.sqlite ] || \
   [ -z "${APP_KEY:-}" ] || [ -n "${TELEGRAM_BOT_TOKEN:-}" ] || \
   [ "${DEMO_RESET_ALLOWED:-false}" != false ]; then
    echo 'Refusing hosted demo startup: unsafe environment or database configuration.' >&2
    exit 1
fi

case "$PORT" in
    ''|*[!0-9]*) echo 'Refusing hosted demo startup: invalid port.' >&2; exit 1 ;;
esac

php artisan demo:initialize-hosted --no-interaction
chown -R www-data:www-data /var/www/html/storage
envsubst '${PORT}' < /var/www/html/docker/nginx.conf > /etc/nginx/conf.d/default.conf
php-fpm -D
exec nginx -g 'daemon off;'
