# ── Stage 1: Install PHP dependencies ────────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

COPY backend/composer.json backend/composer.lock* ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-req=ext-bcmath \
    --ignore-platform-req=ext-pdo_pgsql

# ── Stage 2: Runtime image ────────────────────────────────────────────────────
FROM php:8.3-cli-bookworm

WORKDIR /app/backend

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install bcmath exif gd pdo_pgsql pcntl zip \
    && rm -rf /var/lib/apt/lists/*

# Copy vendor from Stage 1 and application source
COPY --from=vendor /app/vendor ./vendor
COPY backend/. .

# Set up storage directories with correct permissions
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN chmod +x /app/backend/start-railway.sh

EXPOSE 8080

CMD ["/bin/sh", "/app/backend/start-railway.sh"]
