#!/bin/sh

# Wait a moment for services to initialize
sleep 2

# Fix permissions before doing anything
echo "Setting up file permissions..."
chown -R www-data:www-data /var/www/html/storage/
chown -R www-data:www-data /var/www/html/bootstrap/cache/
chmod -R 775 /var/www/html/storage/
chmod -R 775 /var/www/html/bootstrap/cache/

# Create log file with correct permissions
mkdir -p /var/www/html/storage/logs
touch /var/www/html/storage/logs/laravel.log
chown www-data:www-data /var/www/html/storage/logs/laravel.log
chmod 664 /var/www/html/storage/logs/laravel.log

# Set up APP_KEY if needed
if [ -z "$(grep '^APP_KEY=' .env)" ] || [ "$(grep '^APP_KEY=' .env | cut -d= -f2)" = "" ]; then
  echo "Writing APP_KEY from environment to .env file..."
  if grep -q "^APP_KEY=" .env; then
    sed -i "s/^APP_KEY=.*/APP_KEY=${APP_KEY}/" .env
  else
    echo "APP_KEY=${APP_KEY}" >> .env
  fi
fi

# Set Laravel to log to stdout for Docker
export LOG_CHANNEL=stdout

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Test database connection
echo "Testing database connection..."
php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo 'Database connection: SUCCESS - Connected to ' . DB::connection()->getDatabaseName() . PHP_EOL;
    echo 'Database driver: ' . DB::connection()->getDriverName() . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database connection: FAILED - ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

echo "Starting application..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
