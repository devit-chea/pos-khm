# Deploying to a DigitalOcean Droplet

One Droplet runs three containers from `docker-compose.prod.yml`:

- **caddy**: public ports 80/443, automatic HTTPS (Let's Encrypt), blocks the vendor's unauthenticated `/migrate` and `/clear` routes
- **app**: PHP 8.3 + Apache, built from `Dockerfile.prod`, code baked into the image
- **db**: MySQL 8, not reachable from the internet

Uploads (`public/images`, `public/documents`, logos, exports…) and `storage/` live on named Docker volumes,
so they survive rebuilds and redeploys.

## 1. Create the Droplet

- Marketplace image **Docker on Ubuntu 24.04** (or plain Ubuntu + `curl -fsSL https://get.docker.com | sh`)
- Size: at least **2 GB RAM** (MySQL + PHP); 4 GB is more comfortable
- Add your SSH key; enable backups if you want DigitalOcean snapshots
- Point your domain's **DNS A record** at the Droplet IP before the first start (Caddy needs it for the certificate)
- Firewall: allow only 22, 80, 443

```bash
ufw allow OpenSSH && ufw allow 80 && ufw allow 443 && ufw enable
```

## 2. Get the code onto the server

```bash
git clone https://github.com/<you>/<repo>.git /opt/salepro   # or upload the zip and unzip it there
cd /opt/salepro
git checkout upgrade/6.1.5                                   # the branch you deploy
```

## 3. Create `.env` on the server

```bash
cp .env.example .env
nano .env
```

Set at least:

| Key | Value |
|---|---|
| `APP_URL` | `https://pos.example.com` |
| `DOMAIN` | `pos.example.com` |
| `APP_KEY` | leave empty for now, generated in step 4 |
| `DB_DATABASE`, `DB_USERNAME` | e.g. `salepro` |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD` | long random values (`openssl rand -base64 24`) |
| `VERSION`, mail settings, `PRINTNODE_API_KEY`, payment keys… | copy from your local `.env` as needed |

`APP_ENV=production`, `APP_DEBUG=false`, `DB_HOST=db` and `TRUSTED_PROXIES=*` are forced by the compose file.

## 4. First start

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan key:generate --show
# put the printed base64:... value into APP_KEY in .env, then:
docker compose -f docker-compose.prod.yml up -d
```

Then load the database, choosing **one** of these:

**A. Copy your existing data.** Dump it locally and import it on the server:

```bash
# local machine
docker compose exec db mysqldump -usalepro -psalepro --single-transaction --routines salepro | gzip > salepro.sql.gz
scp salepro.sql.gz root@<droplet-ip>:/opt/salepro/
# server
gunzip < salepro.sql.gz | docker compose -f docker-compose.prod.yml exec -T db \
  sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

Copy uploaded images too, if any: `docker compose cp` from the local `public/images` into the `app` container.

**B. Start empty:**

```bash
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class='Database\Seeders\Tenant\TenantDatabaseSeeder' --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class='Database\Seeders\LocalBootstrapSeeder' --force
```

This creates login `admin` / `admin123`. **Change that password immediately.**

Open `https://pos.example.com`.

## Updating

```bash
cd /opt/salepro
git pull
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan translations:sync
```

Back up first (below). Don't run `php artisan optimize` or `view:cache` (see CLAUDE.md).

## Backups

The database and the upload volumes are the only state. A daily database dump via the host's cron:

```bash
mkdir -p /opt/backups
crontab -e
# 03:00 every day, keep 14 days
0 3 * * * cd /opt/salepro && docker compose -f docker-compose.prod.yml exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines "$MYSQL_DATABASE"' | gzip > /opt/backups/salepro-$(date +\%F).sql.gz && find /opt/backups -name 'salepro-*.sql.gz' -mtime +14 -delete
```

Copy the dumps off the server (e.g. DigitalOcean Spaces with `s3cmd`), or enable Droplet backups, which also cover the upload volumes.

## Useful commands

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f app        # PHP/Apache logs
docker compose -f docker-compose.prod.yml exec app php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec app tail -f storage/logs/laravel.log
```

## Things to know

- **No scheduler runs.** `app/Console/Kernel.php` schedules the vendor demo reset `reset:db` twice a day,
  which drops every table. If you need `purchase:auto` / `dsoalert:find`, remove the `reset:db` entry first,
  then add a host cron entry: `* * * * * cd /opt/salepro && docker compose -f docker-compose.prod.yml exec -T app php artisan schedule:run`.
- The upload volumes are filled from the image only when they are first created. Default images added in a later
  vendor release won't appear in them automatically; copy them in with `docker compose cp` if needed.
- `docker compose -f docker-compose.prod.yml down -v` **deletes the database and all uploads.** Use `down` without `-v`.
