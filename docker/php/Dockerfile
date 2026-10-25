# PHP 8.4 CLI image for local development: Composer, Artisan and Pest run here.
FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libicu-dev libzip-dev libonig-dev postgresql-client \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql intl mbstring zip bcmath pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN echo "memory_limit=1G" > /usr/local/etc/php/conf.d/memory.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html
