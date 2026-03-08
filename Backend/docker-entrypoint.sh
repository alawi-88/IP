#!/bin/bash
set -e

# Configure Apache port (default 80, can be overridden with APACHE_PORT env var)
if [ -n "$APACHE_PORT" ] && [ "$APACHE_PORT" != "80" ]; then
    echo "Configuring Apache to listen on port $APACHE_PORT..."
    sed -i "s/Listen 80/Listen $APACHE_PORT/" /etc/apache2/ports.conf
    sed -i "s/:80/:$APACHE_PORT/" /etc/apache2/sites-available/*.conf
fi

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
max_retries=30
counter=0
until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do
    counter=$((counter + 1))
    if [ $counter -ge $max_retries ]; then
        echo "MySQL not available after $max_retries attempts, starting anyway..."
        break
    fi
    echo "MySQL not ready yet (attempt $counter/$max_retries)..."
    sleep 2
done

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force 2>/dev/null || echo "Migration failed or already up to date"

# Clear and cache config
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "Laravel is ready!"

exec "$@"
