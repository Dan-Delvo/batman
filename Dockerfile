# Stage 1: Build assets with Node + PHP
FROM node:22-alpine AS build

# Install PHP and dependencies needed for Wayfinder/Artisan
RUN apk add --no-cache \
    php83 \
    php83-cli \
    php83-mbstring \
    php83-xml \
    php83-dom \
    php83-curl

# Ensure 'php' points to php83
RUN ln -sf /usr/bin/php83 /usr/bin/php

WORKDIR /app
COPY . .
RUN yarn install && yarn build

# Stage 2: Serve with PHP 8.3 Apache
FROM webdevops/php-apache:8.3-alpine
WORKDIR /app
COPY --from=build /app /app
ENV WEB_DOCUMENT_ROOT=/app/public
