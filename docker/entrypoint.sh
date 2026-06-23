#!/bin/sh

cd /var/www/html

log() {
    echo "[entrypoint] $1"
}

# Railway MySQL
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
    log "Generated APP_KEY — save to Railway variables!"
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear

PORT="${PORT:-8080}"

log "Starting server on 0.0.0.0:${PORT} (healthcheck can pass immediately)..."
php artisan serve --host=0.0.0.0 --port="${PORT}" &
SERVER_PID=$!

setup_app() {
    log "Waiting for database..."
    for i in $(seq 1 60); do
        if php artisan db:show --no-interaction 2>/dev/null; then
            log "Database ready."
            php artisan migrate --force --no-interaction && break
        fi
        sleep 2
    done

    if [ "${RUN_SEEDERS:-true}" = "true" ]; then
        log "Seeding data in background (site may load partially at first)..."
        php artisan db:seed --class=DeploySeeder --force --no-interaction || log "Seed warning"
    fi

    php artisan config:cache || true
    php artisan view:cache || true
    log "Setup complete."
}

setup_app &

wait $SERVER_PID
