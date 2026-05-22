#!/usr/bin/env sh
set -eu

cd /var/www/html

PORT="${PORT:-10000}"

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan storage:link || true
php artisan migrate --force

if [ "${RENDER_SKIP_SEED:-false}" != "true" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT}"
