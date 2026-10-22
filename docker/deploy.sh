#!/usr/bin/env bash
#
# Runs ON THE VPS. The workflow copies this file, docker-compose.prod.yml and
# .env into the deploy directory, then invokes:
#
#   deploy.sh <app-image> <nginx-image>
#
# Pulls the release, migrates once, rolls the containers, and checks the app
# answers. Anything that fails puts the previous release back.

set -Eeuo pipefail

APP_IMAGE="${1:?usage: deploy.sh <app-image> <nginx-image>}"
NGINX_IMAGE="${2:?usage: deploy.sh <app-image> <nginx-image>}"

DEPLOY_DIR="${DEPLOY_DIR:-/srv/hayzeeonline}"
# Prefixes every container, volume and network, so the production and test
# stacks stay separate even on one host. compose reads it for interpolation.
STACK_NAME="${STACK_NAME:-hayzeeonline}"
export STACK_NAME

COMPOSE="docker compose -f docker-compose.prod.yml"

cd "$DEPLOY_DIR"

[[ -f .env ]] || { echo "deploy: .env missing — the workflow should have copied it" >&2; exit 1; }
chmod 600 .env

# The port Apache proxies to. The workflow passes this in (PROXY_PORT), and
# exporting it here makes it beat whatever .env says, because compose reads the
# shell environment ahead of the env file.
#
# Keeping the port in one place matters: when it lived in the workflow AND the
# secret, a secret copied from the production template gave the test stack the
# production port while Apache proxied to the other one. The deploy reported
# healthy (it read the same wrong value) and the site served 503.
ENV_PORT="$(grep -E '^APP_PORT=' .env 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"'"'"' ' || true)"
APP_PORT="${APP_PORT:-${ENV_PORT:-8096}}"
export APP_PORT

if [[ -n $ENV_PORT && $ENV_PORT != "$APP_PORT" ]]; then
    echo "deploy: NOTE .env says APP_PORT=${ENV_PORT}, deploying on ${APP_PORT} (the workflow wins)" >&2
fi

# What is running right now, read from the container rather than a file, so a
# freshly copied .env cannot confuse the rollback.
previous_image() {
    docker inspect --format '{{.Config.Image}}' "$1" 2>/dev/null || true
}
PREV_APP="$(previous_image "${STACK_NAME}_app")"
PREV_NGINX="$(previous_image "${STACK_NAME}_nginx")"

echo "deploy: current  ${PREV_APP:-<none>}"
echo "deploy: incoming ${APP_IMAGE}"

# compose reads these for ${APP_IMAGE} / ${NGINX_IMAGE}. Exporting rather than
# writing them into .env keeps .env exactly as the secret defines it.
run_stack() {
    APP_IMAGE="$1" NGINX_IMAGE="$2" $COMPOSE up -d --remove-orphans
}

rollback() {
    echo "deploy: FAILED" >&2
    if [[ -n "$PREV_APP" && -n "$PREV_NGINX" ]]; then
        echo "deploy: rolling back to ${PREV_APP}" >&2
        run_stack "$PREV_APP" "$PREV_NGINX" || true
    else
        echo "deploy: nothing to roll back to (first deploy)" >&2
    fi
    exit 1
}

# ---- pull first, so nothing serving is touched until the images are here ----
# A pull failure is only fatal if the image is not already on the box: that
# covers re-running the same release, and a locally built image.
ensure_image() {
    local out
    if out="$(docker pull -q "$1" 2>&1)"; then
        echo "deploy: pulled $1"
    elif docker image inspect "$1" >/dev/null 2>&1; then
        echo "deploy: using local $1 (pull failed: ${out})"
    else
        # Print what the registry actually said. Swallowing it turned an
        # explicit "denied" or "manifest unknown" into a bare "cannot pull",
        # which says nothing about whether the image is missing, private, or
        # the login expired.
        echo "deploy: cannot pull or find $1" >&2
        echo "deploy: registry said: ${out}" >&2
        echo "deploy: logged in as: $(docker system info 2>/dev/null | grep -i username || echo 'not logged in')" >&2
        exit 1
    fi
}
echo "deploy: fetching images"
ensure_image "$APP_IMAGE"
ensure_image "$NGINX_IMAGE"

trap rollback ERR

# ---- migrate once, from a throwaway container -------------------------------
# Not in the entrypoint: app, queue and scheduler start from the same image and
# would race each other.
echo "deploy: migrating"
APP_IMAGE="$APP_IMAGE" NGINX_IMAGE="$NGINX_IMAGE" \
    $COMPOSE run --rm --no-deps -e CONTAINER_ROLE=migrate app \
    php artisan migrate --force --no-interaction

# ---- roll --------------------------------------------------------------------
echo "deploy: starting"
run_stack "$APP_IMAGE" "$NGINX_IMAGE"

# ---- prove it answers before calling it a success ---------------------------
# curl is not on every minimal image; fall back to wget, then to a container.
http_ok() {
    if command -v curl >/dev/null 2>&1; then curl -fsS "$1" >/dev/null 2>&1
    elif command -v wget >/dev/null 2>&1; then wget -qO- "$1" >/dev/null 2>&1
    else docker run --rm --network host curlimages/curl:latest -fsS "$1" >/dev/null 2>&1
    fi
}

echo "deploy: waiting for http://127.0.0.1:${APP_PORT}/up"

for i in $(seq 1 40); do
    if http_ok "http://127.0.0.1:${APP_PORT}/up"; then
        trap - ERR
        echo "deploy: healthy after ${i}s"
        docker image prune -f --filter "until=168h" >/dev/null 2>&1 || true
        echo "deploy: done — ${APP_IMAGE}"
        exit 0
    fi
    sleep 1
done

echo "deploy: never became healthy" >&2
APP_IMAGE="$APP_IMAGE" NGINX_IMAGE="$NGINX_IMAGE" $COMPOSE logs --tail=50 app nginx >&2 || true
rollback
