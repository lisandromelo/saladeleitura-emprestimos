#!/bin/sh
set -e

cd /var/www/html

if [ -f artisan ]; then
    i=0
    until php artisan migrate --force >/tmp/migrate.log 2>&1; do
        i=$((i + 1))
        if [ "$i" -ge 30 ]; then
            cat /tmp/migrate.log || true
            break
        fi
        sleep 2
    done
fi

exec docker-php-entrypoint "$@"
