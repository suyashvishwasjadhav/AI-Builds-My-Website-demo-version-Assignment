#!/bin/sh
set -e

cd /var/www/backend

if [ -z "$APP_KEY" ]; then
    echo "Generating temporary APP_KEY..."
    export APP_KEY=$(php artisan key:generate --show)
fi

if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    mkdir -p /var/www/database
    touch /var/www/database/database.sqlite
fi

php artisan storage:link --force || true
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan db:seed --force || true

# Start background queue worker (auto-restart if it ever exits)
(while true; do php artisan queue:work --tries=1 --sleep=3; sleep 1; done) &

# Start HTTP server
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
