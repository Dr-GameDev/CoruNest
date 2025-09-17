# Multi-stage build for Laravel application

# Frontend builder stage
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Install system dependencies (including git) and PHP extensions for Composer
RUN apk add --no-cache git php php-phar php-json php-mbstring php-openssl curl bash \
    php83-dom \
    php83-session \
    php83-pcntl \
    php83-fileinfo \
    php83-tokenizer \
    php83-iconv \
    php83-posix \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy PHP composer files and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy node files and install JS dependencies
COPY package*.json ./
RUN npm install

# Copy the rest of the application including vendor (now exists due to composer install)
COPY . .

# Build frontend assets
RUN npm run build

# PHP Application Stage
FROM php:8.2-fpm AS app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zip \
    unzip \
    default-mysql-client \
    nginx \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application files
COPY . .

# Copy built assets from frontend-builder stage
COPY --from=frontend-builder /app/public/build ./public/build

# Complete composer setup
RUN composer dump-autoload --optimize

# Create necessary directories and set permissions
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copy configuration files
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/laravel.ini

# Switch to non-root user for security
USER www-data

EXPOSE 8000

# Use supervisor to run nginx and php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
