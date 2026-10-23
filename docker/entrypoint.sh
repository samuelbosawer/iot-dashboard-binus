#!/bin/sh
# Make Laravel's writable folders writable by Apache (www-data) every time the container starts.
# The project folder is bind-mounted, so this cannot be done once in the Dockerfile.
set -e

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

exec docker-php-entrypoint "$@"
