#!/usr/bin/env bash
#
# Copies the uploads from a pre-Docker deployment into this stack's named
# volumes. Runs ON THE VPS, where both the old directory and the volumes live.
#
#   sudo ./docker/restore-uploads.sh /var/www/hayzeeonline/public
#   sudo ./docker/restore-uploads.sh /var/www/hayzeeonline/public --dry-run
#
# Why this is needed: the controllers write uploads into the web root with
# ->move(public_path(...)), and the web root is part of the image. The
# production stack therefore mounts a volume over each upload directory so a
# release cannot delete them — and on the first deploy those volumes are empty,
# so every image URL already in the database returns 404 until the files are
# put there. That is what this script does.
#
# It only ever ADDS files. Nothing in the volumes is deleted, and the source
# directory is left untouched, so it is safe to re-run and safe to roll back to
# the old setup afterwards.

set -Eeuo pipefail

SRC="${1:-}"
DRY_RUN=0
[[ "${2:-}" == "--dry-run" ]] && DRY_RUN=1

if [[ -z $SRC ]]; then
    cat >&2 <<USAGE
usage: restore-uploads.sh <old-public-dir> [--dry-run]

  <old-public-dir>  the public/ directory of the previous deployment, e.g.
                    /var/www/hayzeeonline/public

Not sure where it is? Try:
  sudo find /var/www /home /srv -maxdepth 5 -type d -name detailedproducts 2>/dev/null
USAGE
    exit 1
fi

[[ -d $SRC ]] || { echo "restore: $SRC is not a directory" >&2; exit 1; }

# Must match docker-compose.prod.yml. STACK_NAME prefixes every volume so a
# second stack on this host cannot be written into by accident.
STACK_NAME="${STACK_NAME:-hayzeeonline}"

# source directory -> volume suffix
DIRS="
images:product_images
detailedproducts:product_detailed
health_supplements:health_supplements
insurance:insurance
client_consents:client_consents
quick_forms:quick_forms
"

# www-data inside the php image. A file owned by root in these volumes is one
# the application cannot overwrite or delete later.
APP_UID=33
APP_GID=33

command -v docker >/dev/null || { echo "restore: docker not found" >&2; exit 1; }

echo "restore: source     ${SRC}"
echo "restore: stack      ${STACK_NAME}"
[[ $DRY_RUN == 1 ]] && echo "restore: DRY RUN — nothing will be written"
echo

total_copied=0

for entry in $DIRS; do
    src_name="${entry%%:*}"
    vol="${STACK_NAME}_${entry##*:}"
    src_path="${SRC}/${src_name}"

    if [[ ! -d $src_path ]]; then
        printf '  %-22s source missing — skipped\n' "$src_name"
        continue
    fi

    src_count="$(find "$src_path" -type f 2>/dev/null | wc -l | tr -d ' ')"

    # An absent volume means this stack has never run, or STACK_NAME is wrong.
    # Creating it here would silently attach files to nothing, so stop instead.
    if ! docker volume inspect "$vol" >/dev/null 2>&1; then
        printf '  %-22s VOLUME %s DOES NOT EXIST\n' "$src_name" "$vol" >&2
        echo "restore: deploy the stack once before restoring, and check STACK_NAME" >&2
        exit 1
    fi

    before="$(docker run --rm -v "${vol}:/d" alpine sh -c 'find /d -type f | wc -l' 2>/dev/null | tr -d ' ')"

    if [[ $DRY_RUN == 1 ]]; then
        printf '  %-22s would copy %6s file(s) into %s (currently %s)\n' \
            "$src_name" "$src_count" "$vol" "$before"
        continue
    fi

    # -a to keep timestamps and permissions; /src/. so the CONTENTS are copied
    # rather than the directory itself. Read-only source mount, so a mistake
    # here cannot touch the old deployment.
    docker run --rm \
        -v "${vol}:/dest" \
        -v "${src_path}:/src:ro" \
        alpine sh -c "cp -a /src/. /dest/ 2>/dev/null || true; chown -R ${APP_UID}:${APP_GID} /dest"

    after="$(docker run --rm -v "${vol}:/d" alpine sh -c 'find /d -type f | wc -l' | tr -d ' ')"
    printf '  %-22s %6s file(s) in source, volume %s -> %s\n' \
        "$src_name" "$src_count" "$before" "$after"
    total_copied=$(( total_copied + after - before ))
done

echo
if [[ $DRY_RUN == 1 ]]; then
    echo "restore: dry run complete — re-run without --dry-run to copy"
    exit 0
fi

echo "restore: done — ${total_copied} new file(s) across all volumes"
echo "restore: the old directory was not modified; keep it until you have verified."
echo
echo "Verify with a URL that was 404ing, e.g.:"
echo "  curl -sI http://127.0.0.1:${APP_PORT:-8096}/images/<filename> | head -1"
