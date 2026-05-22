#!/bin/sh
set -eu

mkdir -p storage/framework/views

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan storage:link || true
php artisan migrate --force || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"