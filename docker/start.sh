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
# The database may still be starting: retry for up to ~60 seconds before giving up.
echo "Database host: ${DB_HOST:-<not set>}"
for i in $(seq 1 30); do
    if php artisan migrate --force; then
        break
    fi
    echo "Database not ready yet (attempt $i/30), retrying in 2s..."
    sleep 2
done

php artisan db:seed --force

exec apache2-foreground
