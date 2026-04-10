# Stage 1: Build assets
FROM node:22-alpine AS build

# Install PHP 8.3 + core extensions
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
    composer

RUN ln -sf /usr/bin/php83 /usr/bin/php

WORKDIR /app
COPY . .

# Install PHP dependencies without running Laravel's post-install hooks
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
# CRITICAL: Add --no-scripts here to bypass the PDO check
RUN composer dump-autoload --no-dev --optimize --no-scripts

# Build JS assets
RUN yarn install && yarn build

# Stage 2: Production
FROM webdevops/php-apache:8.3-alpine
WORKDIR /app
COPY --from=build /app /app
ENV WEB_DOCUMENT_ROOT=/app/public
