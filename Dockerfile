# MiniBox: the PHP part (PHP-FPM). nginx runs in its own container (see docker-compose.yml).

# Stage 1: install the Composer packages (vendor/) with the official Composer image.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-autoloader
COPY src/ src/
RUN composer dump-autoload --no-dev --optimize

# Stage 2: PHP-FPM.
FROM php:8.3-fpm

# PostgreSQL driver for PDO.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# PHP settings: upload limits, errors go to the log instead of the page.
COPY docker/php.ini /usr/local/etc/php/conf.d/minibox.ini

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor/ vendor/
RUN chmod +x docker/entrypoint.sh

# PHP-FPM listens on port 9000; nginx sends PHP requests there.
EXPOSE 9000
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php-fpm"]
