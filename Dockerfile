# ---------- Base PHP (with needed extensions) ----------
FROM php:8.3-fpm-alpine AS phpbase

# System deps for PHP extensions
RUN apk add --no-cache \
    icu-dev libzip-dev oniguruma-dev zlib-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    git unzip bash curl

# PHP extensions for Laravel + Excel + Datatables
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    intl zip mbstring pdo_mysql bcmath gd exif

# Opcache
RUN docker-php-ext-install opcache
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---------- Node build stage for Vite ----------
FROM node:20-alpine AS nodebuild
WORKDIR /app
# Copy package files first for better caching
COPY package*.json vite.config.* ./
RUN npm ci || npm i
# Copy the rest to build
COPY . .
RUN npm run build

# ---------- Composer deps (no-dev) ----------
FROM phpbase AS composerbuild
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
# Copy the application
COPY . .
# Ensure storage symlink exists at runtime (we'll run artisan later)
# Do not run migrations here (needs DB)

# ---------- Final app image ----------
FROM phpbase AS app
WORKDIR /var/www/html

# Copy vendor from composer stage
COPY --from=composerbuild /var/www/html /var/www/html

# Copy Vite build artifacts from node stage (adjust if you output to /public/build)
COPY --from=nodebuild /app/public/build /var/www/html/public/build

# Permissions (www-data user id 82 on Alpine)
RUN addgroup -g 1000 -S app && adduser -u 1000 -S app -G app \
 && chown -R www-data:www-data /var/www/html \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

USER www-data

# PHP-FPM listens on 9000 by default
EXPOSE 9000

CMD ["php-fpm", "-F"]
