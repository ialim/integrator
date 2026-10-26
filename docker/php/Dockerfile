# PHP 8.4 CLI image for local development: Composer, Artisan and Pest run here.
# It also carries the backup tools (pg_dump 17, age, rclone) so the backup,
# restore and drill scripts in scripts/ops and tests/Ops run locally and in CI.
FROM php:8.4-cli-bookworm

# PostgreSQL 17 client tools come from the PostgreSQL project's own apt
# repository: Debian bookworm ships 15, whose pg_dump refuses a 17 server.
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl gnupg \
    && install -d /usr/share/postgresql-common/pgdg \
    && curl -fsSL -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc https://www.postgresql.org/media/keys/ACCC4CF8.asc \
    && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" \
        > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libicu-dev libzip-dev libonig-dev postgresql-client-17 age rclone \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql intl mbstring zip bcmath pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN echo "memory_limit=1G" > /usr/local/etc/php/conf.d/memory.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html
