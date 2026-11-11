#!/usr/bin/env sh

set -eu

echo "Clearing stale application configuration..."
php artisan optimize:clear

echo "Running database migrations..."
php artisan migrate --force

echo "Database initialization completed."
