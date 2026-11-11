#!/usr/bin/env sh

set -eu

echo "Running database migrations..."
php artisan migrate --force

echo "Clearing stale caches..."
php artisan optimize:clear

echo "Caching production configuration..."
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "Application initialization completed."
