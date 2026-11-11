#!/usr/bin/env sh

set -eu

echo "Clearing cached configuration..."
php artisan config:clear

echo "Running database migrations..."
php artisan migrate --force

echo "Database initialization completed."
