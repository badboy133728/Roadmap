#!/bin/sh

cd /var/www/html

log() {
    echo "[entrypoint] $1"
}

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

php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

PORT="${PORT:-8080}"

# artisan serve корректно отдаёт CSS/JS из public/build
log "Starting server on 0.0.0.0:${PORT}..."
php artisan serve --host=0.0.0.0 --port="${PORT}" &
SERVER_PID=$!

setup_app() {
    log "Waiting for database and running migrations..."
    for i in $(seq 1 90); do
        if php artisan migrate --force --no-interaction 2>/dev/null; then
            log "Migrations OK."
            if [ "${RUN_SEEDERS:-true}" = "true" ]; then
                log "Seeding data..."
                php artisan db:seed --class=DeploySeeder --force --no-interaction 2>/dev/null || log "Seed warning"
            fi
            log "App setup complete."
            return 0
        fi
        sleep 2
    done
    log "WARNING: Could not migrate — check DATABASE_URL on web service"
    return 1
}

setup_app &

wait $SERVER_PID
