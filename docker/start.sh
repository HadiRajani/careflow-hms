#!/bin/sh
# Container start-up: listen on the platform's port, prepare the database, start Apache.
set -e

# Railway/Render tell the app which port to use in $PORT (default 80).
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create/upgrade tables. Seeding is safe to repeat:
# demo users are updated in place, synthetic data is skipped if patients already exist.
php artisan migrate --force
php artisan db:seed --force

exec apache2-foreground
