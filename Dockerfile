# Stage 1: Build assets with Node + PHP + Composer
FROM node:22-alpine AS build

# 1. Install PHP and system dependencies
RUN apk add --no-cache \
    php83 \
    php83-cli \
    php83-mbstring \
    php83-xml \
    php83-dom \
    php83-curl \
    php83-openssl \
    php83-phar \
    composer

# Ensure 'php' points to php83
RUN ln -sf /usr/bin/php83 /usr/bin/php

WORKDIR /app
COPY . .

# 2. Install PHP dependencies first (Required for 'php artisan wayfinder:generate')
RUN composer install --no-dev --no-scripts --no-autoloader
RUN composer dump-autoload --no-dev --optimize

# 3. Now install JS dependencies and build
RUN yarn install && yarn build

# Stage 2: Production Server
FROM webdevops/php-apache:8.3-alpine
WORKDIR /app
COPY --from=build /app /app
ENV WEB_DOCUMENT_ROOT=/app/public
