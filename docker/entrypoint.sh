#!/bin/sh
set -e

cd /var/www/html

log() {
    echo "[entrypoint] $1"
}

# Railway MySQL: подставляем URL и отдельные переменные
if [ -z "$DATABASE_URL" ]; then
    if [ -n "$MYSQL_URL" ]; then
        export DATABASE_URL="$MYSQL_URL"
    elif [ -n "$MYSQL_PUBLIC_URL" ]; then
        export DATABASE_URL="$MYSQL_PUBLIC_URL"
    fi
fi

if [ -n "$MYSQLHOST" ]; then
    export DB_CONNECTION="${DB_CONNECTION:-mysql}"
    export DB_HOST="$MYSQLHOST"
    export DB_PORT="${MYSQLPORT:-3306}"
    export DB_DATABASE="${MYSQLDATABASE:-$MYSQL_DATABASE}"
    export DB_USERNAME="${MYSQLUSER:-$MYSQL_USER}"
    export DB_PASSWORD="${MYSQLPASSWORD:-$MYSQL_PASSWORD}"
fi

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    export APP_KEY=$(php artisan key:generate --show)
    log "Generated APP_KEY (save it in Railway variables!)"
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear

log "DB_HOST=${DB_HOST:-empty}, DATABASE_URL=${DATABASE_URL:+set}, MYSQL_URL=${MYSQL_URL:+set}"

log "Waiting for database (up to 90s)..."
db_ready=0
for i in $(seq 1 45); do
    if php artisan db:show --no-interaction 2>/dev/null; then
        db_ready=1
        log "Database is ready."
        break
    fi
    log "Attempt $i/45 — database not ready, waiting..."
    sleep 2
done

if [ "$db_ready" -ne 1 ]; then
    log "ERROR: Cannot connect to database."
    log "In Railway: add MySQL service and link DATABASE_URL=\${{MySQL.MYSQL_URL}} to this service."
    exit 1
fi

log "Running migrations..."
php artisan migrate --force --no-interaction

if [ "${RUN_SEEDERS:-true}" = "true" ]; then
    log "Running deploy seeders (this may take 1-2 min)..."
    php artisan db:seed --class=DeploySeeder --force --no-interaction || log "Seed warning (non-fatal)"
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

PORT="${PORT:-8080}"
log "Starting server on 0.0.0.0:${PORT}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
