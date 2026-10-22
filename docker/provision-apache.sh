#!/usr/bin/env bash
#
# Makes sure one stack has an Apache vhost pointing at it.
#
# TLS is deliberately NOT handled here — issue certificates yourself:
#
#   sudo certbot --apache -d api.hayzeeonline.com
#
# Usage:
#
#   sudo ./docker/provision-apache.sh <domain> <app-port>
#
#   sudo ./docker/provision-apache.sh api.hayzeeonline.com 8096
#
# The script only ever touches the one domain it is given, so adding a second
# stack later is a matter of calling it again with that stack's domain + port.
#
# Runs on every deploy, so it is careful about two things:
#
#   1. It NEVER rewrites a vhost that already exists. `certbot --apache` edits
#      that file in place — adding the redirect to :443 — so regenerating it
#      from the template on a later deploy would quietly undo your TLS setup.
#      Once the file exists it belongs to you.
#   2. It verifies Apache actually serves the name afterwards, rather than
#      trusting that a2ensite and a reload did what they were asked.

set -Eeuo pipefail

DOMAIN="${1:?usage: provision-apache.sh <domain> <app-port> [alias ...]}"
PORT="${2:?usage: provision-apache.sh <domain> <app-port> [alias ...]}"

# Extra hostnames served by the SAME stack, as ServerAlias entries. Held as a
# space-separated string rather than an array so that `for n in $DOMAIN
# $ALIAS_LIST` is safe under `set -u` on older bash, where expanding an empty
# array is itself an error. Hostnames cannot contain spaces, so splitting is
# exactly what we want here.
ALIAS_LIST="${*:3}"
# The :+ so there is no trailing space when there are no aliases — otherwise
# the certbot command this script prints ends in a dangling "-d", which fails
# if you copy and paste it.
ALL_NAMES="${DOMAIN}${ALIAS_LIST:+ ${ALIAS_LIST}}"

TEMPLATE="$(dirname "$0")/apache-vhost.conf.example"
SITE="/etc/apache2/sites-available/${DOMAIN}.conf"
LINK="/etc/apache2/sites-enabled/${DOMAIN}.conf"

if [[ $EUID -ne 0 ]]; then
    echo "provision: needs root." >&2
    echo "           The deploy runs this as: sudo -S provision-apache.sh ..." >&2
    echo "           so '${SUDO_USER:-$USER}' must be able to sudo on this box." >&2
    exit 1
fi

command -v apache2ctl >/dev/null || { echo "provision: apache2 not installed" >&2; exit 1; }
[[ -f $TEMPLATE ]] || { echo "provision: $TEMPLATE not found" >&2; exit 1; }

# Deliberately not `apache2ctl -S | grep -q`. Under `set -o pipefail` that is
# a trap: grep -q exits the moment it matches, apache2ctl is still writing, it
# takes SIGPIPE, and pipefail reports the whole pipeline as failed — so the
# check fails *because* the domain was found. It only bites once the vhost map
# is long enough for the match to land early, which is why the first deploy
# passes and the next one does not.
# Every name must be served, not just the primary one: a vhost whose
# ServerAlias was hand-edited away answers for api. and falls through to the
# default site for apiv2., which is a 404 on one hostname only — the kind of
# thing nobody notices until a client reports it.
serves_domain() {
    local map name
    map="$(apache2ctl -S 2>&1)" || return 1
    for name in $ALL_NAMES; do
        case "$map" in
            *"${name}"*) ;;
            *)           return 1 ;;
        esac
    done
    return 0
}

# Every ENABLED vhost claiming this name, the -le-ssl.conf copy certbot
# generates included. Checking only the :80 file would miss the case that
# matters most: HTTPS carries all the real traffic, so a converted :80 vhost
# and an unconverted :443 one looks fine on port 80 and serves the old site to
# every actual visitor.
# Checked for every name, because a hostname that was previously served by a
# manual deployment has a vhost file of its own.
enabled_vhosts() {
    local name
    for name in $ALL_NAMES; do
        find /etc/apache2/sites-enabled -maxdepth 1 \
            \( -name "${name}.conf" -o -name "${name}-le-ssl.conf" \) 2>/dev/null
    done | sort -u
}

# Does this file hand requests to our container, rather than serving the
# application off the filesystem itself?
targets_stack() {
    grep -qE "(127\.0\.0\.1|localhost):${PORT}\b" "$1"
}

# ---- refuse to "succeed" against a pre-Docker vhost ------------------------------
# A vhost left from a manual deployment serves the app with DocumentRoot. It
# satisfies every check in the fast path below — the file exists, the site is
# enabled, Apache serves the name — so this script used to exit 0 and the
# deploy finished green while the public site carried on serving the old copy
# from disk. Nothing in the logs said so, which is the worst way to fail.
stale=""
for vhost in $(enabled_vhosts); do
    targets_stack "$vhost" || stale="${stale} ${vhost}"
done

if [[ -n $stale ]]; then
    echo "provision: REFUSING to continue — ${DOMAIN} is served by a vhost that" >&2
    echo "provision: does not proxy to this stack on 127.0.0.1:${PORT}:" >&2
    for vhost in $stale; do
        echo "provision:   ${vhost}" >&2
        grep -nE 'DocumentRoot|ProxyPass|ServerName' "$vhost" 2>/dev/null \
            | sed 's/^/provision:     /' >&2
    done
    echo "provision:" >&2
    echo "provision: The containers are already running and healthy on ${PORT};" >&2
    echo "provision: only Apache is still pointing somewhere else. Convert each" >&2
    echo "provision: file above — drop DocumentRoot/<Directory> and add:" >&2
    echo "provision:" >&2
    echo "provision:     ProxyPreserveHost On" >&2
    echo "provision:     RequestHeader set X-Forwarded-Proto expr=%{REQUEST_SCHEME}" >&2
    echo "provision:     ProxyPass        / http://127.0.0.1:${PORT}/" >&2
    echo "provision:     ProxyPassReverse / http://127.0.0.1:${PORT}/" >&2
    echo "provision:" >&2
    echo "provision: docker/apache-vhost.conf.example is the :80 reference." >&2
    echo "provision: Then: sudo apache2ctl configtest && sudo systemctl reload apache2" >&2
    exit 1
fi

# ---- fast path -------------------------------------------------------------------
# Asking Apache whether it serves the name matters as much as the file existing:
# the site can be disabled, or another vhost can shadow it, and every request
# then falls through to the default page.
if [[ -f $SITE ]] && [[ -L $LINK ]] && serves_domain; then
    echo "provision: ${DOMAIN} already served — nothing to do"
    exit 0
fi

# ---- modules (a2enmod is idempotent) ----------------------------------------------
a2enmod proxy proxy_http headers rewrite ssl >/dev/null

# ---- vhost -------------------------------------------------------------------------
if [[ -f $SITE ]]; then
    echo "provision: ${SITE} exists — leaving its contents alone"
    echo "provision: (delete it and redeploy if you want it regenerated)"

    # An alias added to the workflow after this vhost was written — or after
    # certbot took the file over — will not be in it. Say so rather than
    # editing a file we have just promised not to touch; serves_domain below
    # would fail anyway, but with a much less specific message.
    for name in $ALIAS_LIST; do
        grep -qE "^[[:space:]]*Server(Name|Alias).*\b${name//./\\.}\b" "$SITE" || {
            echo "provision: NOTE ${name} is not named in ${SITE}." >&2
            echo "provision:      Add it by hand:  ServerAlias ${name}" >&2
            echo "provision:      and extend the certificate:" >&2
            echo "provision:      sudo certbot --apache --expand -d ${ALL_NAMES// / -d }" >&2
        }
    done
else
    echo "provision: writing ${SITE} -> 127.0.0.1:${PORT}"
    [[ -n $ALIAS_LIST ]] && echo "provision: aliases: ${ALIAS_LIST}"

    # Drop the placeholder line entirely when there are no aliases, rather
    # than leaving a bare `ServerAlias`, which Apache rejects.
    if [[ -n $ALIAS_LIST ]]; then
        alias_edit="s|__SERVER_ALIAS__|ServerAlias ${ALIAS_LIST}|"
    else
        alias_edit="/__SERVER_ALIAS__/d"
    fi

    sed -e "s|__DOMAIN__|${DOMAIN}|g" \
        -e "s|__PORT__|${PORT}|g" \
        -e "$alias_edit" \
        "$TEMPLATE" > "$SITE"
fi

a2ensite "${DOMAIN}" >/dev/null

if [[ ! -L $LINK ]]; then
    echo "provision: a2ensite did not create ${LINK}" >&2
    exit 1
fi

if ! apache2ctl configtest; then
    echo "provision: config test failed — disabling ${DOMAIN} again" >&2
    a2dissite "${DOMAIN}" >/dev/null || true
    systemctl reload apache2 || true
    exit 1
fi

# Reload, not restart: connections to your other sites are not dropped.
systemctl reload apache2

if ! serves_domain; then
    echo "provision: Apache does not list ${DOMAIN} as a served name." >&2
    echo "provision: vhost map follows —" >&2
    apache2ctl -S 2>&1 | sed 's/^/    /' >&2
    exit 1
fi

echo "provision: ${ALL_NAMES// /, } -> 127.0.0.1:${PORT} on :80"
# One certificate covering every name, so there is a single renewal to care
# about. --expand is what adds a name to a certificate that already exists;
# without it certbot asks interactively and the deploy would hang.
echo "provision: TLS not managed here — sudo certbot --apache --expand -d ${ALL_NAMES// / -d }"
