# Stage 1: Build assets
FROM node:22-alpine AS build

# Install PHP 8.3 + core extensions (Needed for Wayfinder)
RUN apk add --no-cache \
    php83 \
    php83-cli \
    php83-mbstring \
    php83-xml \
    php83-dom \
    php83-curl \
    php83-openssl \
    php83-phar \
    php83-tokenizer \
    php83-session \
    php83-fileinfo \
    php83-ctype \
    php83-xmlwriter \
    php83-pdo_sqlite \
    composer

RUN ln -sf /usr/bin/php83 /usr/bin/php

WORKDIR /app
COPY . .

# 1. Install PHP dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
RUN composer dump-autoload --no-dev --optimize --no-scripts

# 2. Setup Mock Environment & Build Assets
# We do this in ONE RUN command to keep the layer clean and ensure variables persist
RUN touch database/database.sqlite && \
    export DB_CONNECTION=sqlite && \
    export DB_DATABASE=/app/database/database.sqlite && \
    export APP_KEY=base64:$(php -r "echo base64_encode(random_bytes(32));") && \
    php artisan migrate --force && \
    yarn install && \
    yarn build && \
    rm database/database.sqlite
    
# Stage 2: Production
FROM webdevops/php-apache:8.3-alpine
WORKDIR /app

# Copy everything from build stage
COPY --from=build /app /app

# Final production permissions/cleanup
RUN chown -R application:application /app/storage /app/bootstrap/cache

ENV WEB_DOCUMENT_ROOT=/app/public
