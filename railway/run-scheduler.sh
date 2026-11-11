#!/usr/bin/env sh

set -eu

echo "Starting the CitiRx scheduler..."

while true; do
    php artisan schedule:run \
        --verbose \
        --no-interaction

    sleep 60
done
