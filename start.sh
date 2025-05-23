#!/bin/bash
set -e

echo "Checking environment variables..."
echo "DATABASE_URL: $DATABASE_URL"
echo "Using port: $PORT"

PORT=${PORT:-9000} # Default to port 9000 if not set

echo "Waiting for database..."

# Test database connection using Symfony's doctrine:schema:validate
until php bin/console doctrine:schema:validate --env=prod --no-interaction > /dev/null 2>&1; do
    echo "Database not available, waiting 5 seconds..."
    sleep 5
done

echo "Database is available!"

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --env=prod --no-interaction --allow-no-migration

echo "Starting server..."
php -S 0.0.0.0:${PORT} -t public &

echo "Starting message consumer for async tasks..."
php bin/console messenger:consume async --env=prod --time-limit=3600 --memory-limit=256M
