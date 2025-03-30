#!/bin/bash

# Pull the latest changes from the remote repository
git pull origin main

# Install/update Composer dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
php artisan migrate --force

# Optimize the application
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Restart queue workers if you're using them
php artisan queue:restart

echo "Deployment completed successfully!" 