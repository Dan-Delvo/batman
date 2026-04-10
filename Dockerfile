# Stage 1: Build assets
FROM node:22-alpine AS build

# 1. Install PHP 8.3 + EVERY extension Laravel/Composer needs
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
    php83-tokenizer \
    composer

# Force 'php' and 'composer' to use 8.3 specifically
RUN ln -sf /usr/bin/php83 /usr/bin/php

WORKDIR /app
COPY . .

# 2. Install PHP dependencies 
# We add --ignore-platform-reqs just to be safe during the build-only stage
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
RUN composer dump-autoload --no-dev --optimize

# 3. Build JS assets
RUN yarn install && yarn build

# Stage 2: Production
FROM webdevops/php-apache:8.3-alpine
WORKDIR /app
COPY --from=build /app /app
ENV WEB_DOCUMENT_ROOT=/app/public
