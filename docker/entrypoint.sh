#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [[ "${DB_CONNECTION:-}" == "mysql" ]]; then
  until mysqladmin ping -h"${DB_HOST:-mysql}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME:-salonos}" -p"${DB_PASSWORD:-}" --silent; do
    echo "Waiting for MySQL..."
    sleep 2
  done
fi

php artisan storage:link --force || true
php artisan optimize

exec "$@"
