# ---- PHP base with needed extensions ----
FROM php:8.3-fpm-alpine AS phpbase

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev zlib-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev git unzip bash curl

# extensions for your stack (excel, datatables, adminlte)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) intl zip mbstring pdo_mysql bcmath gd exif

# opcache
RUN docker-php-ext-install opcache
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---- build vite assets ----
FROM node:20-alpine AS nodebuild
WORKDIR /app
COPY package*.json vite.config.* ./
RUN npm ci || npm i
COPY . .
RUN npm run build

# ---- composer install (no-dev) ----
FROM phpbase AS composerbuild
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
COPY . .

# ---- final runtime image ----
FROM phpbase AS app
WORKDIR /var/www/html

COPY --from=composerbuild /var/www/html /var/www/html
COPY --from=nodebuild /app/public/build /var/www/html/public/build

RUN addgroup -g 1000 -S app && adduser -u 1000 -S app -G app \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE 9000
CMD ["php-fpm","-F"]
