#!/bin/sh
set -e

cd /var/www/html

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    export APP_KEY=$(php artisan key:generate --show)
    echo "Generated APP_KEY"
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Waiting for database..."
for i in $(seq 1 30); do
    if php artisan migrate:status --no-interaction 2>/dev/null; then
        break
    fi
    sleep 2
done

php artisan migrate --force --no-interaction

if [ "${RUN_SEEDERS:-true}" = "true" ]; then
    php artisan db:seed --force --no-interaction || true
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

PORT="${PORT:-8080}"
echo "Starting server on port ${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
