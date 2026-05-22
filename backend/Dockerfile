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

FROM php:8.3-cli-bookworm

WORKDIR /app/backend

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install bcmath exif gd pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=vendor /app/vendor ./vendor
COPY backend/. .

RUN chmod +x /app/backend/start-railway.sh

EXPOSE 8080

CMD ["/bin/sh", "/app/backend/start-railway.sh"]