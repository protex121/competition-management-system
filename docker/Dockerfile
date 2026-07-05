# Production Dockerfile — not used for local development.
# Build context: project root (see docker-compose.yml)

FROM php:8.4-fpm-alpine AS base

# System dependencies
RUN apk add --no-cache \
    bash \
    curl \
    freetype-dev \
    git \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    unzip

# PHP extensions required by Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip

# Redis extension (phpredis)
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# Application code
COPY . .

RUN composer dump-autoload --optimize
# Note: artisan commands (config:cache, migrate, etc.) run at deploy time,
# not during image build — APP_KEY and .env are not available at build time.

# ---------------------------------------------------------------------------
# Node build stage — compile Vite assets, then discard node_modules
# ---------------------------------------------------------------------------
FROM node:22-alpine AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.* tsconfig.json ./
# Tailwind v4: config lives in resources/css/app.css (copied with resources/)

RUN npm run build

# ---------------------------------------------------------------------------
# Final production image
# ---------------------------------------------------------------------------
FROM base AS production

COPY --from=frontend /var/www/html/public/build ./public/build

# PHP production settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini

# Non-root user for PHP-FPM
RUN addgroup -g 1000 -S www \
    && adduser -u 1000 -S www -G www \
    && chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache

USER www

EXPOSE 9000

CMD ["php-fpm"]
