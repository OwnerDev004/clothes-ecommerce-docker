#!/bin/bash
set -e

# Create required directories if they don't exist
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/bootstrap/cache

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate application key if it doesn't exist
if [ -z "$(grep '^APP_KEY=' .env | grep -v '=$')" ]; then
    php artisan key:generate
fi

# Run migrations if the database is ready
if [ "$DB_HOST" != "" ]; then
    # Wait for the database to be ready (use DB_PORT if set, default to 5432 for PostgreSQL)
    DB_PORT=${DB_PORT:-5432}
    until nc -z -v -w30 $DB_HOST $DB_PORT; do
      echo "Waiting for database connection..."
      # Wait for 5 seconds before check again
      sleep 5
    done

    php artisan migrate --force
fi

# Copy Nginx config
cp /var/www/html/nginx.conf /etc/nginx/sites-available/default

# Start PHP-FPM in the background
php-fpm -D

# Start Nginx in the foreground
exec nginx -g "daemon off;"