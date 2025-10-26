#!/bin/sh

# S'assurer que les variables d'environnement Render sont utilisées
# et non un .env baked contenant des placeholders ${VAR}
if [ -f .env ]; then
  echo "Removing baked .env to use runtime environment variables"
  rm -f .env
fi

# Nettoyer les caches avant de lancer les migrations
php artisan config:clear >/dev/null 2>&1 || true
php artisan cache:clear >/dev/null 2>&1 || true

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"
php artisan migrate --force

# Recréer le cache de config une fois les variables correctes chargées
php artisan config:cache >/dev/null 2>&1 || true

echo "Starting Laravel application..."
exec "$@"