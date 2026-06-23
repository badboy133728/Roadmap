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
    log "Generated APP_KEY — add to Railway variables!"
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear

log "Running migrations (required before site works)..."
migrated=0
for i in $(seq 1 60); do
    if php artisan migrate --force --no-interaction 2>/dev/null; then
        migrated=1
        log "Migrations complete."
        break
    fi
    log "DB not ready, attempt $i/60..."
    sleep 2
done

if [ "$migrated" -ne 1 ]; then
    log "WARNING: migrations failed — check DATABASE_URL on Railway web service"
fi

PORT="${PORT:-8080}"
log "Starting server on 0.0.0.0:${PORT}..."
php artisan serve --host=0.0.0.0 --port="${PORT}" &
SERVER_PID=$!

if [ "${RUN_SEEDERS:-true}" = "true" ] && [ "$migrated" -eq 1 ]; then
    log "Seeding in background..."
    (
        php artisan db:seed --class=DeploySeeder --force --no-interaction
        php artisan config:cache || true
        php artisan view:cache || true
        log "Seeding complete."
    ) &
fi

wait $SERVER_PID
