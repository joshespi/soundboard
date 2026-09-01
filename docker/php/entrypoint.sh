#!/bin/sh
set -e

# vendor/ isn't baked into the image; install once on a fresh clone, skip once it exists.
if [ ! -f vendor/autoload.php ]; then
    if [ "$APP_ENV" = "production" ]; then
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        composer install --no-interaction
    fi
fi

chown -R www-data:www-data storage bootstrap/cache

# Not baked into the image either; guarded since re-linking an existing symlink errors.
[ -L public/storage ] || php artisan storage:link

php artisan migrate --force
php artisan db:seed --force

# optimize caches config — prod only; it'd freeze .env and could cache a real DB connection over phpunit's forced sqlite one.
if [ "$APP_ENV" = "production" ]; then
    php artisan optimize
else
    php artisan optimize:clear
fi

exec "$@"
