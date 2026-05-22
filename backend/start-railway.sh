#!/bin/sh
set -e

echo "==> CCC Backend starting..."

# Ensure required storage directories exist
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Clear config/route/view caches so env changes take effect
php artisan config:clear  || true
php artisan route:clear   || true
php artisan view:clear    || true

# Cache for production performance
if [ "${APP_ENV:-local}" = "production" ]; then
  php artisan config:cache  || true
  php artisan route:cache   || true
  php artisan view:cache    || true
fi

# Storage link — ignore if public/ is read-only (container environments)
php artisan storage:link --force 2>/dev/null || true

# Wait for the database to be available (Railway services can start out of order)
echo "==> Waiting for database..."
MAX_TRIES=30
COUNT=0
until php artisan db:show --json >/dev/null 2>&1; do
  COUNT=$((COUNT + 1))
  if [ "$COUNT" -ge "$MAX_TRIES" ]; then
    echo "ERROR: Database not available after ${MAX_TRIES} attempts. Aborting."
    exit 1
  fi
  echo "  DB not ready (attempt $COUNT/$MAX_TRIES) — retrying in 2s..."
  sleep 2
done
echo "==> Database is ready."

# Run migrations
php artisan migrate --force

echo "==> Starting server on port ${PORT:-8080}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
