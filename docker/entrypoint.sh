#!/bin/sh
set -e

cd /var/www/html

# Bind-mounted source may lack these runtime dirs
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# The web installer writes DB credentials to .env, so it must be writable
[ -f .env ] && chown www-data:www-data .env 2>/dev/null || true

# Wait for MySQL
if [ -n "$DB_HOST" ]; then
    echo "Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."
    until mysqladmin --skip-ssl ping -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do
        sleep 2
    done
fi

php artisan optimize:clear >/dev/null 2>&1 || true

exec "$@"
