#!/bin/sh
set -e

# Runs before php-fpm, the queue worker and the scheduler alike, so it must be
# safe to run concurrently in several containers. It deliberately does NOT run
# migrations: those happen once per release, from deploy.sh, before any of
# these containers start.

APP_USER=www-data

# Every upload directory the controllers write to with public_path(). Each one
# is a named volume, so each arrives empty and root-owned on a fresh deploy.
# Keep in step with docker-compose.prod.yml and docker/php/Dockerfile.
UPLOAD_DIRS="public/images
public/detailedproducts
public/insurance
public/client_consents
public/health_supplements
public/quick_forms"

# A volume mounted over storage/ arrives empty, so the framework's scratch
# directories have to be recreated on every start. The public/ upload
# directories are volumes for the same reason — the controllers write uploads
# straight into the web root.
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    $UPLOAD_DIRS 2>/dev/null || true

# A named volume mounted over a path arrives owned by root, whatever the
# image said — so www-data cannot create anything inside it, and the first
# upload fails with "Unable to create a directory at ...". The image cannot
# fix that at build time because the volume does not exist yet; it has to
# happen here, on every start, because a fresh volume can appear on any
# deploy.
#
# Only the mount points, not the whole tree: chown -R over a large uploads
# volume would add seconds to every container start for no benefit.
if [ "$(id -u)" = "0" ]; then
    chown "$APP_USER":"$APP_USER" \
        storage \
        storage/app \
        storage/app/public \
        storage/logs \
        bootstrap/cache \
        $UPLOAD_DIRS 2>/dev/null || true

    # New volumes only: an existing tree is already right, and walking it
    # every start is wasted time on a volume with thousands of uploads.
    #
    # -maxdepth 0 so the test is on the mount point itself. A volume you have
    # just restored a backup into is owned correctly already and is skipped.
    find storage/app/public storage/logs $UPLOAD_DIRS \
        -maxdepth 0 ! -user "$APP_USER" \
        -exec chown -R "$APP_USER":"$APP_USER" {} + 2>/dev/null || true
fi

# public/storage is gitignored, so the image never contains it. Recreate the
# link rather than shipping one, and leave it alone if the path is already a
# real directory (someone bind-mounted uploads straight in).
if [ ! -e public/storage ]; then
    ln -s /var/www/storage/app/public public/storage 2>/dev/null || true
fi

# Only the long-running roles need a configured application. Anything else —
# `php -v`, `artisan migrate`, `artisan tinker`, a shell for debugging — is run
# deliberately by a human or by deploy.sh and must not be blocked.
case "$1" in
    php-fpm|php-fpm.*)
        serving=1 ;;
    php)
        case "$*" in
            *queue:work*|*queue:listen*|*schedule:work*) serving=1 ;;
            *) serving=0 ;;
        esac ;;
    *)
        serving=0 ;;
esac

if [ "$serving" = "1" ]; then
    if [ -z "${APP_KEY:-}" ]; then
        echo "entrypoint: APP_KEY is not set — refusing to serve traffic." >&2
        echo "            Generate one with: php artisan key:generate --show" >&2
        exit 1
    fi

    # Warm the caches at start rather than baking them into the image: they
    # embed environment values, which are only known now.
    #
    # Note this is why no application code may call env() — after config:cache
    # it returns null outside config files.
    #
    # Failures are tolerated on purpose — a cold cache is slower but still
    # correct, and a container that will not start is worse than a slow one.
    # As the app user: a cache file written by root is one www-data cannot
    # replace on the next start, which turns a warm cache into a hard failure.
    as_app() {
        if [ "$(id -u)" = "0" ]; then gosu "$APP_USER" "$@"; else "$@"; fi
    }

    as_app php artisan config:cache 2>&1 || echo "entrypoint: config:cache failed, continuing uncached" >&2
    as_app php artisan route:cache  2>&1 || echo "entrypoint: route:cache failed, continuing uncached" >&2
    as_app php artisan event:cache  2>&1 || true

    echo "entrypoint: ready (${CONTAINER_ROLE:-app})"
fi

# Drop to the application user — except for php-fpm.
#
# php-fpm's master is meant to run as root: it needs that to open the log
# stream the container hands it on stderr, and it drops its own workers to
# www-data from the pool config (`user = www-data` in www.conf). Forcing the
# master down instead fails at startup with "failed to open error_log
# (/proc/self/fd/2): Permission denied" and the container exits 78.
#
# Everything else — the queue worker, the scheduler, artisan, a debugging
# shell — has no such need and runs as the application user.
#
# `gosu` replaces the process rather than forking, so signals and exit codes
# still reach the command exactly as they would without it.
case "$1" in
    php-fpm|php-fpm.*)
        exec "$@" ;;
esac

if [ "$(id -u)" = "0" ]; then
    exec gosu "$APP_USER" "$@"
fi

exec "$@"
