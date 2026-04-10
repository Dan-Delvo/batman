# Stage 1: Build assets with Node
FROM node:22-alpine AS build
WORKDIR /app
COPY . .
RUN yarn install && yarn build

# Stage 2: Serve with PHP 8.3 Apache
FROM webdevops/php-apache:8.3-alpine
WORKDIR /app
COPY --from=build /app /app
ENV WEB_DOCUMENT_ROOT=/app/public
