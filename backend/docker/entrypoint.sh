#!/bin/sh
set -e

# Config/route/view caching is safe to redo on every boot - it just reads
# whatever env vars docker-compose injected into this container.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# No args -> this is the web (nginx + php-fpm) container, the default role.
# Args given -> the `reverb` service overrides `command:` in docker-compose
# to run `php artisan reverb:start` instead, reusing this same image.
if [ "$#" -eq 0 ]; then
    exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
else
    exec "$@"
fi
