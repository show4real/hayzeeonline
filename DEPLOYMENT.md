# Deployment

The API is deployed by GitHub Actions to a VPS as a set of Docker containers.
Nothing is built on the server: CI builds two images, pushes them to GHCR, and
the server pulls them by digest-pinned tag.

```
 git tag v1.0.0                     .github/workflows/main.yml
        |                                      |
        |                      build app image + nginx image
        |                      push to ghcr.io/<owner>/hayzeeonline-{app,nginx}
        v                                      |
   push the tag  ------------------------------+
                                               |
                       scp .env + compose + scripts to /srv/hayzeeonline
                                               |
                       ssh: docker/deploy.sh <app-img> <nginx-img>
                              pull -> migrate -> roll -> poll /up
                                               |
                       ssh: docker/provision-apache.sh api.hayzeeonline.com 8096
```

## What is where

| Path | Role |
| --- | --- |
| `.github/workflows/main.yml` | The whole pipeline. Domain, port and stack name are plain `env:` values at the top — change them there, not in the GitHub UI. |
| `docker/php/Dockerfile` | Production app image: PHP 8.2.35-fpm, no dev dependencies, authoritative classmap. |
| `docker/nginx/Dockerfile` | Production web image, built `FROM` the app image so both carry the same `public/`. |
| `docker-compose.prod.yml` | The stack: `app`, `nginx`, `queue`, `scheduler`, `redis`. |
| `docker/deploy.sh` | Runs on the VPS. Pulls, migrates, rolls, health-checks, rolls back on failure. |
| `docker/provision-apache.sh` | Creates the Apache vhost on first deploy; never overwrites an existing one. |
| `docker-compose.local.yml` | Local development only, and gitignored. Start from `docker-compose.local.yml.example`. |

## One-time setup

### 1. GitHub secrets

Repository → Settings → Secrets and variables → Actions. The workflow uses the
`production` environment, so these can live there instead if you prefer.

| Secret | What it is |
| --- | --- |
| `ENV_PRODUCTION` | The entire production `.env`, pasted verbatim. See below. |
| `VPS_HOST` | Server hostname or IP. |
| `VPS_USER` | SSH user. Must be able to `sudo`, for the Apache vhost step. |
| `VPS_PASSWORD` | That user's password. Also used for `sudo -S`. |
| `VPS_PORT` | Optional; defaults to 22. |

`GITHUB_TOKEN` is not a secret you create — Actions mints it per run, and it is
what authenticates the GHCR push and lifts Composer's GitHub rate limit.

### 2. The `ENV_PRODUCTION` secret

Start from `.env.example`. The workflow refuses to deploy unless `APP_KEY`,
`DB_HOST` and `JWT_SECRET` are present, because each of those fails in a way
that is hard to read on the server rather than at boot:

```dotenv
APP_NAME="Hayzee Online"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...                  # php artisan key:generate --show
APP_URL=https://api.hayzeeonline.com

LOG_CHANNEL=stack
LOG_STACK=stderr                    # so `docker logs` shows them
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=host.docker.internal        # a MySQL on the VPS itself
DB_PORT=3306
DB_DATABASE=hayzee_pos
DB_USERNAME=...
DB_PASSWORD=...

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis                    # the container, not 127.0.0.1
REDIS_PORT=6379

JWT_SECRET=...                      # php artisan jwt:secret --show
JWT_TTL=60

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://...:7700
MEILISEARCH_KEY=...

PAYSTACK_SECRET_KEY=sk_live_...
STRIPE_SECRET_KEY=sk_live_...
OPENAI_API_KEY=...
ANTHROPIC_API_KEY=...

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="no-reply@hayzeeonline.com"
```

Two traps worth stating plainly:

* **`REDIS_HOST=redis`, not `127.0.0.1`.** Inside a container, localhost is the
  container.
* **Do not set `APP_PORT`.** The workflow's `PROXY_PORT` overrides it, so the
  published port and the Apache vhost cannot drift apart. If you set it anyway
  the deploy logs a note and ignores it.
* **No application code may call `env()`.** The containers run `config:cache`
  at start, after which `env()` returns `null` outside `config/`. Add a key to
  `config/services.php` and read it with `config()`.

### 3. DNS

Point the name at the VPS before the first deploy — `provision-apache.sh`
checks that Apache actually serves the name, and certbot needs the record to
exist.

```
api.hayzeeonline.com.    A   <VPS_HOST>
apiv2.hayzeeonline.com.  A   <VPS_HOST>
```

Both, if you are using `DOMAIN_ALIASES` — see 3b below.

### 3b. Extra hostnames (apiv2.hayzeeonline.com)

`DOMAIN_ALIASES` in `main.yml` lists additional hostnames served by the **same**
stack. They become `ServerAlias` entries on the one vhost — no extra containers,
port, database, volumes or env secret, and one certificate to renew.

```yaml
DOMAIN: api.hayzeeonline.com
DOMAIN_ALIASES: "apiv2.hayzeeonline.com"
```

Space separated for more than one. Leave it empty for a single hostname.

Each name needs its own A record, and the certificate has to be extended to
cover it — `--expand` is what adds a name to an existing certificate, and
without it certbot prompts interactively:

```
apiv2.hayzeeonline.com.  A  <VPS_HOST>
```

```bash
sudo certbot --apache --expand \
    -d api.hayzeeonline.com -d apiv2.hayzeeonline.com
```

`provision-apache.sh` checks that Apache serves **every** name, not just the
primary one, and fails if any is missing. If the vhost already exists it will
not edit it — it prints the `ServerAlias` line and the certbot command for you
to apply, because that file may be certbot's to manage.

#### What is shared, and the one thing to know

Both names reach the same containers, so they share the database, the uploads,
Redis, sessions and rate limits. The requests are indistinguishable to the
application, with one exception worth understanding:

`URL::asset()` builds on the **host of the request**, and the upload
controllers store that absolute URL in the database. So the same photo gets a
different stored URL depending on which hostname the admin uploaded through:

```
upload via api.    ->  https://api.hayzeeonline.com/images/x.png
upload via apiv2.  ->  https://apiv2.hayzeeonline.com/images/x.png
```

Both serve fine. What used to break was deletion: `productTrait` recovered the
filename by stripping a hardcoded `https://hayzeeonline.com/images/` prefix,
which matched neither, so no file was ever removed and uploads accumulated
forever. It now reads the URL's path component instead
(`uploadPathFromUrl()`), so every variant resolves to the same file on disk.

`APP_URL` should stay the canonical name (`https://api.hayzeeonline.com`): it is
what queued jobs and console commands build URLs from, where there is no
request to take a host from.

### 4. TLS

Deliberately **not** automated. Run it once, by hand, after the first deploy:

```bash
sudo certbot --apache -d api.hayzeeonline.com
```

Certbot rewrites the vhost in place to add the `:443` block and the redirect.
`provision-apache.sh` will never regenerate a vhost that already exists, so
later deploys leave your certificate configuration alone.

### 5. The VPS

Needs Docker with the compose plugin, Apache with `mod_proxy`, and a user who
can `sudo`. `/srv/hayzeeonline` is created by the first `scp`.

## Migrating from the existing manual Apache setup

`api.hayzeeonline.com` is already served by Apache from the filesystem. The
containers will not take over on their own, and the failure is quiet: the deploy
polls `127.0.0.1:8096` directly, so it goes green while Apache carries on
serving the old copy. `provision-apache.sh` now refuses to finish in that state
and prints the exact files to change, but the work below is still yours to do.

Do all of it in one sitting — between the first deploy and the vhost switch the
containers are running but unreachable, which is harmless.

### 1. Take three things off the server first

```bash
cd /var/www/<current-app>        # wherever the manual deploy lives
grep -E '^(APP_KEY|JWT_SECRET)=' .env
```

**Copy those two values into `ENV_PRODUCTION` verbatim. Do not generate new
ones.** A new `APP_KEY` makes every encrypted value and signed cookie
undecryptable; a new `JWT_SECRET` invalidates every token in the wild, logging
out every signed-in user and app at the moment of cutover.

Then the uploads, which is the part that actually loses data:

```bash
cd /var/www/<current-app>/public
tar czf ~/hayzee-uploads.tar.gz \
    images detailedproducts insurance client_consents health_supplements quick_forms
du -sh ~/hayzee-uploads.tar.gz
```

And confirm the database credentials in that same `.env` — the stack keeps using
the existing MySQL, nothing is migrated.

### 2. Check the port is free

```bash
sudo ss -lntp | grep 8096 || echo "8096 free"
```

### 3. Let MySQL accept connections from the containers

This catches people out. The app no longer connects over the loopback — it comes
from the Docker bridge, so a user granted at `'hayzee'@'localhost'` is refused.

```bash
# In the ENV_PRODUCTION secret:
DB_HOST=host.docker.internal
DB_PORT=3306
```

```sql
-- Grant from the Docker networks as well as localhost.
CREATE USER IF NOT EXISTS 'hayzee'@'172.%' IDENTIFIED BY '<existing password>';
GRANT ALL PRIVILEGES ON hayzee_pos.* TO 'hayzee'@'172.%';
FLUSH PRIVILEGES;
```

MySQL must also be listening on more than loopback (`bind-address = 0.0.0.0` in
`mysqld.cnf`, with the port firewalled to the host). If you would rather not open
it at all, move MySQL into the compose stack instead — but then the data has to
be migrated, which this path deliberately avoids.

### 4. Deploy, then restore the uploads into the volumes

```bash
git tag v1.0.0 && git push origin v1.0.0
```

The deploy will build, migrate, start the containers and pass its health check,
then stop at the Apache step with the refusal described above. That is expected.

**The containers now serve, but every image URL returns 404.** The upload
directories are volumes (so a release cannot delete them) and on the first
deploy they are empty, while the actual files are still in the old deployment's
`public/` directory on this same machine. Copy them across:

```bash
cd /srv/hayzeeonline

# Where is the old public/ directory?
sudo find /var/www /home /srv -maxdepth 5 -type d -name detailedproducts 2>/dev/null

# Look before you leap.
sudo ./docker/restore-uploads.sh /var/www/hayzeeonline/public --dry-run

# Then copy.
sudo ./docker/restore-uploads.sh /var/www/hayzeeonline/public
```

It handles all six directories, skips any that do not exist, sets ownership to
`33:33` (`www-data` in the image — a root-owned file is one the app cannot later
replace), and reports a file count per volume so you can see it worked.

It only ever **adds**. The source directory is mounted read-only and nothing in
the volumes is deleted, so it is safe to re-run and it will not overwrite an
upload made after cutover. Keep the old directory until you have verified —
it is also your rollback.

Then check a URL that was 404ing:

```bash
docker run --rm -v hayzeeonline_product_images:/d alpine ls /d | head
curl -sI http://127.0.0.1:8096/images/<one-of-those-filenames> | head -1
```

No restart is needed — volume contents are live.

### 5. Convert the vhost

You have two files, because certbot split them. Both need converting — if you
only do the `:80` one, every real (HTTPS) visitor still gets the old site.

```bash
sudo cp /etc/apache2/sites-available/api.hayzeeonline.com.conf{,.pre-docker}
sudo cp /etc/apache2/sites-available/api.hayzeeonline.com-le-ssl.conf{,.pre-docker}
```

In **each** file, delete `DocumentRoot` and any `<Directory>` block, and add:

```apache
    ServerAlias apiv2.hayzeeonline.com
    ProxyPreserveHost On
    RequestHeader set X-Forwarded-Proto expr=%{REQUEST_SCHEME}
    RequestHeader set X-Forwarded-Port  expr=%{SERVER_PORT}
    ProxyPass        / http://127.0.0.1:8096/
    ProxyPassReverse / http://127.0.0.1:8096/
    ProxyTimeout 120
```

The `ServerAlias` is what makes `apiv2.hayzeeonline.com` reach this same stack.
`provision-apache.sh` will not add it to a vhost that already exists — it only
tells you it is missing — so this is the moment to put it in. It belongs in both
files, like everything else here.

Keep everything else in the `-le-ssl.conf` file — the `SSLCertificateFile` lines
and the `:443` wrapper stay exactly as certbot wrote them. Leave the `:80`
file's certbot redirect to HTTPS in place too; it is still correct.

`docker/apache-vhost.conf.example` is the reference for the `:80` shape.

```bash
sudo a2enmod proxy proxy_http headers
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Then extend the certificate to cover the new name, or HTTPS on `apiv2` fails
the handshake before Apache ever sees the request:

```bash
sudo certbot --apache --expand \
    -d api.hayzeeonline.com -d apiv2.hayzeeonline.com

curl -sI https://api.hayzeeonline.com/up   | head -1
curl -sI https://apiv2.hayzeeonline.com/up | head -1
```

### 6. Confirm, then re-run the deploy

```bash
curl -s https://api.hayzeeonline.com/up | head -c 40
curl -s https://api.hayzeeonline.com/api/openapi.json | head -c 60
curl -s -X POST https://api.hayzeeonline.com/api/user/products \
    -H 'Content-Type: application/json' \
    -d '{"page":1,"rows":2,"price":[0,99999999],"search_all":""}' | head -c 200
```

Re-run the workflow from the Actions tab (`workflow_dispatch`). It should now
reach the end, with `provision-apache.sh` reporting "already served — nothing to
do". From then on every release is just a tag.

### Rolling back to the manual setup

The old app directory is untouched, so it is a vhost swap:

```bash
sudo cp /etc/apache2/sites-available/api.hayzeeonline.com.conf.pre-docker \
        /etc/apache2/sites-available/api.hayzeeonline.com.conf
sudo cp /etc/apache2/sites-available/api.hayzeeonline.com-le-ssl.conf.pre-docker \
        /etc/apache2/sites-available/api.hayzeeonline.com-le-ssl.conf
sudo systemctl reload apache2
```

Keep that directory until you have had a few clean releases. Note that any
upload made while the containers were live lives in the volumes, not on the
filesystem, so it will not be visible to the old setup — copy it back the same
way if you roll back after real traffic.

### Once you are happy

Uploads now only exist in Docker volumes, so add them to whatever backs the
server up:

```bash
docker run --rm -v hayzeeonline_insurance:/src:ro -v /backups:/out \
    alpine tar czf /out/insurance-$(date +%F).tar.gz -C /src .
```

## Releasing

```bash
git tag v1.0.0
git push origin v1.0.0
```

Only `vMAJOR.MINOR.PATCH` triggers the workflow. A pre-release tag such as
`v1.0.0-test.1` deliberately does **not** match, which leaves room to add a
staging stack later without this workflow picking up its tags. There is also a
`workflow_dispatch` button for re-running a deploy without a new tag.

### What a deploy does

1. Builds and pushes both images, tagged with the version, the commit SHA and
   `latest`.
2. Writes `.env` from the secret on the runner and copies it over.
3. `deploy.sh`: pulls both images (failing early if the registry refuses),
   runs `php artisan migrate --force` once from a throwaway container, rolls
   the containers, then polls `http://127.0.0.1:8096/up` for 40s.
4. Anything that fails puts the previous images back and exits non-zero.
5. `provision-apache.sh` makes sure the vhost exists and that Apache serves it.

Migrations run from a one-off container rather than the entrypoint, because
`app`, `queue` and `scheduler` all start from the same image and would
otherwise race each other.

## Ports

One port per stack, bound to loopback, with Apache in front. The VPS also runs
the inventory API, hence the choice:

| Stack | Port |
| --- | --- |
| inventory production | 8090 |
| inventory test | 8091 |
| **hayzeeonline production** | **8096** |

`PROXY_PORT` in `main.yml` is the single source of truth: it drives the
published port, the health check and the vhost together.

## Operating it

```bash
cd /srv/hayzeeonline
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f queue

# a one-off command
docker compose -f docker-compose.prod.yml run --rm --no-deps app php artisan about

# reindex the product catalogue by hand (the scheduler does this every 5 min)
docker compose -f docker-compose.prod.yml run --rm --no-deps app \
    php artisan scout:import 'App\Models\Product'
```

### Rolling back

Images are tagged with the version, so a rollback is a deploy of the old tag:

```bash
cd /srv/hayzeeonline
STACK_NAME=hayzeeonline APP_PORT=8096 ./docker/deploy.sh \
    ghcr.io/<owner>/hayzeeonline-app:v0.9.0 \
    ghcr.io/<owner>/hayzeeonline-nginx:v0.9.0
```

`deploy.sh` does not roll migrations back. A release that changes the schema
destructively needs its own down-migration.

### State that must survive a release

Everything else lives in the image and is replaced wholesale.

| Volume | Holds |
| --- | --- |
| `<stack>_product_images` | `public/images` — product cover photos |
| `<stack>_product_detailed` | `public/detailedproducts` — gallery photos |
| `<stack>_health_supplements` | `public/health_supplements` — supplement photos |
| `<stack>_insurance` | `public/insurance` — ID cards, previous-insurance PDFs |
| `<stack>_client_consents` | `public/client_consents` — signature images |
| `<stack>_quick_forms` | `public/quick_forms` — quick-form ID uploads |
| `<stack>_uploads` | `storage/app/public` |
| `<stack>_logs` | `storage/logs` |
| `<stack>_redis` | cache, sessions, and the job queue |

The six `public/` volumes exist because the controllers write uploads straight
into the web root with `->move(public_path(...))` rather than to a Laravel disk.
The web root is part of the image, so without these volumes **every deploy would
delete every upload** — including the insurance ID documents and consent
signatures, which are KYC records and not reproducible.

If you add another `public_path()` upload target, add it in four places:
`docker-compose.prod.yml` (app **and** nginx), `docker/php/Dockerfile`,
`docker/php/entrypoint.sh`, and a `location` block in
`docker/nginx/default.conf`. `grep -r "public_path('" app/` lists the current
set.

## Troubleshooting

### `dial tcp ***:***: i/o timeout` on the copy step

```
drone-scp error: error copy file to dest: ***,
  error message: dial tcp ***:***: i/o timeout
```

The images built and pushed fine — this is the first step that touches the
server, and it never completed a TCP handshake. Nothing in this repository
affects it; it is reachability between the GitHub runner and the VPS.

**`i/o timeout` is the useful part.** The three failures look different:

| What the log says | What it means |
| --- | --- |
| `i/o timeout` | Packets are being **dropped**. A firewall, or the wrong host/port. |
| `connection refused` | The host answered — nothing is listening on that port. sshd is down or on another port. |
| `no such host` | DNS. `VPS_HOST` is wrong or unresolvable. |

So sshd is almost certainly running and the password is irrelevant so far — the
connection is being swallowed before SSH is reached.

Run the **Diagnose VPS connectivity** workflow (Actions tab → Run workflow). It
checks the shape of the secrets, resolves the name, and reports OPEN / REFUSED /
NO ANSWER for the configured port plus 443 as a control. Work through the causes
below in order; it is nearly always the first two.

#### 1. The provider's own firewall

Separate from anything on the box, and the usual culprit: Hostinger, Contabo,
OVH, Oracle Cloud, AWS and Azure all have a firewall or security group in their
control panel, which drops rather than refuses. SSH from your laptop works
because your address was allowed when the server was built.

Check the panel for an inbound rule allowing your SSH port from `0.0.0.0/0`.

#### 2. `ufw` / `iptables` on the server

```bash
sudo ufw status verbose
sudo iptables -S | grep -i -- '22\|drop\|reject'
```

A rule like `22/tcp ALLOW IN 102.89.x.x` allows only your home address.

#### 3. A wrong `VPS_PORT`

Worth a specific look. In the failing log the port is masked as `***`, which
means a `VPS_PORT` secret is set and being used — if it were unset, the
workflow's literal `22` fallback would have printed as `22`. So the value in
that secret is what the runner dialled. (Treat this as a strong hint, not proof:
GitHub masks a secret's value wherever it appears.)

Set it to your real SSH port, or delete the secret entirely to fall back to 22.

#### 4. `fail2ban`

Repeated failed runs can get the runner banned, which then drops packets.

```bash
sudo fail2ban-client status sshd
sudo fail2ban-client set sshd unbanip <runner egress ip>
```

#### 5. A malformed secret

A trailing newline pasted into `VPS_HOST`, a scheme (`https://`), or the port
appended to the host. The diagnostic workflow's first step reports the length
and flags all three without revealing the value.

### If SSH cannot be opened to the internet

Allow-listing GitHub's runners is not practical — `ubuntu-latest` runs from a
large, rotating Azure range, so an allow-list of one address breaks on the next
run. The egress address the diagnostic prints is good for a one-off unban, not
for a firewall rule. Three real options:

1. **Open the SSH port, but harden it.** Key-only auth
   (`PasswordAuthentication no`), a non-standard port, and `fail2ban`. Note this
   means moving off the `VPS_PASSWORD` secret to an `SSH_PRIVATE_KEY` one, which
   is the better arrangement regardless — pass it as `key:` instead of
   `password:` in both the scp and ssh steps.
2. **A self-hosted runner on the VPS.** No inbound SSH at all; the runner polls
   GitHub outbound. Replace `runs-on: ubuntu-latest` with `runs-on: self-hosted`
   and drop both remote steps in favour of running `deploy.sh` locally.
3. **A private network.** Tailscale has an action for this
   (`tailscale/github-action`); the runner joins your tailnet and reaches the
   VPS on its tailnet address, with nothing exposed publicly.

Option 1 is the smallest change. Option 2 is the most secure and also removes
the `scp` step entirely.

## Local development

The local stack is a separate, gitignored file, so your ports are yours:

```bash
cp docker-compose.local.yml.example docker-compose.local.yml
cp .env.example .env
docker compose -f docker-compose.local.yml up -d --build
docker compose -f docker-compose.local.yml exec php php artisan key:generate
docker compose -f docker-compose.local.yml exec php php artisan jwt:secret
docker compose -f docker-compose.local.yml exec php php artisan migrate
```

| Service | URL |
| --- | --- |
| API | http://localhost:8086 |
| phpMyAdmin | http://localhost:3703 |
| Mailpit | http://localhost:8027 |
| MySQL | `127.0.0.1:3312` |
| Redis | `127.0.0.1:6381` |

Every port is overridable, so nothing collides with the other stacks on the
machine:

```bash
NGINX_PORT=9090 docker compose -f docker-compose.local.yml up -d
```

The local image is pinned to the same PHP patch release as production
(8.2.35) with the same extension set, so "works locally" means something.

### Tests

```bash
docker compose -f docker-compose.local.yml exec php php artisan test
```

`phpunit.xml` pins the test connection to in-memory SQLite. This is not
cosmetic: most feature tests use `RefreshDatabase`, which `migrate:fresh`es the
configured connection — without that override, running the suite drops every
table in the real `hayzee_pos` database.
