#!/bin/sh
if [ -f .env ]; then
  echo "Removing baked .env to use runtime environment variables"
  rm -f .env
fi

php artisan config:clear >/dev/null 2>&1 || true
php artisan cache:clear >/dev/null 2>&1 || true

echo "Waiting for database to be ready..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"
php artisan migrate --force

php artisan config:cache >/dev/null 2>&1 || true

echo "Starting Laravel application..."
exec "$@"