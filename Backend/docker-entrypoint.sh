#!/bin/bash
set -e

# Install/update composer dependencies if needed
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Ensure storage directories exist and are writable
mkdir -p /var/www/storage/framework/{cache,sessions,views}
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Clear config cache
php artisan config:clear 2>/dev/null || true

# Create storage symlink
php artisan storage:link 2>/dev/null || true

# Run database migrations automatically
echo "Running database migrations..."
php artisan migrate --force 2>/dev/null || echo "Warning: Migrations failed (DB may not be ready yet)"

# Clear and rebuild caches
php artisan optimize:clear 2>/dev/null || true

exec "$@"
