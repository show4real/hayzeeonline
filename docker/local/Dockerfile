# syntax=docker/dockerfile:1
#
# LOCAL DEVELOPMENT ONLY — the production image is docker/php/Dockerfile.
#
# The differences that matter:
#   * the application is bind-mounted, not copied in, so edits are live
#   * Composer and the dev dependencies are present
#   * opcache revalidates, so changed files are actually picked up
#   * it runs as the host user, so files it writes are not owned by root
#
# Same PHP patch release as production, so "works locally" means something.
FROM php:8.2.35-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        default-mysql-client \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev \
        procps \
        unzip \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Must match the production extension set, or a dependency that installs here
# fails in CI (or worse, the other way round).
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath exif gd intl opcache pcntl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN printf '[client]\nskip-ssl\n' > /root/.my.cnf

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Development settings. opcache.validate_timestamps=1 is the whole point —
# production sets it to 0 because a release is a new image, but here the code
# changes under a running container.
RUN { \
        echo 'memory_limit = 512M'; \
        echo 'max_execution_time = 0'; \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 72M'; \
        echo 'max_file_uploads = 30'; \
        echo 'display_errors = On'; \
        echo 'display_startup_errors = On'; \
        echo 'error_reporting = E_ALL'; \
        echo 'log_errors = On'; \
        echo 'error_log = /proc/self/fd/2'; \
        echo 'date.timezone = UTC'; \
        echo 'opcache.enable = 1'; \
        echo 'opcache.validate_timestamps = 1'; \
        echo 'opcache.revalidate_freq = 0'; \
    } > /usr/local/etc/php/conf.d/99-local.ini

# Send the pool's output to the container log, so `docker compose logs php`
# shows PHP warnings rather than swallowing them.
RUN { \
        echo '[www]'; \
        echo 'catch_workers_output = yes'; \
        echo 'decorate_workers_output = no'; \
        echo 'access.log = /proc/self/fd/2'; \
        echo 'clear_env = no'; \
    } > /usr/local/etc/php-fpm.d/zz-local.conf

WORKDIR /var/www/html

# Run as the host user, so files written inside the container (storage/logs,
# bootstrap/cache, composer's vendor/) are editable on the host afterwards.
# Bind mounts carry host ownership straight through, so a mismatch here is the
# usual cause of "Permission denied" on a freshly built container.
ARG UID=1000
ARG GID=1000
RUN groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g www-data www-data \
    && mkdir -p /var/www/.composer \
    && chown -R www-data:www-data /var/www/.composer

ENV COMPOSER_HOME=/var/www/.composer

USER www-data
