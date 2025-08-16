# ===================== Base PHP (extensions + composer) =====================
FROM php:8.3-fpm-alpine AS phpbase

# System deps needed by PHP extensions & tools
RUN apk add --no-cache \
    icu-dev libzip-dev oniguruma-dev zlib-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    git unzip bash curl

# PHP extensions for your stack (maatwebsite/excel, datatables, adminlte)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) intl zip mbstring pdo_mysql bcmath gd exif \
 && docker-php-ext-install opcache

# Opcache config
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html


# ===================== Node build (Vite) =====================
FROM node:20-alpine AS nodebuild
WORKDIR /app

# Cache deps
COPY package*.json vite.config.* ./
RUN npm ci || npm i

# Copy the rest and build
COPY . .
RUN npm run build


# ===================== Composer deps (no scripts / no autoload first) =====================
FROM phpbase AS composerbuild
WORKDIR /var/www/html

# 1) copy only composer files for better cache
COPY composer.json composer.lock ./

# safer composer env
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

# 2) install vendor WITHOUT scripts and WITHOUT autoloader
RUN composer install \
     --prefer-dist --no-interaction --no-progress \
    --no-scripts --no-autoloader

# 3) now copy the whole application so app/Helpers/helpers.php exists
COPY . .

# 4) now dump autoload (helpers.php exists, so no error)
RUN composer dump-autoload --no-dev -o

# (Optional) you can pre-cache configs/routes/views here if you want,
# but it's usually better to run them after the container starts with real .env


# ===================== Final runtime image =====================
FROM phpbase AS app
WORKDIR /var/www/html

# bring in application (including vendor) from composer stage
COPY --from=composerbuild /var/www/html /var/www/html

# bring built assets
COPY --from=nodebuild /app/public/build /var/www/html/public/build

# permissions (keep storage/bootstrap writable)
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm","-F"]
