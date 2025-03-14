#!/bin/bash
set -e

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
  echo "Creating .env file from .env.example..."
  cp .env.example .env
fi

# Install composer dependencies
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
  echo "Installing dependencies..."
  composer install --no-interaction --no-progress
else
  echo "Vendor directory exists, checking for updates..."
  composer install --no-interaction --no-progress
fi

# Generate application key if not set
if grep -q "APP_KEY=base64:" .env && ! grep -q "APP_KEY=base64:.*[^=]" .env; then
  echo "Generating Laravel application key..."
  php artisan key:generate --no-interaction
fi

# Run migrations if needed
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "Running database migrations..."
  php artisan migrate --force
fi

# Cache configurations for production
if [ "${APP_ENV:-local}" = "production" ]; then
  echo "Optimizing for production..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

# Execute the main command
echo "Starting Laravel application..."
exec "$@"