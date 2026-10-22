# syntax=docker/dockerfile:1

###############################################################################
# Stage 1 — PHP extensions, shared by the builder and the runtime.
###############################################################################
# Pinned to the exact patch release rather than 8.2-fpm, so a rebuild months
# from now produces the same runtime as the release that was tested.
FROM php:8.2.35-fpm-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        default-mysql-client \
        gosu \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        libfcgi-bin \
        procps \
    && rm -rf /var/lib/apt/lists/*

# Two of those are there for the healthchecks, not for the application:
#   libfcgi-bin  -> cgi-fcgi, which pings php-fpm in the app container
#   procps       -> pgrep, which the queue and scheduler containers use
# Without procps those two sit permanently "unhealthy" (their healthcheck exits
# 127, "pgrep: not found") even though the worker is running perfectly well.

# gd must be configured before it is built, otherwise jpeg/freetype/webp
# support is silently left out — and gd is what Intervention Image drives to
# rotate and recompress every product photo on upload.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath exif gd intl opcache pcntl pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# `artisan migrate` shells out to the mysql client for schema files. MySQL 8/9
# presents a self-signed certificate that the client refuses by default.
RUN printf '[client]\nskip-ssl\n' > /root/.my.cnf

###############################################################################
# Stage 2 — Composer dependencies, cached on the lock file alone.
###############################################################################
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app

# Only the manifests, so a code-only change does not re-resolve dependencies.
COPY composer.json composer.lock ./

# Two things guard this step, because it is the one that fails on someone
# else's infrastructure rather than ours:
#
#   COMPOSER_AUTH — without a token, Composer fetches zipballs from
#   api.github.com anonymously, which is rate-limited to 60 requests an hour
#   PER IP. CI runners share IPs, so a build walks into somebody else's
#   exhausted quota and GitHub answers 504. Mounted as a BuildKit secret, not
#   a build-arg, so the token never appears in the image history.
#
#   The retry — even authenticated, api.github.com returns the occasional 504.
#   A release should not fail because one zipball was briefly unavailable.
RUN --mount=type=secret,id=composer_auth \
    export COMPOSER_AUTH="$(cat /run/secrets/composer_auth 2>/dev/null || echo '{}')" \
    && for attempt in 1 2 3; do \
        if composer install \
            --no-dev \
            --no-scripts \
            --no-autoloader \
            --prefer-dist \
            --no-interaction \
            --no-progress; then ok=1; break; fi; \
        echo "composer install failed (attempt ${attempt}/3) — retrying"; \
        sleep $((attempt * 15)); \
    done; \
    [ "${ok:-0}" = 1 ]

COPY . ./

# Now that the source is present the autoloader can be generated and
# authoritative — every class is known at build time, so no filesystem lookups
# happen at runtime.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && composer run-script post-autoload-dump --no-dev

###############################################################################
# Stage 3 — Runtime.
###############################################################################
FROM base AS runtime

WORKDIR /var/www

COPY docker/php/php.ini       /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf      /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

COPY --from=vendor --chown=www-data:www-data /app /var/www

# storage/ and bootstrap/cache are the only writable paths. Everything else is
# read-only to the application at runtime.
#
# The six public/ directories are writable too, because the controllers write
# uploads straight into the web root with ->move(public_path(...)) rather than
# to a Laravel disk. Each is mounted as a volume in docker-compose.prod.yml so
# the uploads survive a release; these are the mount points.
ARG UPLOAD_DIRS="public/images public/detailedproducts public/insurance public/client_consents public/health_supplements public/quick_forms"

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        ${UPLOAD_DIRS} \
    && chown -R www-data:www-data storage bootstrap/cache ${UPLOAD_DIRS} \
    && chmod -R 775 storage bootstrap/cache ${UPLOAD_DIRS}

# www-data has no home directory in the base image, so anything that wants to
# write dotfiles (artisan tinker's psysh history, composer) fails. /tmp is
# writable and nothing here needs the contents to persist.
ENV HOME=/tmp

# Deliberately NOT `USER www-data`.
#
# A named volume mounted over storage/ arrives owned by root, and a process
# already running as www-data cannot chown its way out of that — which is
# exactly the "Unable to create a directory" failure on first deploy. So the
# entrypoint starts as root, fixes the mount points, and drops to www-data
# with gosu before exec'ing anything. Nothing in the app ever runs as root.

# Links the published package to this repository on GitHub: without the source
# label GHCR shows it as a loose package owned by the account, with its own
# separate permissions. Also stamps what was built, so `docker inspect` on the
# server tells you the release and commit.
ARG SOURCE_URL=""
ARG VERSION="dev"
ARG REVISION=""
LABEL org.opencontainers.image.source="$SOURCE_URL" \
      org.opencontainers.image.version="$VERSION" \
      org.opencontainers.image.revision="$REVISION" \
      org.opencontainers.image.title="hayzeeonline-app"

# Asks php-fpm's own ping endpoint (enabled in zz-www.conf), so a wedged pool
# is restarted rather than left as a black hole behind the proxy.
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
