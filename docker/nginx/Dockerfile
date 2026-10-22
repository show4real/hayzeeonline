# syntax=docker/dockerfile:1

# nginx serves public/ itself, so it needs those files — it does not share a
# volume with php-fpm for the application code. The build arg points at the app
# image so both always come from the same release.
ARG APP_IMAGE=hayzeeonline-app:latest
FROM ${APP_IMAGE} AS app

FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Only public/ — the application code has no business inside the web root.
COPY --from=app /var/www/public /var/www/public

# Uploads live on volumes mounted at these paths in this container too, so
# nginx serves them directly instead of routing them through PHP. The same six
# directories the app writes — a stored URL::asset() URL 404s without them.
RUN mkdir -p /var/www/storage/app/public \
             /var/www/public/images \
             /var/www/public/detailedproducts \
             /var/www/public/insurance \
             /var/www/public/client_consents \
             /var/www/public/health_supplements \
             /var/www/public/quick_forms \
    && ln -sfn /var/www/storage/app/public /var/www/public/storage

ARG SOURCE_URL=""
ARG VERSION="dev"
ARG REVISION=""
LABEL org.opencontainers.image.source="$SOURCE_URL" \
      org.opencontainers.image.version="$VERSION" \
      org.opencontainers.image.revision="$REVISION" \
      org.opencontainers.image.title="hayzeeonline-nginx"

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1/healthz >/dev/null 2>&1 || exit 1
